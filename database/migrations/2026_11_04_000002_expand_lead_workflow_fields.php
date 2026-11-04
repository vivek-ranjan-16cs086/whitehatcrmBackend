<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'lead_source')) $table->string('lead_source')->nullable();
            if (!Schema::hasColumn('leads', 'budget_min')) $table->decimal('budget_min', 15, 2)->nullable();
            if (!Schema::hasColumn('leads', 'budget_max')) $table->decimal('budget_max', 15, 2)->nullable();
            $table->string('status', 50)->default('active')->change();
            $table->string('type', 50)->default('hot')->change();
            $table->string('visit_status', 50)->default('not_scheduled')->change();
            $table->dateTime('follow_up_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Preserve workflow values and follow-up times on rollback.
    }
};
