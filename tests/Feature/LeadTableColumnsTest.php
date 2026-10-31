<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadTableColumnsTest extends TestCase
{
    use RefreshDatabase;

    public function test_extended_filters_support_owner_text_dates_and_budget_without_exposing_other_leads(): void
    {
        \Illuminate\Support\Facades\Schema::table('leads', function ($table) {
            $table->decimal('budget_min', 14, 2)->nullable();
            $table->decimal('budget_max', 14, 2)->nullable();
        });
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        Lead::create(['name' => 'Matching buyer', 'assigned_to' => $admin->id, 'type' => 'hot', 'visit_date' => '2026-10-31', 'budget_min' => 100, 'budget_max' => 500]);
        Lead::create(['name' => 'Other buyer', 'type' => 'cold', 'visit_date' => '2026-11-10', 'budget_min' => 50, 'budget_max' => 900]);
        $this->actingAs($admin)->getJson(route('lead-management.ajax-list', [
            'start' => 0, 'length' => 20, 'filters' => ['assigned_to' => (string) $admin->id, 'type' => 'hot', 'name' => 'Matching', 'visit_date_from' => '2026-10-30', 'visit_date_to' => '2026-10-31', 'budget_min' => '100', 'budget_max' => '500'],
        ]))->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.edit_data.name', 'Matching buyer');
        $this->getJson(route('lead-management.ajax-list', ['filters' => ['visit_date_from' => 'invalid']]))
            ->assertUnprocessable();
        $this->getJson(route('lead-management.ajax-list', ['start' => 0, 'length' => 20, 'filters' => ['assigned_to' => 'unassigned']]))
            ->assertOk()->assertJsonPath('recordsFiltered', 1);
    }

    public function test_more_filters_combine_and_clearing_them_restores_leads(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        $project = Project::create(['name' => 'Filter project', 'email' => 'test@example.com', 'phone' => '1234567890']);
        $match = Lead::create(['name' => 'Matching lead', 'status' => 'active', 'inventory' => 'Premium 3 BHK', 'visit_status' => 'scheduled']);
        $match->forceFill(['project_id' => $project->id])->save();
        Lead::create(['name' => 'Other lead', 'status' => 'booked', 'inventory' => '2 BHK']);
        $this->actingAs($admin)->getJson(route('lead-management.ajax-list', [
            'start' => 0, 'length' => 20, 'filter_status' => 'active',
            'filter_project' => $project->id, 'filter_inventory' => '3 BHK', 'filter_visit' => 'scheduled',
        ]))->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.edit_data.name', 'Matching lead');
        $this->getJson(route('lead-management.ajax-list', ['start' => 0, 'length' => 20]))
            ->assertOk()->assertJsonPath('recordsFiltered', 2);
    }

    public function test_arrival_time_is_converted_to_indian_time_including_date_rollover(): void
    {
        $originalTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        try {
            config(['app.timezone' => 'UTC']);
            $admin = User::factory()->create();
            $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
            $lead = Lead::create(['name' => 'Late arrival', 'assigned_to' => $admin->id]);
            $lead->forceFill(['created_at' => '2026-10-30 22:45:00'])->save();

            $this->actingAs($admin)->getJson(route('lead-management.ajax-list', ['start' => 0, 'length' => 20]))
                ->assertOk()->assertJsonPath('data.0.arrived_at_ist', '31 Oct 2026, 04:15 AM IST');
        } finally {
            date_default_timezone_set($originalTimezone);
        }
    }

    public function test_lead_list_includes_project_inventory_and_visit_details_and_sorts_projects_by_name(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        foreach (['Zebra Estate', 'Alpha Estate'] as $name) {
            $project = Project::create(['name' => $name, 'email' => 'test@example.com', 'phone' => '1234567890']);
            $lead = Lead::create(['name' => $name, 'assigned_to' => $admin->id, 'inventory' => '3 BHK', 'visit_date' => '2026-10-31', 'visit_status' => 'scheduled']);
            $lead->forceFill(['project_id' => $project->id])->save();
        }
        $this->actingAs($admin)->getJson(route('lead-management.ajax-list', [
            'length' => 20, 'start' => 0, 'columns' => [['data' => 'project']],
            'order' => [['column' => 0, 'dir' => 'asc']],
        ]))->assertOk()->assertJsonPath('data.0.project', 'Alpha Estate')
            ->assertJsonPath('data.1.project', 'Zebra Estate')
            ->assertJsonPath('data.0.inventory', '3 BHK')
            ->assertJsonPath('data.0.visit_date', '31 Oct 2026')
            ->assertJsonPath('data.0.visit_status', 'Scheduled');
    }
}
