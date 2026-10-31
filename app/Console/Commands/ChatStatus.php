<?php

namespace App\Console\Commands;

use App\Services\ChatRealtimeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Broadcast;

class ChatStatus extends Command
{
    protected $signature = 'chat:status {--probe : Verify Pusher credentials with a read-only HTTPS request}';

    protected $description = 'Check chat delivery settings without printing credentials or sending messages';

    public function handle(ChatRealtimeService $realtime): int
    {
        $present = fn ($value) => filled($value) ? 'Present' : 'Missing';
        $this->table(['Check', 'Status'], [
            ['Broadcast connection', config('broadcasting.default')],
            ['Pusher App ID', $present(config('broadcasting.connections.pusher.app_id'))],
            ['Pusher App Key', $present(config('broadcasting.connections.pusher.key'))],
            ['Pusher App Secret', $present(config('broadcasting.connections.pusher.secret'))],
            ['Pusher cluster', config('broadcasting.connections.pusher.options.cluster') ?: 'Missing'],
            ['Pusher PHP SDK', class_exists(\Pusher\Pusher::class) ? 'Installed' : 'Missing'],
            ['PHP cURL', extension_loaded('curl') ? 'Enabled' : 'Missing'],
            ['Firebase credentials file', is_file(storage_path('app/firebase/firebase-service-account.json')) ? 'Present' : 'Missing'],
        ]);

        if (! $realtime->enabled()) {
            $this->error('Pusher delivery is disabled. Set BROADCAST_CONNECTION=pusher and all Pusher credentials in the server .env, then run php artisan config:cache.');

            return self::FAILURE;
        }
        if (! class_exists(\Pusher\Pusher::class) || ! extension_loaded('curl')) {
            $this->error('Install Composer dependencies and enable the PHP cURL extension.');

            return self::FAILURE;
        }
        if ($this->option('probe')) {
            try {
                // Read-only credentials/connectivity check. Never broadcast a test message.
                Broadcast::connection('pusher')->getPusher()->get('/channels');
                $this->info('Pusher HTTPS credentials and connectivity: OK.');
            } catch (\Throwable $exception) {
                $this->error('Pusher HTTPS check failed. Check the app credentials, cluster and outbound HTTPS access.');
                $this->line('Failure type: '.class_basename($exception));

                return self::FAILURE;
            }
        }
        $this->info('Pusher is configured. The receiving browser must show Live connection, not Polling or Reconnecting.');
        $this->line('For mobile push, the recipient also needs a registered Firebase device token and notification permission.');

        return self::SUCCESS;
    }
}
