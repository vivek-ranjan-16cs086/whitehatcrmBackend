<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing hosting databases may already have these shared account fields.
        if (! Schema::hasColumn('users', 'enable')) {
            Schema::table('users', fn (Blueprint $table) => $table->boolean('enable')->default(true));
        }
        if (! Schema::hasColumn('users', 'device_token')) {
            Schema::table('users', fn (Blueprint $table) => $table->text('device_token')->nullable());
        }
    }

    public function down(): void
    {
        // Account status and Firebase tokens are shared by existing CRM features.
        // Preserve them when rolling back chat rather than deleting account data.
    }
};
