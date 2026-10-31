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
        if (! Schema::hasTable('targets')) {
            Schema::create('targets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('project_id');

                $table->integer('booking_set')->default(0);
                $table->integer('booking_done')->default(0);
                $table->integer('site_visited')->default(0);
                $table->integer('site_visited_done')->default(0);

                $table->date('start_day');
                $table->date('end_day');
                $table->enum('status', ['active', 'pending', 'completed'])->default('active');

                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
