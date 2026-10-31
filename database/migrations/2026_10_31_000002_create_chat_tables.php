<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_conversations')) {
            Schema::create('chat_conversations', function (Blueprint $table) {
                $table->id();
                $table->string('type', 10);
                $table->string('name')->nullable();
                $table->string('direct_key')->nullable()->unique();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('chat_members')) {
            Schema::create('chat_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('last_read_message_id')->default(0);
                $table->timestamps();
                $table->unique(['conversation_id', 'user_id']);
                $table->index(['user_id', 'conversation_id']);
            });
        }
        if (! Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
                $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
                $table->uuid('client_id');
                $table->text('body');
                $table->timestamps();
                $table->unique(['conversation_id', 'sender_id', 'client_id'], 'chat_message_idempotency');
                $table->index(['conversation_id', 'id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_members');
        Schema::dropIfExists('chat_conversations');
    }
};
