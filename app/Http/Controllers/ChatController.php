<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\ChatRealtimeService;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    public function conversations(Request $request)
    {
        $user = $this->active($request);
        $request->validate(['page' => 'nullable|integer|min:1']);
        $conversations = ChatConversation::whereHas('members', fn ($query) => $query->whereKey($user->id))
            ->with(['members:id,name,enable', 'latestMessage.sender:id,name'])
            ->withCount(['messages as unread_count' => fn ($query) => $query
                ->where('sender_id', '!=', $user->id)
                ->where('chat_messages.id', '>', function ($sub) use ($user) {
                    $sub->select('last_read_message_id')->from('chat_members')
                        ->whereColumn('conversation_id', 'chat_conversations.id')->where('user_id', $user->id)->limit(1);
                })])
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(50);

        return response()->json($conversations->through(fn ($conversation) => $this->conversationData($conversation, $user)));
    }

    private function conversationData(ChatConversation $conversation, User $user): array
    {
        $conversation->loadMissing(['members:id,name,enable', 'latestMessage.sender:id,name']);

        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'name' => $conversation->type === 'direct'
                ? ($conversation->members->firstWhere('id', '!=', $user->id)?->name ?? 'Former user')
                : $conversation->name,
            'members' => $conversation->members->map(fn ($member) => [
                'id' => $member->id, 'name' => $member->name,
                'last_read_message_id' => (int) $member->pivot->last_read_message_id,
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
        $data = $request->validate(['body' => 'required|string|max:5000', 'client_id' => 'required|uuid']);
        abort_if(trim($data['body']) === '', 422, 'Write a message first.');
        $message = DB::transaction(function () use ($conversation, $user, $data) {
            $message = $conversation->messages()->firstOrCreate([
                'sender_id' => $user->id, 'client_id' => $data['client_id'],
            ], ['body' => trim($data['body'])]);
            if ($message->wasRecentlyCreated) {
                $conversation->touch();
            }

            return $message;
        });
        if ($message->wasRecentlyCreated) {
            $this->realtime->notify($conversation, 'message', ['message_id' => $message->id]);
            // Send through the existing Firebase setup only when a recipient has a device token.
            foreach ($conversation->members()->where('enable', true)->whereKeyNot($user->id)->whereNotNull('device_token')->get() as $recipient) {
                if (! $recipient->device_token) {
                    continue;
                }
                try {
                    app(FirebaseNotificationService::class)->sendToDevice($recipient->device_token,
                        $user->name, \Illuminate\Support\Str::limit($message->body, 120),
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
        $updated = DB::table('chat_members')->where('conversation_id', $conversation->id)->where('user_id', $user->id)
            ->where('last_read_message_id', '<', $data['message_id'])->update(['last_read_message_id' => $data['message_id'], 'updated_at' => now()]);
        if ($updated) {
            $this->realtime->notify($conversation, 'read', ['user_id' => $user->id, 'message_id' => (int) $data['message_id']]);
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
