<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadOptionsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadOptionsDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_reasons_match_the_api_without_counting_saved_leads(): void
    {
        $expected = ['Plot or Villa', 'Budget', 'Location', 'Ready To Move', 'Rent', 'Invalid Mobile Number', 'Others', 'Switch Off'];
        $service = app(LeadOptionsService::class);
        $this->assertSame($expected, $service->all()['deactivate_reason']);
        $user = User::factory()->create();
        $this->assertSame($expected, $service->filtersFor($user)['deactivate_reason']);
        $response = app(\App\Http\Controllers\Api\LeadController::class)->getDeactivateList()->getData(true);
        $this->assertSame($expected, array_column($response['data']['options'], 'label'));
        DB::table('leads')->insert(['name' => 'Legacy reason', 'deactivate_reason' => 'Custom reason']);
        $this->assertSame($expected, $service->all()['deactivate_reason']);
    }

    public function test_filter_sources_are_exact_saved_values_from_visible_non_deleted_leads(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Lead::create(['name' => 'Visible', 'assigned_to' => $user->id, 'lead_source' => 'Exact CASE Source', 'source_website' => 'actual.example']);
        Lead::create(['name' => 'Private', 'assigned_to' => $other->id, 'lead_source' => 'Private source']);
        $deleted = Lead::create(['name' => 'Deleted', 'assigned_to' => $user->id, 'lead_source' => 'Deleted source']);
        $deleted->delete();
        $options = app(LeadOptionsService::class)->filtersFor($user);
        $this->assertSame(['Exact CASE Source'], $options['lead_source']);
        $this->assertSame(['actual.example'], $options['source_website']);
    }

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
            'lead_source' => 'Custom source', 'inventory' => 'Custom property'] as $field => $value) {
            $this->assertContains($value, $options[$field]);
        }
        $this->assertNotContains('Calling', $options['lead_source']);
        $this->assertNotContains('ready-to-buy', $options['type']);
        DB::table('leads')->update(['lead_source' => 'Changed source']);
        $this->assertSame(['Changed source'], $service->all()['lead_source']);
        Lead::firstOrFail()->delete();
        $this->assertSame([], $service->all()['lead_source']);
    }

    public function test_unused_enum_choices_are_not_offered_as_saved_values(): void
    {
        $options = app(LeadOptionsService::class)->all();
        $this->assertContains('Budget', $options['deactivate_reason']);
        $this->assertSame([], $options['status']);
        $this->assertSame([], $options['type']);
    }
}
