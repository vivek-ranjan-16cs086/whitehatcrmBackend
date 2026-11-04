<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Services\LeadOptionsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeadOptionsDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_options_follow_database_values_without_static_fallbacks(): void
    {
        $service = app(LeadOptionsService::class);
        $this->assertSame([], $service->all()['lead_source']);
        DB::table('leads')->insert([
            'name' => 'Database options', 'status' => 'custom-status', 'type' => 'custom-type',
            'visit_status' => 'custom-visit', 'deactivate_reason' => 'Custom reason',
            'lead_source' => 'Custom source', 'inventory' => 'Custom property',
        ]);
        $options = $service->all();
        foreach (['status' => 'custom-status', 'type' => 'custom-type', 'visit_status' => 'custom-visit',
            'deactivate_reason' => 'Custom reason', 'lead_source' => 'Custom source', 'inventory' => 'Custom property'] as $field => $value) {
            $this->assertContains($value, $options[$field]);
        }
        $this->assertNotContains('Calling', $options['lead_source']);
        $this->assertNotContains('ready-to-buy', $options['type']);
        DB::table('leads')->update(['lead_source' => 'Changed source']);
        $this->assertSame(['Changed source'], $service->all()['lead_source']);
        Lead::firstOrFail()->delete();
        $this->assertSame(['Changed source'], $service->all()['lead_source']);
    }

    public function test_unused_choices_come_from_database_enum_metadata(): void
    {
        $columns = Schema::getColumns('leads');
        foreach ($columns as &$column) {
            if ($column['name'] === 'deactivate_reason') {
                $column['type'] = "enum('Unused reason','Another reason')";
            }
        }
        unset($column);
        Schema::shouldReceive('getColumns')->once()->with('leads')->andReturn($columns);
        Schema::shouldReceive('hasColumn')->once()->with('projects', 'inventory')->andReturn(false);
        $this->assertSame(['Another reason', 'Unused reason'], app(LeadOptionsService::class)->all()['deactivate_reason']);
    }
}
