<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private function conversation(User $sender, User $recipient): int
    {
        return $this->actingAs($sender)->postJson('/admin/chat/conversations', [
            'type' => 'direct', 'member_ids' => [$recipient->id],
        ])->assertCreated()->json('id');
    }

    public function test_images_pdf_and_audio_can_be_shared_privately_without_message_text(): void
    {
        Storage::fake('local');
        $sender = User::factory()->create(['enable' => true]);
        $recipient = User::factory()->create(['enable' => true]);
        $outsider = User::factory()->create(['enable' => true]);
        $id = $this->conversation($sender, $recipient);
        $wav = 'RIFF'.pack('V', 44).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16).'data'.pack('V', 8).str_repeat("\0", 8);
        foreach ([
            ['image', UploadedFile::fake()->image('photo.jpg')],
            ['pdf', UploadedFile::fake()->createWithContent('document.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF")],
            ['audio', UploadedFile::fake()->createWithContent('voice.wav', $wav)],
        ] as [$kind, $file]) {
            $response = $this->actingAs($sender)->post('/admin/chat/conversations/'.$id.'/messages', [
                'client_id' => (string) Str::uuid(), 'file' => $file,
            ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('attachment.kind', $kind);
            $message = ChatMessage::findOrFail($response->json('id'));
            Storage::disk('local')->assertExists($message->attachment['path']);
            $this->assertArrayNotHasKey('path', $response->json('attachment'));
            $this->actingAs($recipient)->get($response->json('attachment.url'))->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->actingAs($outsider)->getJson($response->json('attachment.url'))->assertForbidden();
        }
        $this->assertDatabaseCount('notifications', 3);
    }

    public function test_attachment_retry_reuses_existing_message_file_and_notification(): void
    {
        Storage::fake('local');
        $sender = User::factory()->create(['enable' => true]);
        $id = $this->conversation($sender, User::factory()->create(['enable' => true]));
        $client = (string) Str::uuid();
        foreach ([201, 200] as $status) {
            $this->actingAs($sender)->post('/admin/chat/conversations/'.$id.'/messages', [
                'client_id' => $client, 'file' => UploadedFile::fake()->image('photo.png'),
            ], ['Accept' => 'application/json'])->assertStatus($status);
        }
        $this->assertCount(1, Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('chat_messages', 1);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_executable_and_oversized_attachments_are_rejected(): void
    {
        Storage::fake('local');
        $sender = User::factory()->create(['enable' => true]);
        $id = $this->conversation($sender, User::factory()->create(['enable' => true]));
        foreach ([UploadedFile::fake()->createWithContent('script.php', '<?php echo "bad";'),
            UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')] as $file) {
            $this->post('/admin/chat/conversations/'.$id.'/messages', ['client_id' => (string) Str::uuid(), 'file' => $file], ['Accept' => 'application/json'])
                ->assertUnprocessable()->assertJsonValidationErrors('file');
        }
        $this->assertCount(0, Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_profile_picture_upload_updates_only_the_authenticated_user(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['enable' => true]);
        $other = User::factory()->create(['enable' => true]);
        $this->actingAs($user)->post('/admin/chat/profile-picture', [
            'profile_pic' => UploadedFile::fake()->image('avatar.png'),
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonStructure(['profile_picture_url']);
        Storage::disk('public')->assertExists($user->fresh()->profile_pic);
        $this->assertNull($other->fresh()->profile_pic);
        $this->getJson('/admin/chat/config')->assertJsonPath('profile_picture_url', Storage::disk('public')->url($user->fresh()->profile_pic));
    }
}
