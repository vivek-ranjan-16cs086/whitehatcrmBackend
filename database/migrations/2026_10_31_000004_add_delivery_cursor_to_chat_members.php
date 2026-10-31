<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('chat_members', 'last_delivered_message_id')) {
            Schema::table('chat_members', function (Blueprint $table) {
                $table->unsignedBigInteger('last_delivered_message_id')->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('chat_members', 'last_delivered_message_id')) {
            Schema::table('chat_members', fn (Blueprint $table) => $table->dropColumn('last_delivered_message_id'));
        }
    }
};
