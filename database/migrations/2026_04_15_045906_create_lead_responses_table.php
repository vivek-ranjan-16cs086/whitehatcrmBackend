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
        if (! Schema::hasTable('lead_responses')) {
            Schema::create('lead_responses', function (Blueprint $table) {
                $table->id();

                // Lead reference
                $table->foreignId('lead_id')
                    ->constrained()
                    ->onDelete('cascade');

                // User who added the note
                $table->foreignId('user_id')
                    ->constrained()
                    ->onDelete('cascade');

                // Note content
                $table->text('note');
                $table->text('call_recording')->nullable();

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_responses');
    }
};
