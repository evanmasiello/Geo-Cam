#!/usr/bin/env bash
# Behavioral regression checks for the privacy, flag, comment and
# registration fixes. Runs the real endpoints against a temporary store.
set -euo pipefail

if ! command -v php >/dev/null 2>&1; then
  echo "php not found; skipping behavior tests"
  exit 0
fi

root="$(cd "$(dirname "$0")/.." && pwd)"
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT

passed=0

ok() {
  passed=$((passed + 1))
  echo "ok $passed - $1"
}

fail() {
  echo "not ok - $1" >&2
  exit 1
}

run() {
  (cd "$tmp/php" && php -d error_reporting=0 -d display_errors=0 -r "$1" 2>/dev/null)
}

setup_store() {
  rm -rf "$tmp/php" "$tmp/posts" "$tmp/logs"
  mkdir -p "$tmp/php" "$tmp/posts" "$tmp/logs"

  cp "$root/php/getGlobalFeed.php" "$tmp/php/"
  cp "$root/php/newGetPost.php" "$tmp/php/"
  cp "$root/php/getPostsComments.php" "$tmp/php/"
  cp "$root/php/getPostImage.php" "$tmp/php/"
  cp "$root/php/flagPost.php" "$tmp/php/"
  cp "$root/php/addComment.php" "$tmp/php/"
  cp "$root/php/deleteComment.php" "$tmp/php/"
  cp "$root/php/createUser.php" "$tmp/php/"

  printf 'CLOSED' > "$tmp/php/status.txt"
  printf 'CLOSED' > "$tmp/logs/logStatus.txt"
  printf '[]' > "$tmp/php/flags.json"
  printf '[]' > "$tmp/php/commentFlags.json"
  printf '[]' > "$tmp/php/blockedWords.json"
  printf '[]' > "$tmp/php/mailKeys.json"

  cat > "$tmp/php/users.json" <<'JSON'
[
  {"id":1,"user":"owner","pass":"x","email":"o@example.com","emailIsVerified":true,"followed":"[2]","commentCount":0},
  {"id":2,"user":"friend","pass":"x","email":"f@example.com","emailIsVerified":true,"followed":"[]"},
  {"id":3,"user":"stranger","pass":"x","email":"s@example.com","emailIsVerified":true,"followed":"[]"}
]
JSON

  php -r '
    $sessions = [
      ["key" => hash("sha256", "ownertok"), "userId" => 1, "time" => time()],
      ["key" => hash("sha256", "friendtok"), "userId" => 2, "time" => time()],
      ["key" => hash("sha256", "strangertok"), "userId" => 3, "time" => time()],
    ];
    file_put_contents($argv[1], json_encode($sessions));
  ' "$tmp/php/sessions.json"

  cat > "$tmp/posts/posts.json" <<'JSON'
[
  {"id":0,"user":1,"lat":40.01,"long":-75.01,"time":100,"likes":1,"visibility":"all","hidden":false,"comments":"[{\"id\":0,\"user\":3,\"comment\":\"visible\"},{\"id\":1,\"user\":3,\"comment\":\"secret-hidden\",\"hidden\":true}]"},
  {"id":1,"user":1,"lat":40.02,"long":-75.02,"time":101,"likes":2,"visibility":"following","hidden":false,"comments":"[{\"id\":0,\"user\":1,\"comment\":\"follower-only\"}]"},
  {"id":2,"user":1,"lat":40.03,"long":-75.03,"time":102,"likes":3,"visibility":"all","hidden":true,"comments":"[]"}
]
JSON

  printf 'PUBLICIMG' > "$tmp/posts/post0.png"
  printf 'FOLLOWIMG' > "$tmp/posts/post1.png"
  printf 'HIDDENIMG' > "$tmp/posts/post2.png"
  printf 'SMALL0' > "$tmp/posts/smallPost0.jpg"
}

setup_store

# --- feed privacy ---------------------------------------------------------

global="$(run '$_POST=["lat"=>40,"long"=>-75]; include "getGlobalFeed.php";')"

case "$global" in
  *'"id":1'*) fail "global feed leaked a follower-only post" ;;
  *'"id":2'*) fail "global feed leaked a hidden post" ;;
  *'"lat":0'*) ;;
  *) fail "global feed did not zero coordinates" ;;
esac

case "$global" in
  *secret-hidden*) fail "global feed leaked a hidden comment" ;;
  *visible*) ;;
  *) fail "global feed dropped a visible comment" ;;
esac

ok "global feed excludes hidden/follower-only posts and strips hidden comments"

anon="$(run '$_POST=["lat"=>40,"long"=>-75]; include "newGetPost.php";')"

case "$anon" in
  *'"id":1'*|*'"id":2'*) fail "local feed leaked a private/hidden post to an anonymous caller" ;;
  *'"id":0'*) ;;
  *) fail "local feed dropped the public post" ;;
esac

friend="$(run '$_POST=["lat"=>40,"long"=>-75,"session"=>"friendtok"]; include "newGetPost.php";')"

case "$friend" in
  *'"id":1'*) ;;
  *) fail "local feed hid a follower-only post from an allowed follower" ;;
esac

ok "local feed applies server-side visibility"

# --- comment privacy ------------------------------------------------------

public_comments="$(run '$_POST=["id"=>0]; include "getPostsComments.php";')"

case "$public_comments" in
  *secret-hidden*) fail "public comments leaked a hidden comment" ;;
  *visible*) ;;
  *) fail "public comments dropped a visible comment" ;;
esac

