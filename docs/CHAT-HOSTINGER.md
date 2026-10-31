# Team chat on Hostinger shared hosting

The web CRM and React Native app use the same Laravel chat API and private Pusher Channels app. Messages are stored in MySQL. Pusher delivers update notifications; clients fetch messages through authenticated HTTP requests. No Reverb process or continuously running queue worker is required.

## Deploy the backend

Upload the backend changes, including `public/admin/js/chat.js`, `public/admin/css/chat.css`, and the bundled Pusher/Echo assets in `public/admin/libraries/js/`. Keep the existing production `.env` and database credentials.

From the Laravel application directory, using Hostinger's PHP 8.2+ CLI:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
```

The migrations add conversations, members, messages, and any missing account-status/device-token fields. They do not move or replace the leads database. Back up the production database through hPanel before running production migrations. The existing PHP `curl`, `sodium`, and MySQL extensions should be enabled.

Create a **Channels** app at <https://dashboard.pusher.com/>, open **App Keys**, and set these values in the **server's** `.env`:

```dotenv
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_public_app_key
PUSHER_APP_SECRET=your_private_app_secret
PUSHER_APP_CLUSTER=your_actual_cluster
PUSHER_SCHEME=https
PUSHER_PORT=443
```

Run `php artisan config:cache` again after changing these values. If hPanel has no terminal, use the account's SSH access or a one-time scheduled command pointing at the application directory; remove the one-time job afterwards. Do not create a public URL that runs Artisan commands.

Pusher credentials are not stored in frontend bundles. Only the public key and cluster are returned by the authenticated configuration endpoint. Do not put the secret in mobile code or a `VITE_` variable.

The production server must allow outbound HTTPS to Pusher. Open **Team Chat** in the CRM sidebar. Before credentials are configured, chat uses a 15-second polling fallback. With Pusher connected, messages arrive through private channels and periodic HTTP reconciliation recovers missed events.

The top bar uses a white background across modules. Chat list and conversation headers show circular contact pictures, with the name's first initial as the fallback when a picture is absent or fails to load. The shared admin layout now connects on every CRM page. Incoming messages show a notification with an **Open chat** link even while the recipient uses Leads, Dashboard, or another module. The chat screen reuses this same connection. This requires a CRM tab to remain open; notifications while every CRM tab/browser is closed require a separate browser Web Push/service-worker integration, which is not included.

The header has separate Messages and Notifications controls. The Messages badge counts unread messages, highlights while unread messages remain, and clears as messages are read. There is no sound toggle in the top bar. Notification sound stays enabled; browsers may require a click or keypress before allowing notification audio. The web client confirms delivery when it receives messages on any admin page. Sent messages show one gray tick, two gray received ticks, or two blue read ticks; group double ticks require all other members to confirm. Deploy the `2026_10_31_000004_add_delivery_cursor_to_chat_members.php` migration before using these receipts.

Chat now supports profile-picture upload (2 MB), one image/PDF/audio attachment per message (10 MB), and browser voice recording (up to 2 minutes). Run the attachment migration `2026_10_31_000005_add_attachments_to_chat_messages.php`. Voice recording requires HTTPS and microphone permission. Files are stored under `storage/app/private/chat/attachments` and served through member-authorized routes, not public links. Keep that directory writable and persistent across deployments. Profile pictures use the existing public storage disk and must be accessible at its configured URL. The FTP workflow excludes private uploads and local test-storage files.

In Hostinger PHP settings, allow at least `upload_max_filesize=10M` and `post_max_size=12M` for the advertised attachment limit; a lower host limit can reject the request before Laravel validates it. This does not change the app's existing PHP configuration automatically. Only the web chat recording/upload interface is included; mobile developers can use the same endpoints with native pickers/recorders.

To diagnose delayed delivery after uploading the latest code:

```sh
php artisan config:clear
php artisan config:cache
php artisan chat:status --probe
```

This command displays whether credentials are present without printing their values. The optional probe performs a read-only Pusher API request; it does not send anyone a test message. A successful HTTPS check confirms server credentials/connectivity, but the recipient's browser must also successfully authorize and subscribe to its private channel. The Team Chat status should read **Live connection**. **Polling**, **Reconnecting**, or **Subscription failed** means fallback delivery is active.

## Deploy the mobile app

The sibling `Mobile App` includes the chat screen, drawer entry, JWT-authenticated API client, and official Pusher React Native SDK. Its key and cluster come from `/api/v1/chat/config`; no separate mobile credentials are needed.

```sh
npm ci
# For iOS, on macOS:
cd ios && pod install && cd ..
# Rebuild the native app, rather than only reloading Metro:
npm run android
```

Use the existing release-signing process to distribute an updated Android/iOS build. Background push delivery uses the existing Firebase service-account file and each user's registered `device_token`. Foreground notifications use the app's existing Firebase listener. Notification taps do not automatically deep-link to a conversation in this version; open Team Chat to read it.

## Chat behavior and access

- Active users can start a one-to-one conversation with another active user.
- Super admins and users who can view all team leads can create named groups, up to 50 members including themselves.
- Only conversation members can list history, send messages, mark reads, or send typing updates. Super admins do not gain access to private chats they have not joined.
- Private channels are personal: `private-chat.user.{user_id}`. Web subscription authorization uses the session; mobile authorization uses the existing JWT Bearer token.
- Messages have a 5,000-character limit. Clients send a UUID `client_id`; retrying the same message does not create another row or send another notification.
- Read cursors only move forward. History supports older-page loading and incremental catch-up. Group membership is set when a group is created; membership editing is not included.
- Each new message creates a `CHAT_MESSAGE` record in the existing Notifications module for every other active conversation member, even if they have no mobile device token. The admin bell refreshes on message/read events and through the polling fallback. Notification links open the matching conversation. Reading messages marks their corresponding chat notifications read without clearing unrelated lead alerts. Retrying a message does not duplicate its saved notifications.
- Message broadcasts are synchronous (`ShouldBroadcastNow`). Provider failures do not discard saved messages; polling recovers them. Firebase delivery is best effort and uses the existing device token for each user.

## API reference

All mobile endpoints start with `/api/v1/chat`, require `Authorization: Bearer YOUR_JWT`, and return JSON. Web uses the same paths under `/admin/chat` with session authentication and CSRF protection for writes.

| Method | Path | Request |
| --- | --- | --- |
| GET | `/config` | Public client settings for the authenticated user |
| GET | `/users` | `search`, `page` |
| GET | `/conversations` | `page` |
| POST | `/conversations` | `type: direct/group`, `member_ids: [id, ...]`, `name` for groups |
| GET | `/conversations/{id}/messages` | Optional `before` or `after` message ID, not both; 100 messages per page |
| POST | `/conversations/{id}/messages` | `body`, UUID `client_id` |
| POST | `/conversations/{id}/read` | Last displayed `message_id` |
| POST | `/conversations/{id}/typing` | Empty JSON body |
| POST | `/broadcasting/auth` | `channel_name`, `socket_id` |

Lists return `data` and pagination metadata; message history returns `data` and `has_more`. Each `chat.updated` event has a `kind` (`conversation`, `message`, `read`, `typing`) and `conversation_id`. Message events contain `message_id`; fetch content through the history endpoint. Typing events contain the sender's ID/name and expire in the clients after a few seconds.

## Verify after deployment

Sign in as two active users. Start a direct chat, send from each client, verify history after refresh, read receipts, unread counts, and typing indicators. Try an unrelated user: the private-channel and conversation endpoints must return 403. Repeat a send with the same `client_id` and confirm no duplicate. Disable Pusher temporarily and confirm polling still delivers persisted messages.

Official references: [Laravel broadcasting](https://laravel.com/docs/12.x/broadcasting), [Pusher private-channel authorization](https://pusher.com/docs/channels/server_api/authorizing-users/), [Pusher React Native SDK](https://github.com/pusher/pusher-websocket-react-native).
