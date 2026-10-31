<?php

namespace Tests\Feature;

use App\Events\ChatUpdated;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['enable' => true]);
    }

    private function direct(User $sender, User $recipient): int
    {
        return $this->actingAs($sender)->postJson('/admin/chat/conversations', [
            'type' => 'direct', 'member_ids' => [$recipient->id],
        ])->assertCreated()->json('id');
    }

    public function test_chat_requires_login_and_an_active_account(): void
    {
        $this->getJson('/admin/chat/config')->assertUnauthorized();
        $this->getJson('/api/v1/chat/config')->assertUnauthorized();
        $user = $this->user();
        $this->actingAs($user)->get('/admin/chat')->assertOk()->assertSee('Conversations');
        $user->update(['enable' => false]);
        $this->getJson('/admin/chat/config')->assertForbidden();
    }

    public function test_direct_chat_is_reused_and_message_retries_do_not_duplicate_messages(): void
    {
        $sender = $this->user();
        $recipient = $this->user();
        $id = $this->direct($sender, $recipient);
        $this->assertSame($id, $this->direct($recipient, $sender));
        $body = ['body' => 'Hello <script>alert(1)</script>', 'client_id' => (string) Str::uuid()];
        $message = $this->actingAs($sender)->postJson("/admin/chat/conversations/$id/messages", $body)
            ->assertCreated()->assertJsonPath('body', $body['body'])->json('id');
        $this->postJson("/admin/chat/conversations/$id/messages", $body)->assertOk()->assertJsonPath('id', $message);
        $this->assertDatabaseCount('chat_messages', 1);
        $this->actingAs($recipient)->getJson('/admin/chat/conversations')->assertOk()->assertJsonPath('data.0.unread_count', 1);
        $this->postJson("/admin/chat/conversations/$id/read", ['message_id' => $message])->assertOk();
        $this->getJson('/admin/chat/conversations')->assertJsonPath('data.0.unread_count', 0);
    }

    public function test_nonmembers_cannot_read_send_type_or_mark_read_even_if_they_are_admin(): void
    {
        $id = $this->direct($this->user(), $this->user());
        $outsider = $this->user();
        $outsider->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        $this->actingAs($outsider)->getJson("/admin/chat/conversations/$id/messages")->assertForbidden();
        foreach (['messages', 'read', 'typing'] as $action) {
            $this->postJson("/admin/chat/conversations/$id/$action", ['body' => 'Not allowed', 'client_id' => (string) Str::uuid(), 'message_id' => 1])->assertForbidden();
        }
        $this->getJson('/admin/chat/conversations')->assertJsonCount(0, 'data');
    }

    public function test_only_managers_can_create_group_chats_and_disabled_users_cannot_be_added(): void
    {
        $sender = $this->user();
        $recipient = $this->user();
        $this->actingAs($sender)->postJson('/admin/chat/conversations', ['type' => 'group', 'name' => 'Sales', 'member_ids' => [$recipient->id]])->assertForbidden();
        $sender->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        $this->postJson('/admin/chat/conversations', ['type' => 'group', 'name' => 'Sales', 'member_ids' => [$recipient->id]])->assertCreated()->assertJsonCount(2, 'members');
        $recipient->update(['enable' => false]);
        $this->postJson('/admin/chat/conversations', ['type' => 'direct', 'member_ids' => [$recipient->id]])->assertUnprocessable();
    }

    public function test_mobile_jwt_guard_uses_the_same_chat_and_read_cursor_never_moves_backwards(): void
    {
        $sender = $this->user();
        $recipient = $this->user();
        $id = $this->direct($sender, $recipient);
        $messages = collect(range(1, 2))->map(fn ($i) => ChatMessage::create(['conversation_id' => $id, 'sender_id' => $sender->id, 'client_id' => Str::uuid(), 'body' => 'Message '.$i]));
        $this->actingAs($recipient, 'api')->getJson("/api/v1/chat/conversations/$id/messages")->assertOk()->assertJsonCount(2, 'data');
        foreach ([$messages->last()->id, $messages->first()->id] as $cursor) {
            $this->postJson("/api/v1/chat/conversations/$id/read", ['message_id' => $cursor])->assertOk();
        }
        $this->assertDatabaseHas('chat_members', ['conversation_id' => $id, 'user_id' => $recipient->id, 'last_read_message_id' => $messages->last()->id]);
    }

    public function test_message_history_supports_older_pages_and_incremental_catchup(): void
    {
        $sender = $this->user();
        $id = $this->direct($sender, $this->user());
        $this->actingAs($sender);
        for ($i = 1; $i <= 105; $i++) {
            ChatMessage::create(['conversation_id' => $id, 'sender_id' => $sender->id, 'client_id' => Str::uuid(), 'body' => 'Message '.$i]);
        }
        $result = $this->getJson("/admin/chat/conversations/$id/messages")->assertOk()->assertJsonCount(100, 'data')->assertJsonPath('has_more', true);
        $first = $result->json('data.0.id');
        $this->getJson("/admin/chat/conversations/$id/messages?before=$first")->assertOk()->assertJsonCount(5, 'data');
        $this->getJson("/admin/chat/conversations/$id/messages?after=0")->assertJsonPath('has_more', true)->assertJsonPath('data.0.body', 'Message 1');
    }

    public function test_private_channel_authorization_and_broadcast_payload_are_limited_to_members(): void
    {
        config(['broadcasting.default' => 'pusher', 'broadcasting.connections.pusher.app_id' => '123',
            'broadcasting.connections.pusher.key' => 'test-key', 'broadcasting.connections.pusher.secret' => 'test-secret']);
        Broadcast::purge();
        require base_path('routes/channels.php');
        Event::fake([ChatUpdated::class]);
        $sender = $this->user();
        $recipient = $this->user();
        $outsider = $this->user();
        $id = $this->direct($sender, $recipient);
        $this->actingAs($sender)->postJson('/admin/chat/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-chat.user.'.$sender->id])->assertOk()->assertJsonStructure(['auth']);
        $this->postJson('/admin/chat/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-chat.user.'.$outsider->id])->assertForbidden();
        $this->postJson("/admin/chat/conversations/$id/messages", ['body' => 'Private', 'client_id' => Str::uuid()])->assertCreated();
        Event::assertDispatched(ChatUpdated::class, fn ($event) => $event->payload['kind'] === 'message'
            && count($event->recipients) === 2 && ! in_array($outsider->id, $event->recipients));
        $this->getJson('/admin/chat/config')->assertOk()->assertJsonMissing(['secret' => 'test-secret']);
        $this->actingAs($recipient, 'api')->postJson('/api/v1/chat/broadcasting/auth', [
            'socket_id' => '123.789', 'channel_name' => 'private-chat.user.'.$recipient->id,
        ])->assertOk()->assertJsonStructure(['auth']);
        $this->postJson('/api/v1/chat/broadcasting/auth', [
            'socket_id' => '123.789', 'channel_name' => 'private-chat.user.'.$sender->id,
        ])->assertForbidden();
    }

    public function test_provider_failure_does_not_discard_a_saved_message(): void
    {
        $sender = $this->user();
        $id = $this->direct($sender, $this->user());
        config(['broadcasting.default' => 'pusher', 'broadcasting.connections.pusher.app_id' => '123',
            'broadcasting.connections.pusher.key' => 'test-key', 'broadcasting.connections.pusher.secret' => 'test-secret']);
        Broadcast::shouldReceive('queue')->once()->andThrow(new \RuntimeException('Provider offline'));
        $this->actingAs($sender)->postJson("/admin/chat/conversations/$id/messages", [
            'body' => 'Available through polling', 'client_id' => Str::uuid(),
        ])->assertCreated();
        $this->assertDatabaseHas('chat_messages', ['conversation_id' => $id, 'body' => 'Available through polling']);
        $this->getJson("/admin/chat/conversations/$id/messages")->assertOk()
            ->assertJsonPath('data.0.body', 'Available through polling');
    }
}