anon_comments="$(run '$_POST=["id"=>1]; include "getPostsComments.php";')"
[ "$anon_comments" = "[]" ] || fail "anonymous caller read follower-only comments"

friend_comments="$(run '$_POST=["id"=>1,"session"=>"friendtok"]; include "getPostsComments.php";')"

case "$friend_comments" in
  *follower-only*) ;;
  *) fail "allowed follower could not read follower-only comments" ;;
esac

ok "comments enforce visibility and hide hidden comments"

# --- image proxy ----------------------------------------------------------

[ "$(run '$_GET=["id"=>0]; include "getPostImage.php";')" = "PUBLICIMG" ] ||
  fail "public image was not served"
[ -z "$(run '$_GET=["id"=>1]; include "getPostImage.php";')" ] ||
  fail "anonymous caller fetched a follower-only image"
[ "$(run '$_GET=["id"=>1]; $_COOKIE=["session"=>"friendtok"]; include "getPostImage.php";')" = "FOLLOWIMG" ] ||
  fail "allowed follower could not fetch a follower-only image"
[ -z "$(run '$_GET=["id"=>2]; include "getPostImage.php";')" ] ||
  fail "hidden image was served"

ok "image proxy enforces post visibility"

# --- flag threshold and dedupe -------------------------------------------

[ "$(run '$_POST=["id"=>0,"reason"=>"vulgar","session"=>"ownertok"]; include "flagPost.php";')" = "1" ] ||
  fail "first flag was not recorded"
[ "$(run '$_POST=["id"=>0,"reason"=>"other","session"=>"ownertok"]; include "flagPost.php";')" = "alreadyChecked" ] ||
  fail "duplicate flag from the same user was accepted"
[ "$(run '$_POST=["id"=>0,"reason"=>"vulgar","session"=>"friendtok"]; include "flagPost.php";')" = "1" ] ||
  fail "second flag was not recorded"

[ "$(php -r '$a=json_decode(file_get_contents($argv[1])); echo $a[0]->hidden ? "1" : "0";' "$tmp/posts/posts.json")" = "0" ] ||
  fail "post was hidden before the threshold"

[ "$(run '$_POST=["id"=>0,"reason"=>"vulgar","session"=>"strangertok"]; include "flagPost.php";')" = "1" ] ||
  fail "third flag was not recorded"

[ "$(php -r '$a=json_decode(file_get_contents($argv[1])); echo $a[0]->hidden ? "1" : "0";' "$tmp/posts/posts.json")" = "1" ] ||
  fail "post was not hidden after three distinct flags"

ok "flags require three distinct users and dedupe per user"

# --- comment count integrity ---------------------------------------------

php -r '
  $users = json_decode(file_get_contents($argv[1]));
  foreach ($users as $u) unset($u->commentCount);
  file_put_contents($argv[1], json_encode($users));
  $posts = json_decode(file_get_contents($argv[2]));
  $posts[0]->comments = json_encode([]);
  file_put_contents($argv[2], json_encode($posts));
' "$tmp/php/users.json" "$tmp/posts/posts.json"

[ "$(run '$_POST=["id"=>999,"comment"=>"hi","session"=>"friendtok"]; include "addComment.php";')" = "0" ] ||
  fail "commenting on a missing post did not fail"

[ "$(php -r '$u=json_decode(file_get_contents($argv[1])); echo $u[1]->commentCount ?? 0;' "$tmp/php/users.json")" = "0" ] ||
  fail "missing-post comment changed the comment count"

[ "$(run '$_POST=["id"=>0,"comment"=>"hi","session"=>"friendtok"]; include "addComment.php";')" != "0" ] ||
  fail "valid comment was rejected"

[ "$(php -r '$u=json_decode(file_get_contents($argv[1])); echo $u[1]->commentCount ?? 0;' "$tmp/php/users.json")" = "1" ] ||
  fail "valid comment did not increment the comment count"

run '$_POST=["id"=>0,"commentId"=>0,"session"=>"friendtok"]; include "deleteComment.php";' >/dev/null
run '$_POST=["id"=>0,"commentId"=>0,"session"=>"friendtok"]; include "deleteComment.php";' >/dev/null

[ "$(php -r '$u=json_decode(file_get_contents($argv[1])); echo $u[1]->commentCount ?? 0;' "$tmp/php/users.json")" = "0" ] ||
  fail "deleting the same comment twice decremented the count twice"

ok "comment counts survive missing posts and repeated deletes"

# --- first-user registration ---------------------------------------------

printf '[]' > "$tmp/php/users.json"
printf '[]' > "$tmp/php/sessions.json"
printf '[]' > "$tmp/php/mailKeys.json"
printf '[]' > "$tmp/php/blockedWords.json"
printf 'CLOSED' > "$tmp/php/status.txt"

run '$_SERVER["REQUEST_METHOD"]="POST"; $_POST=["uname"=>"newuser","pass"=>"secret","email"=>"bad"]; include "createUser.php";' >/dev/null

[ "$(php -r 'echo count(json_decode(file_get_contents($argv[1])));' "$tmp/php/users.json")" = "0" ] ||
  fail "invalid email created an account"

run '$_SERVER["REQUEST_METHOD"]="POST"; $_POST=["uname"=>"newuser","pass"=>"secret","email"=>"new@example.com"]; include "createUser.php";' >/dev/null

[ "$(php -r 'echo count(json_decode(file_get_contents($argv[1])));' "$tmp/php/users.json")" = "1" ] ||
  fail "first signup against an empty users.json did not create an account"

ok "registration validates email and works on an empty store"

echo "$passed behavior checks passed"
