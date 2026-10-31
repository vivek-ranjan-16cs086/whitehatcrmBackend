# React Native chat API handoff

Base URL: `https://zitrackapi.apexelyria.com/api/v1`

Every endpoint below requires the existing JWT login token:

```http
Authorization: Bearer <JWT_ACCESS_TOKEN>
Accept: application/json
Content-Type: application/json
```

Use the existing Axios client in `Mobile App/src/api/apiClient.ts` to retain token refresh/error handling. Chat endpoints return their objects directly, without the generic `{status, data}` wrapper used by some older endpoints.

## Endpoints

| Method | Path relative to base URL | Purpose / input |
| --- | --- | --- |
| GET | `/chat/config` | Authenticated user's Pusher configuration and group-creation capability |
| POST | `/chat/profile-picture` | Multipart upload, `profile_pic` image; maximum 2 MB |
| GET | `/chat/unread` | Total unread messages across all conversations and incoming/delivery cursors |
| GET | `/chat/users?search=Shreya&page=1` | Active users excluding self; optional search and page; 100 per page |
| GET | `/chat/conversations?page=1` | User's conversations, members, latest messages and unread counts; 50 per page |
| GET | `/chat/conversations/{id}` | One conversation; members only |
| POST | `/chat/conversations` | Start/reuse a direct chat or create a group; examples below |
| GET | `/chat/conversations/{id}/messages` | Latest 100 messages, returned in ascending ID order |
| GET | `/chat/conversations/{id}/messages?before=120` | Older messages, before the earliest displayed ID |
| GET | `/chat/conversations/{id}/messages?after=120` | New messages, after the latest displayed ID; use `after=0` to start catch-up from the beginning |
| POST | `/chat/conversations/{id}/messages` | Send `{body, client_id}` |
| GET | `/chat/conversations/{id}/messages/{messageId}/file` | Member-only attachment download/stream; JWT required |
| POST | `/chat/conversations/{id}/read` | Mark through the last displayed `{message_id}` as read |
| POST | `/chat/conversations/{id}/delivered` | Confirm the recipient's client received through `{message_id}`; does not mark read |
| POST | `/chat/conversations/{id}/typing` | Empty `{}` body; throttle client calls to once per 3 seconds |
| POST | `/chat/broadcasting/auth` | Authorize Pusher subscription with `{socket_id, channel_name}` and JWT header |
| GET | `/notifications` | Existing notifications, including `CHAT_MESSAGE`; optional `?is_read=0` |
| GET | `/notifications/count` | Existing unread notification count |
| PATCH | `/notifications/read/{id}` | Mark one notification as read; empty body |
| DELETE | `/notifications/{id}` | Delete one notification |
| DELETE | `/notifications` | Clear own notifications |
| POST | `/store-device-token` | Register Firebase token with `{deviceToken}`; update it after token refresh |

`{id}` in chat paths means conversation ID. Notification paths use notification IDs. Do not send `before` and `after` together.

## Request examples

Start/reuse one-to-one chat (`POST /chat/conversations`):

```json
{"type":"direct","member_ids":[12]}
```

Create group (`POST /chat/conversations`):

```json
{"type":"group","name":"Sales Team","member_ids":[12,15,19]}
```

The sender is added automatically. Direct chats require exactly one other active user. Group creation is allowed only when `/chat/config` returns `can_create_group: true` (super admins or users with `view_team.lead`). Maximum 49 other members plus sender. Group name: maximum 100 characters. Existing direct chats are reused; the endpoint returns HTTP 201 even when reused.

Send (`POST /chat/conversations/7/messages`):

```json
{"body":"Please follow up on this lead.","client_id":"c680853c-78a8-4a86-aad8-ce58b0f15c32"}
```

Generate a new UUID per new message. Reuse that same UUID when retrying a failed/uncertain send. New messages return HTTP 201; duplicate retries return the existing message with HTTP 200 and do not duplicate notifications. Maximum body length: 5,000 characters; whitespace-only messages are rejected.

