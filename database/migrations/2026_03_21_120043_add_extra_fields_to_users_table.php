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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
			$table->string('alternate_phone')->nullable()->after('phone');
			$table->date('date_of_birth')->nullable()->after('alternate_phone'); 
			$table->text('address')->nullable()->after('date_of_birth');
			$table->string('profile_pic')->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
				'phone',
				'alternate_phone',
				'date_of_birth',
				'address',
				'profile_pic'
			]);
        });
    }
};
