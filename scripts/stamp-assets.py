#!/usr/bin/env python3
"""Append a content hash to local js/css references in the HTML pages.

Run at deploy time against the checkout, never committed back. The browser
sees a new URL whenever a file's contents actually change, so pages can be
cached hard without going stale -- and because the hash is derived from the
bytes, it cannot drift the way hand-bumped V-numbers did.

Idempotent: an existing ?v= is replaced, not appended to.
"""
import hashlib, pathlib, re, sys

ROOT = pathlib.Path(sys.argv[1] if len(sys.argv) > 1 else ".")
REF = re.compile(r'((?:src|href)=")(\./(?:js|css)/[^"?]+)(\?[^"]*)?(")')

def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()[:8]

stamped = missing = 0
for html in sorted(ROOT.glob("*.html")):
    original = html.read_text()

    def repl(m):
        global stamped, missing
        prefix, ref, _old_query, suffix = m.groups()
        target = ROOT / ref[2:]
        if not target.is_file():
            print(f"::warning::{html.name} references missing {ref}")
            missing += 1
            return m.group(0)
        stamped += 1
        return f"{prefix}{ref}?v={digest(target)}{suffix}"

    updated = REF.sub(repl, original)
    if updated != original:
        html.write_text(updated)
        print(f"  stamped {html.name}")

print(f"{stamped} reference(s) stamped, {missing} missing")
sys.exit(1 if missing else 0)
