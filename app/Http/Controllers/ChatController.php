<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Notification;
use App\Models\User;
use App\Services\ChatRealtimeService;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{
    public function __construct(private ChatRealtimeService $realtime) {}

    private function active(Request $request): User
    {
        abort_unless($request->user()?->enable, 403, 'Your account is inactive.');

        return $request->user();
    }

    private function member(Request $request, ChatConversation $conversation): User
    {
        $user = $this->active($request);
        abort_unless($conversation->members()->whereKey($user->id)->exists(), 403);

        return $user;
    }

    private function canCreateGroup(User $user): bool
    {
        return $user->hasRole('super-admin', 'web') || $user->canViewAllLeads();
    }

    public function page(Request $request)
    {
        $this->active($request);

        return view('admin.chat.index');
    }

    public function configuration(Request $request)
    {
        $user = $this->active($request);

        return response()->json([
            'user_id' => $user->id,
            'realtime_enabled' => $this->realtime->enabled(),
            'key' => config('broadcasting.connections.pusher.key'),
            'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            'channel' => 'private-chat.user.'.$user->id,
            'event' => 'chat.updated',
            'poll_interval_ms' => 15000,
            'can_create_group' => $this->canCreateGroup($user),
            'profile_picture_url' => $user->profile_pic ? Storage::disk('public')->url($user->profile_pic) : null,
            'max_attachment_bytes' => 10 * 1024 * 1024,
        ]);
    }

    public function users(Request $request)
    {
        $user = $this->active($request);
        $request->validate(['search' => 'nullable|string|max:100', 'page' => 'nullable|integer|min:1']);

        return response()->json(User::query()->where('enable', true)->whereKeyNot($user->id)
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')->paginate(100, ['id', 'name']));
    }

    public function profilePicture(Request $request)
    {
        $user = $this->active($request);
        $request->validate(['profile_pic' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:2048']);
        $path = $request->file('profile_pic')->store('profile_pics', 'public');
        abort_unless($path, 503, 'Unable to store the profile picture.');
        $old = $user->profile_pic;
        try {
            $user->update(['profile_pic' => $path]);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
        if ($old && str_starts_with($old, 'profile_pics/') && ! User::where('profile_pic', $old)->exists()) {
            Storage::disk('public')->delete($old);
        }

        return response()->json(['profile_picture_url' => Storage::disk('public')->url($path)]);
    }

    public function file(Request $request, ChatConversation $conversation, ChatMessage $message)
    {
        $this->member($request, $conversation);
        abort_unless($message->conversation_id === $conversation->id && $message->attachment, 404);
        $attachment = $message->attachment;
        abort_unless(Storage::disk('local')->exists($attachment['path']), 404);
        $headers = ['Content-Type' => $attachment['mime'], 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];

        return Storage::disk('local')->response($attachment['path'], $attachment['name'], $headers,
            $attachment['kind'] === 'pdf' ? 'attachment' : 'inline');
    }

    public function conversations(Request $request)
    {
        $user = $this->active($request);
        $request->validate(['page' => 'nullable|integer|min:1']);
        $conversations = ChatConversation::whereHas('members', fn ($query) => $query->whereKey($user->id))
            ->with(['members:id,name,enable,profile_pic', 'latestMessage.sender:id,name'])
            ->withCount(['messages as unread_count' => fn ($query) => $query
                ->where('sender_id', '!=', $user->id)
                ->where('chat_messages.id', '>', function ($sub) use ($user) {
                    $sub->select('last_read_message_id')->from('chat_members')
                        ->whereColumn('conversation_id', 'chat_conversations.id')->where('user_id', $user->id)->limit(1);
                })])
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(50);

        return response()->json($conversations->through(fn ($conversation) => $this->conversationData($conversation, $user)));
    }

    public function show(Request $request, ChatConversation $conversation)
    {
        $user = $this->member($request, $conversation);

        return response()->json($this->conversationData($conversation, $user));
    }

    public function unread(Request $request)
    {
        $user = $this->active($request);
        $rows = DB::table('chat_members as member')
            ->leftJoin('chat_messages as message', function ($join) use ($user) {
                $join->on('message.conversation_id', '=', 'member.conversation_id')->where('message.sender_id', '!=', $user->id);
            })->where('member.user_id', $user->id)
            ->select('member.conversation_id', 'member.last_delivered_message_id')
            ->selectRaw('COALESCE(MAX(message.id), 0) as incoming_message_id')
            ->selectRaw('SUM(CASE WHEN message.id > member.last_read_message_id THEN 1 ELSE 0 END) as unread_count')
            ->groupBy('member.conversation_id', 'member.last_delivered_message_id')->get()
            ->map(fn ($row) => [
                'conversation_id' => (int) $row->conversation_id, 'unread_count' => (int) $row->unread_count,
                'incoming_message_id' => (int) $row->incoming_message_id,
                'last_delivered_message_id' => (int) $row->last_delivered_message_id,
            ]);

        return response()->json(['unread_count' => $rows->sum('unread_count'), 'conversations' => $rows]);
    }

    private function conversationData(ChatConversation $conversation, User $user): array
    {
        $conversation->loadMissing(['members:id,name,enable,profile_pic', 'latestMessage.sender:id,name']);

        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'name' => $conversation->type === 'direct'
                ? ($conversation->members->firstWhere('id', '!=', $user->id)?->name ?? 'Former user')
                : $conversation->name,
            'members' => $conversation->members->map(fn ($member) => [
                'id' => $member->id, 'name' => $member->name,
                'avatar_url' => $member->profile_pic ? Storage::disk('public')->url($member->profile_pic) : null,
                'last_read_message_id' => (int) $member->pivot->last_read_message_id,
                'last_delivered_message_id' => max((int) $member->pivot->last_delivered_message_id, (int) $member->pivot->last_read_message_id),
            ])->values(),
            'latest_message' => $conversation->latestMessage ? $this->messageData($conversation->latestMessage) : null,
            'unread_count' => (int) ($conversation->unread_count ?? 0),
        ];
    }

    private function messageData(ChatMessage $message): array
    {
        $message->loadMissing('sender:id,name');

        return [
            'id' => $message->id, 'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id, 'sender_name' => $message->sender?->name ?? 'Former user',
            'client_id' => $message->client_id, 'body' => $message->body,
            'attachment' => $message->attachment ? array_merge(
                \Illuminate\Support\Arr::except($message->attachment, ['path']),
                ['url' => url((request()->is('api/*') ? 'api/v1/chat' : 'admin/chat').'/conversations/'.$message->conversation_id.'/messages/'.$message->id.'/file')]
            ) : null,
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }

    public function create(Request $request)
    {
        $user = $this->active($request);
        $data = $request->validate([
            'type' => 'required|in:direct,group', 'name' => 'nullable|string|max:100',
            'member_ids' => 'required|array|min:1|max:49',
            'member_ids.*' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')->where('enable', true)],
        ]);
        $ids = collect($data['member_ids'])->map(fn ($id) => (int) $id)->reject(fn ($id) => $id === $user->id)->values();
        abort_if($ids->isEmpty(), 422, 'Choose another team member.');
        if ($data['type'] === 'direct') {
            abort_unless($ids->count() === 1, 422, 'Direct chats need exactly one recipient.');
        } else {
            abort_unless($this->canCreateGroup($user), 403, 'Only team managers can create groups.');
            abort_if(blank($data['name'] ?? null), 422, 'Enter a group name.');
        }
        $conversation = DB::transaction(function () use ($data, $user, $ids) {
            $members = $ids->push($user->id)->sort()->values()->all();
            $conversation = $data['type'] === 'direct'
                ? ChatConversation::firstOrCreate(['direct_key' => implode(':', $members)], ['type' => 'direct', 'created_by' => $user->id])
                : ChatConversation::create(['type' => 'group', 'name' => trim($data['name']), 'created_by' => $user->id]);
            $conversation->members()->syncWithoutDetaching($members);

            return $conversation;
        });
        $this->realtime->notify($conversation, 'conversation');

        return response()->json($this->conversationData($conversation, $user), 201);
    }

    public function messages(Request $request, ChatConversation $conversation)
    {
        $this->member($request, $conversation);
        $data = $request->validate(['before' => 'nullable|integer|min:1', 'after' => 'nullable|integer|min:0']);
        abort_if(isset($data['before'], $data['after']), 422, 'Use either before or after, not both.');
        $query = $conversation->messages()->with('sender:id,name');
        if (isset($data['after'])) {
            $query->where('id', '>', $data['after'])->orderBy('id');
        } else {
            $query->when(isset($data['before']), fn ($q) => $q->where('id', '<', $data['before']))->orderByDesc('id');
        }
        $messages = $query->limit(101)->get();
        $more = $messages->count() > 100;

        return response()->json([
            'data' => $messages->take(100)->sortBy('id')->values()->map(fn ($message) => $this->messageData($message)),
            'has_more' => $more,
        ]);
    }

    public function send(Request $request, ChatConversation $conversation)
    {
        $user = $this->member($request, $conversation);
        $data = $request->validate([
            'body' => 'nullable|required_without:file|string|max:5000', 'client_id' => 'required|uuid',
            'file' => 'nullable|file|max:10240|mimetypes:image/jpeg,image/png,image/gif,image/webp,application/pdf,audio/webm,video/webm,audio/ogg,application/ogg,audio/mpeg,audio/mp4,audio/x-m4a,audio/aac,audio/wav,audio/x-wav',
        ]);
        abort_if(trim($data['body'] ?? '') === '' && ! $request->hasFile('file'), 422, 'Write a message or attach a file.');
        $storedPath = null;
        try {
            $message = DB::transaction(function () use ($conversation, $user, $data, $request, &$storedPath) {
                ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
                $existing = $conversation->messages()->where('sender_id', $user->id)->where('client_id', $data['client_id'])->first();
                if ($existing) {
                    return $existing;
                }
                $attachment = null;
                if ($request->hasFile('file')) {
                    $file = $request->file('file');
                    $storedPath = $file->store('chat/attachments', 'local');
                    abort_unless($storedPath, 503, 'Unable to store this attachment.');
                    $mime = $file->getMimeType();
                    $attachment = ['path' => $storedPath, 'name' => \Illuminate\Support\Str::limit(preg_replace('/[\x00-\x1F\x7F]/u', '', basename($file->getClientOriginalName())), 180, ''),
                        'mime' => $mime, 'size' => $file->getSize(), 'kind' => str_starts_with($mime, 'image/') ? 'image' : ($mime === 'application/pdf' ? 'pdf' : 'audio')];
                }
                $message = $conversation->messages()->create([
                    'sender_id' => $user->id, 'client_id' => $data['client_id'],
                    'body' => trim($data['body'] ?? ''), 'attachment' => $attachment,
                ]);
                if ($message->wasRecentlyCreated) {
                    $conversation->touch();
                    foreach ($conversation->members()->where('enable', true)->whereKeyNot($user->id)->pluck('users.id') as $recipientId) {
                        Notification::create([
                            'user_id' => $recipientId,
                            'title' => \Illuminate\Support\Str::limit('New message from '.$user->name, 250),
                            'message' => \Illuminate\Support\Str::limit($message->body ?: 'Shared '.$message->attachment['kind'].': '.$message->attachment['name'], 120),
                            'type' => 'CHAT_MESSAGE',
                            'data' => ['conversationId' => $conversation->id, 'messageId' => $message->id, 'senderId' => $user->id],
                            'is_read' => false,
                        ]);
                    }
                }

                return $message;
            });
        } catch (\Throwable $exception) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }
            throw $exception;
        }
        if ($message->wasRecentlyCreated) {
            $this->realtime->notify($conversation, 'message', [
                'message_id' => $message->id, 'sender_id' => $user->id,
                'sender_name' => $user->name,
                'preview' => \Illuminate\Support\Str::limit($message->body ?: 'Shared '.$message->attachment['kind'].': '.$message->attachment['name'], 120),
            ]);
            // Send through the existing Firebase setup only when a recipient has a device token.
            foreach ($conversation->members()->where('enable', true)->whereKeyNot($user->id)->whereNotNull('device_token')->get() as $recipient) {
                if (! $recipient->device_token) {
                    continue;
                }
                try {
                    app(FirebaseNotificationService::class)->sendToDevice($recipient->device_token,
                        $user->name, \Illuminate\Support\Str::limit($message->body ?: 'Shared '.$message->attachment['kind'], 120),
                        ['type' => 'CHAT_MESSAGE', 'conversationId' => (string) $conversation->id, 'messageId' => (string) $message->id]);
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }
        }

        return response()->json($this->messageData($message), $message->wasRecentlyCreated ? 201 : 200);
    }

    public function read(Request $request, ChatConversation $conversation)
    {
        $user = $this->member($request, $conversation);
        $data = $request->validate(['message_id' => ['required', 'integer', Rule::exists('chat_messages', 'id')->where('conversation_id', $conversation->id)]]);
        DB::table('chat_members')->where('conversation_id', $conversation->id)->where('user_id', $user->id)
            ->where('last_delivered_message_id', '<', $data['message_id'])->update(['last_delivered_message_id' => $data['message_id']]);
        $updated = DB::table('chat_members')->where('conversation_id', $conversation->id)->where('user_id', $user->id)
            ->where('last_read_message_id', '<', $data['message_id'])->update(['last_read_message_id' => $data['message_id'], 'updated_at' => now()]);
        $notificationsRead = Notification::where('user_id', $user->id)->where('type', 'CHAT_MESSAGE')
            ->where('data->conversationId', $conversation->id)->where('data->messageId', '<=', (int) $data['message_id'])
            ->where('is_read', false)->update(['is_read' => true]);
        if ($updated || $notificationsRead) {
            $this->realtime->notify($conversation, 'read', ['user_id' => $user->id, 'message_id' => (int) $data['message_id']]);
        }

        return response()->json(['status' => true]);
    }

    public function delivered(Request $request, ChatConversation $conversation)
    {
        $user = $this->member($request, $conversation);
        $data = $request->validate(['message_id' => ['required', 'integer', Rule::exists('chat_messages', 'id')->where('conversation_id', $conversation->id)]]);
        $updated = DB::table('chat_members')->where('conversation_id', $conversation->id)->where('user_id', $user->id)
            ->where('last_delivered_message_id', '<', $data['message_id'])
            ->update(['last_delivered_message_id' => $data['message_id'], 'updated_at' => now()]);
        if ($updated) {
            $this->realtime->notify($conversation, 'delivered', ['user_id' => $user->id, 'message_id' => (int) $data['message_id']]);
        }

        return response()->json(['status' => true]);
    }

    public function typing(Request $request, ChatConversation $conversation)
    {
        $user = $this->member($request, $conversation);
        if (Cache::add('chat:typing:'.$conversation->id.':'.$user->id, true, 3)) {
            $this->realtime->notify($conversation, 'typing', ['user_id' => $user->id, 'name' => $user->name]);
        }

        return response()->json(['status' => true]);
    }
}
