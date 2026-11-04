<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadWorkflowMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_preserves_nulls_legacy_values_and_follow_up_times(): void
    {
        $nullId = DB::table('leads')->insertGetId([
            'name' => 'Legacy null values', 'status' => null, 'type' => null,
            'visit_status' => null, 'follow_up_date' => '2026-11-05 14:35:00',
        ]);
        $legacyType = str_repeat('legacy', 20);
        $legacyId = DB::table('leads')->insertGetId([
            'name' => 'Legacy long value', 'type' => $legacyType,
        ]);

        $migration = require database_path('migrations/2026_11_04_000002_expand_lead_workflow_fields.php');
        $migration->up();
        $migration->up();

        $this->assertDatabaseHas('leads', [
            'id' => $nullId, 'status' => null, 'type' => null, 'visit_status' => null,
            'follow_up_date' => '2026-11-05 14:35:00',
        ]);
        $this->assertDatabaseHas('leads', ['id' => $legacyId, 'type' => $legacyType]);
    }
}
