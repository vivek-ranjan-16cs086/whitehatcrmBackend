# Multiple device notifications

Deploy the backend and run `php artisan migrate` before enabling clients. The migration copies existing users.device_token values to user_devices. The old column is retained for rollback compatibility but is no longer read or written by application code. Duplicate legacy tokens are assigned to the last user by ID because their original owner cannot be inferred.

## Android / iOS / JWT web client

POST /api/v1/device/register with Authorization: Bearer <JWT> and JSON:

```json
{"device_id":"installation-or-browser-profile-uuid","platform":"android","fcm_token":"FCM_TOKEN"}
```

Use android, web, or ios as platform. Persist a randomly generated device_id per installation/browser profile and reuse it after token refresh. Register after login and whenever FCM returns a new token. Multiple browser tabs should share the profile ID and coordinate foreground popups.

Before logout, POST /api/v1/device/unregister with the same authenticated user and {"device_id":"installation-or-browser-profile-uuid"}. Alternatively pass device_id to POST /api/v1/logout; it removes only that registration before revoking the JWT. Without device_id, logout keeps registrations for compatibility; updated clients must supply it or unregister first. Clear local notification state on account switch and register the token for the new account.

## Session-authenticated Laravel web client

POST /device/register and /device/unregister accept the same JSON, using session cookies and Laravel CSRF protection. Registration remembers the browser device ID in the session so the existing web logout removes it automatically. Firebase browser permission, service worker setup, and client calls must be implemented by the web client; this change supplies backend support.

## Existing client compatibility

POST /api/v1/store-device-token still accepts deviceToken, plus optional device_id and platform. Without device_id it uses a token hash as a temporary installation ID. This avoids overwriting other devices but cannot correlate token rotation: upgrade clients to stable device IDs. Registering a migrated token with a stable ID replaces its legacy registration.

## Delivery

Lead, role, reminder and chat push sends use every registered device. Notification history remains one row per recipient/event. The token hash has a unique constraint so a token cannot remain attached to two accounts. Registration moves an existing token to the authenticated account. Concurrent conflicting registrations can be rejected by the unique constraint; clients may retry registration.

Firebase UNREGISTERED responses remove only the matching unchanged token. Other failures are reported and tokens retained; subsequent devices are still attempted. Automatic retry of transient push failures is not configured. Real Firebase delivery must be verified with valid credentials and client devices.

## Due follow-up reminders

Run Laravel's scheduler every minute with `php artisan schedule:run`. The `crm:due-followup-reminder` command sends one digest to each enabled assignee with upcoming follow-ups at 6:00 AM and 5:18 PM India time. Each digest includes up to five leads due within 12 hours of its start time, ordered by earliest follow-up.

When a user opens a notification with type `DUE_FOLLOWUP_LEADS`, call `GET /api/v1/due_followup_leads` with their bearer token. The endpoint requires lead-view permission and returns all leads assigned to the signed-in user that are due in the next 12 hours under `data.leads`. Notification history stores the digest leads under `data.leads`. Firebase sends `leads` as a JSON string with lead IDs and follow-up timestamps. Handle other notification types according to their existing destinations.
