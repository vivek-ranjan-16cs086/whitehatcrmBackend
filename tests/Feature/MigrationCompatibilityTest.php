<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MigrationCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_database_with_missing_migration_history_can_resume_without_losing_data(): void
    {
        $user = User::factory()->create(['phone' => '1234567890']);
        $role = Role::create(['name' => 'sales', 'guard_name' => 'web']);
        $user->assignRole($role);
        $lead = Lead::create(['name' => 'Preserved lead', 'assigned_to' => $user->id]);
        DB::table('lead_responses')->insert([
            'lead_id' => $lead->id, 'user_id' => $user->id, 'note' => 'Preserved history',
        ]);

        // Reproduce an imported hosting database whose schema already exists,
        // but whose migrations table is missing the corresponding records.
        DB::table('migrations')->delete();

        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'phone' => '1234567890']);
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'name' => 'Preserved lead']);
        $this->assertDatabaseHas('lead_responses', ['lead_id' => $lead->id, 'note' => 'Preserved history']);
        $this->assertDatabaseHas('model_has_roles', ['model_id' => $user->id, 'role_id' => $role->id]);
        $this->assertDatabaseCount('migrations', count(glob(database_path('migrations/*.php'))));
    }

    public function test_partially_existing_user_fields_are_completed_without_replacing_existing_values(): void
    {
        $user = User::factory()->create(['phone' => '1234567890']);
        Schema::table('users', function ($table) {
            $table->dropColumn(['alternate_phone', 'address']);
        });

        $migration = require database_path('migrations/2026_03_21_120043_add_extra_fields_to_users_table.php');
        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasColumns('users', ['phone', 'alternate_phone', 'date_of_birth', 'address', 'profile_pic']));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'phone' => '1234567890']);
    }

    public function test_multi_table_migration_creates_only_missing_tables(): void
    {
        DB::table('cache')->insert(['key' => 'preserved', 'value' => 'existing value', 'expiration' => 2000000000]);
        Schema::drop('cache_locks');

        $migration = require database_path('migrations/0001_01_01_000001_create_cache_table.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('cache_locks'));
        $this->assertDatabaseHas('cache', ['key' => 'preserved', 'value' => 'existing value']);
    }
}
