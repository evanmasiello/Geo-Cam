# RFC: Push notifications for Android and iPhone

## Current state

The Android and iOS apps are thin wrappers around the web app. They provide
no native notification capability — users only see activity when they actively
open the app and refresh the feed. For a social app, this means likes,
comments, follows, and direct interactions are invisible until the user
happens to check.

## Why it matters

- **Engagement**: push notifications are the primary driver of return visits
  for social apps. Without them, the app is effectively a passive viewer.
- **Real-time feel**: the current feed requires manual refresh. Notifications
  make the app feel alive even when it’s in the background.
- **Platform expectations**: users on both platforms expect apps to notify
  them of interactions. A wrapper that doesn’t is indistinguishable from a
  website shortcut.

## Constraints of wrapper apps

Because the apps are wrappers (likely a WebView or similar), the options are
narrower than for a fully native app:

- **Web Push API** (service workers) works in modern mobile browsers and
  WebViews, but iOS Safari support is partial (requires iOS 16.4+, user
  gesture to request permission, limited background delivery).
- **Native push** (FCM for Android, APNs for iOS) requires a native bridge
  inside the wrapper — the wrapper must expose a way for the web layer to
  register a device token and receive callbacks when a notification arrives.
- **Background execution** is heavily restricted on both platforms. A pure
  web wrapper cannot run JavaScript in the background reliably; native push
  is the only path to guaranteed delivery when the app is closed.

## Options

### A. Web Push API (service worker)

Add a service worker to the web app that registers for push subscriptions.
The wrapper must allow service worker registration and persist the subscription
endpoint + keys.

- **Pro**: no native code changes; works on Android Chrome and modern iOS
  Safari; server-side sending via `web-push` library or similar.
- **Pro**: the same service worker code works for desktop browser users.
- **Con**: iOS background delivery is unreliable; notifications may not
  arrive if the app is force-quit or the system reclaims resources.
- **Con**: the wrapper must explicitly support service workers (some WebView
  configurations disable them).

### B. Native push via FCM + APNs (recommended)

Add a native bridge to each wrapper app:

1. **Android** — use Firebase Cloud Messaging. The wrapper registers an FCM
   token, exposes it to the web layer via a JavaScript interface
   (`window.Android.getFcmToken()` or similar), and the web app POSTs it to
   your backend.
2. **iOS** — use Apple Push Notification Service. Same pattern: wrapper gets
   an APNs device token, exposes it to the web layer, backend stores it.
3. **Backend** — add a `push_tokens` table/collection mapping
   `user_id` → `{platform, token, last_used}`. When an event fires, look up
   the user’s active tokens and send via FCM / APNs HTTP v2 APIs.

- **Pro**: guaranteed delivery even when the app is backgrounded/closed.
- **Pro**: rich notifications (actions, images, badges) on both platforms.
- **Con**: requires modifying the wrapper apps to add the native bridge and
  permission flows.
- **Con**: two separate push infrastructures (FCM + APNs) to maintain.

### C. Hybrid: service worker + native fallback

Use Web Push as the primary path (works for most users), and fall back to
native push only for iOS where Web Push is unreliable. The backend sends to
both channels and deduplicates.

- **Pro**: best coverage; no single point of failure.
- **Con**: most complex; requires maintaining both systems.

## Backend changes

A new `push_tokens` store (file or table) plus a lightweight notification
dispatcher:

```php
// php/pushTokens.php
$tokens = json_decode(file_get_contents("pushTokens.json"));
foreach ($tokens as $token) {
    if ($token->user_id == $uID) {
        // send via FCM or APNs depending on $token->platform
    }
}
```

Events that should trigger notifications:
- **likes** — `likePost.php`
- **comments** — `addComment.php`
- **follows** — `followUser.php`
- **direct interactions** — any mention or reply (future)

Each event calls `queueNotification($userId, $eventType, $payload)` which
writes to a small queue file or directly calls the push provider.

## What the wrapper apps need to do

1. Request notification permission from the user on first launch.
2. Register with FCM (Android) or APNs (iOS) and obtain a device token.
3. Expose the token to the web layer via a JS bridge.
4. On app open / login, POST the token to the backend (`php/pushTokens.php`).
5. Handle incoming notifications: deep-link into the relevant post/profile.

## Open questions

1. Do the current wrapper apps already expose a JS-to-native bridge, or does
   that need to be added from scratch?
2. Should notifications be sent for all events, or should users have granular
   control (e.g., notify on likes but not follows)?
3. Do we need a notification history screen in the app, or is the system
   notification tray sufficient?

## References

- PR #31 — usage metric tracking (event instrumentation feeds into notifications)
- PR #30 — SQLite migration (push tokens fit naturally as a SQLite table)
- `php/likePost.php`, `php/addComment.php`, `php/followUser.php` — event sources
