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
        if (Schema::hasColumn('leads', 'deactivate_reason')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {

            // ENUM field
            $table->enum('deactivate_reason', [
                'Plot or Villa',
                'Budget',
                'Location',
                'Ready To Move',
                'Rent',
                'Invalid Mobile Number',
                'Others',
                'Switch Off',
            ])->nullable()->after('project_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {

            $table->dropColumn('deactivate_reason');
        });
    }
};
