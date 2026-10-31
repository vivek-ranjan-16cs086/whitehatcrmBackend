<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('lead_timeline_events')) {
            Schema::create('lead_timeline_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->constrained()->cascadeOnDelete();

                $table->foreignId('user_id')->nullable();
                $table->string('user_name')->nullable();
                $table->string('user_role')->nullable();

                $table->string('event_type'); // COMMENT | FOLLOW_UP | CALL | TRANSFER

                $table->text('message')->nullable();

                // Follow-up
                $table->dateTime('follow_up_date')->nullable();

                // Call
                $table->string('call_outcome')->nullable();
                $table->string('phone')->nullable();

                // Assignment
                $table->foreignId('assigned_to_user_id')->nullable();
                $table->unsignedBigInteger('assignment_context_id')->nullable();

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_timeline_events');
    }
};