For an image, PDF, or voice/audio attachment, send **multipart/form-data** to the same messages endpoint with `client_id`, one uploaded `file`, and optional `body`. Text may be empty when a file is attached. Maximum file size is 10 MB; the file's actual detected type is validated. Supported types include JPEG/PNG/GIF/WebP images, PDF, and WebM/Ogg/MP3/M4A/AAC/WAV audio. Retrying an attachment message should reuse both its UUID and upload. The message response adds:

```json
{"attachment":{"kind":"image","name":"photo.jpg","mime":"image/jpeg","size":12345,"url":"https://zitrackapi.apexelyria.com/api/v1/chat/conversations/7/messages/120/file"}}
```

Text-only messages return `attachment: null`. Private storage paths are never returned. Download/stream using the JWT Authorization header. A bare mobile browser/PDF link will not carry that JWT: fetch the file with authentication into the app's private cache and then display/play it, or use image/audio components that support authenticated request headers. Never append the JWT to the URL. The web CRM includes microphone recording; the React Native developer must use a native recorder for the mobile recording UI and upload its output through this endpoint.

Upload your own profile picture using multipart `profile_pic` at `/chat/profile-picture`. JPEG/PNG/GIF/WebP are supported, up to 2 MB. The response returns `profile_picture_url`; chat config also includes it, and conversation members include `avatar_url`. This uses the same profile picture field as the existing user profile API.

Read (`POST /chat/conversations/7/read`):

```json
{"message_id":120}
```

Read cursors never move backwards. This also marks the user's saved chat notifications through that message as read; unrelated lead alerts remain unchanged. Read and typing responses are `{"status":true}`.

Firebase registration (`POST /store-device-token`):

```json
{"deviceToken":"ACTUAL_FCM_DEVICE_TOKEN"}
```

## Response formats

`GET /chat/config`:

```json
{
  "user_id":12,
  "realtime_enabled":true,
  "key":"PUBLIC_PUSHER_APP_KEY",
  "cluster":"ap2",
  "channel":"private-chat.user.12",
  "event":"chat.updated",
  "poll_interval_ms":15000,
  "can_create_group":false
}
```

Use the returned cluster rather than hardcoding `ap2`. Never embed the Pusher App Secret in mobile code.

Conversation objects from create/show or the conversation list's `data` array:

```json
{
  "id":7,
  "type":"direct",
  "name":"Sales User",
  "members":[
    {"id":12,"name":"Current User","last_read_message_id":120,"last_delivered_message_id":120},
    {"id":15,"name":"Sales User","last_read_message_id":118,"last_delivered_message_id":120}
  ],
  "latest_message":null,
  "unread_count":0
}
```

`latest_message` is null or the message object below. The conversation list supplies calculated `unread_count`; create/show default it to 0, so use the list to refresh actual unread badges.

Message objects from send/history:

```json
{
  "id":120,
  "conversation_id":7,
  "sender_id":12,
  "sender_name":"Current User",
  "client_id":"c680853c-78a8-4a86-aad8-ce58b0f15c32",
  "body":"Please follow up on this lead.",
  "created_at":"2026-10-31T10:00:00+00:00"
}
```

Users/conversations lists use standard Laravel pagination: `data`, `current_page`, `next_page_url`, etc. History returns `{"data":[...],"has_more":false}`. For older pages, move `before` to the returned earliest ID. For catch-up, keep requesting `after` with the latest returned ID until `has_more` is false. Deduplicate messages by `id` when merging HTTP responses/events. Sender ID can be null for a deleted account.

## Real-time integration

