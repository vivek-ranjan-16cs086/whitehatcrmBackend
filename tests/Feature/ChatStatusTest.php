<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class ChatStatusTest extends TestCase
{
    public function test_status_reports_disabled_realtime_without_printing_credentials(): void
    {
        config(['broadcasting.default' => 'log', 'broadcasting.connections.pusher.secret' => 'never-print-this']);
        $this->artisan('chat:status')->expectsOutputToContain('Pusher delivery is disabled')
            ->doesntExpectOutputToContain('never-print-this')->assertFailed();
    }

    public function test_probe_uses_a_read_only_pusher_request(): void
    {
        config(['broadcasting.default' => 'pusher', 'broadcasting.connections.pusher.app_id' => '123',
            'broadcasting.connections.pusher.key' => 'test-key', 'broadcasting.connections.pusher.secret' => 'never-print-this']);
        Broadcast::shouldReceive('connection->getPusher->get')->with('/channels')->once()->andReturn((object) ['channels' => []]);
        $this->artisan('chat:status', ['--probe' => true])->expectsOutputToContain('Pusher HTTPS credentials and connectivity: OK.')
            ->doesntExpectOutputToContain('never-print-this')->assertSuccessful();
    }
}
