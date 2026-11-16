<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_devices')) {
            return;
        }

        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_id', 191);
            $table->string('platform', 20);
            $table->text('fcm_token');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'device_id']);
        });
        // Keep existing registrations during the client rollout.
        if (Schema::hasColumn('users', 'device_token')) {
            DB::table('users')->whereNotNull('device_token')->orderBy('id')->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    if ($user->device_token === '') {
                        continue;
                    }
                    $hash = hash('sha256', $user->device_token);
                    DB::table('user_devices')->updateOrInsert(['token_hash' => $hash], [
                        'user_id' => $user->id, 'device_id' => 'legacy-'.$hash,
                        'platform' => 'unknown', 'fcm_token' => $user->device_token,
                        'last_seen_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
