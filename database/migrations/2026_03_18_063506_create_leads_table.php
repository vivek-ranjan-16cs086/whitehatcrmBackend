<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads')) {
            Schema::create('leads', function (Blueprint $table) {
                $table->id();

                $table->string('name');
                $table->string('phone', 20)->nullable();
                $table->string('email')->nullable();
                $table->text('message')->nullable();

                $table->string('source_website')->nullable();
                $table->unsignedBigInteger('external_id')->nullable();

                $table->enum('status', [
                    'not_available',
                    'active',
                    'booked',
                    'deactive',
                    'follow_up',
                    'follow_up_expired',
                    'follow_up_complete',
                ])->default('active');

                $table->enum('type', [
                    'hot',
                    'cold',
                    'warm',
                ])->default('hot');

                $table->unsignedBigInteger('assigned_to')->nullable();
                $table->date('follow_up_date')->nullable();
                $table->text('notes')->nullable();
                $table->enum('visit_status', [
                    'not_scheduled',
                    'scheduled',
                    'completed',
                ])->default('not_scheduled');

                $table->date('visit_date')->nullable();
                $table->foreignId('sub_response_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('sub_responses')
                    ->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
