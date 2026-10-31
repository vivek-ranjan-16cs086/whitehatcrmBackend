<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('username')->nullable()->unique());
        }
    }

    public function down(): void
    {
        // Existing hosting databases may already use usernames for login.
        // Keep this shared account field when rolling back.
    }
};
