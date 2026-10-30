<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('leads', 'source_page_name')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->string('source_page_name')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('leads', 'source_page_name')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn('source_page_name');
            });
        }
    }
};