1. Fetch `/chat/config` after login.
2. Initialize the official `@pusher/pusher-websocket-react-native` SDK with the returned public `key`, `cluster`, and TLS.
3. Authorize private subscriptions by posting to `/chat/broadcasting/auth` with the existing JWT header:

   ```json
   {"socket_id":"12345.67890","channel_name":"private-chat.user.12"}
   ```

   Return the backend's `{"auth":"..."}` response to the SDK authorizer. Use the full `channel` returned by config; do not add another `private-` prefix.

4. Subscribe to that personal channel and listen for the exact native SDK event name `chat.updated` (no leading dot).
5. Parse event data if it is a JSON string. Handle:

| `kind` | Additional fields | Client action |
| --- | --- | --- |
| `message` | `conversation_id`, `message_id`, `sender_id`, `sender_name`, `preview` | Fetch new messages via `after`; refresh conversations and notification count |
| `read` | `conversation_id`, `user_id`, `message_id` | Refresh conversation member read cursors and notification count |
| `delivered` | `conversation_id`, `user_id`, `message_id` | Refresh conversation members' delivery cursors |
| `typing` | `conversation_id`, `user_id`, `name` | Show typing for other users; clear after a few seconds |
| `conversation` | `conversation_id` | Refresh conversations |

Read receipt rule: an outgoing message is read by a member when their `last_read_message_id >= message.id`. For groups, exclude the sender when counting readers.

Delivery receipt rule: call `/delivered` when the client receives a message (or catches up on incoming messages), including when chat is not actively open. Only call `/read` after the messages are displayed to the user. `/chat/unread` returns `unread_count` and `conversations`, each with `conversation_id`, `unread_count`, `incoming_message_id`, and `last_delivered_message_id`. Acknowledge `incoming_message_id` when it exceeds that member's delivered cursor. Both delivery/read cursors only move forward. Reading implies delivery. For WhatsApp-style ticks: one gray tick = saved/sent; two gray ticks = all other members delivered; two blue ticks = all other members read. Old mobile builds that do not acknowledge delivery cannot produce received ticks until they mark messages read.

On reconnect or app foreground, fetch missed messages using `after` and refresh conversations. Use the returned polling interval as a fallback when live delivery is unavailable. Disconnect/unsubscribe on logout. A WebSocket subscription does not replace Firebase for background push.

## Saved and push notifications

Every new message saves a `CHAT_MESSAGE` notification for each other active member, whether or not they have a device token. `/notifications` returns:

```json
{
  "status":true,
  "unread_count":1,
  "data":[{
    "id":91,
    "title":"New message from Current User",
    "message":"Please follow up on this lead.",
    "type":"CHAT_MESSAGE",
    "data":{"conversationId":7,"messageId":120,"senderId":12},
    "is_read":0
  }]
}
```

The actual notification response also includes `image` and `time`. For notification taps, navigate to the conversation identified by `data.conversationId`. Marking only the notification read does not mark chat messages read; call the chat `/read` endpoint after displaying messages.

Firebase push payloads use `type: CHAT_MESSAGE`, `conversationId`, and `messageId` (IDs are strings in FCM data). Register/update the Firebase token and request OS notification permission. Handle foreground messages and notification taps/cold launches in the app. Automatic notification-tap deep linking is not included in the existing mobile screen.

## Errors and limits

Chat endpoints use HTTP 401 for missing/expired login, 403 for inactive/nonmember/unauthorized accounts, 404 for missing conversations, 422 for invalid input, and 429 for throttling. Subscription auth returns 503 when Pusher is not configured. Inspect JSON `message` and optional `errors`; some older notification endpoints instead return `status: false`.

Write limits per minute: create conversations 30; send messages 60; typing 60; read updates 120; subscription auth 120. Reads/history require conversation membership; even super admins cannot access private conversations they have not joined.

Existing mobile implementation: `src/api/chatApi.ts`, `src/services/chatRealtime.ts`, and `src/screens/chat/TeamChatScreen.tsx`. Rebuild the native app after installing the Pusher SDK; validate web-to-mobile/mobile-to-web delivery, read receipts, and Firebase delivery on physical devices.
