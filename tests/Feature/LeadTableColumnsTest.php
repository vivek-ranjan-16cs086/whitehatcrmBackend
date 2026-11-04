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

    public function test_unassigned_button_filters_all_unassigned_leads(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        Lead::create(['name' => 'Unassigned active', 'status' => 'active']);
        Lead::create(['name' => 'Unassigned booked', 'status' => 'booked']);
        Lead::create(['name' => 'Assigned', 'assigned_to' => $admin->id]);
        $this->actingAs($admin)->get(route('lead-management.index'))->assertOk()
            ->assertSee('id="unassignedLeadsButton"', false);
        $this->getJson(route('lead-management.ajax-list', ['start' => 0, 'length' => 20, 'attention' => 'unassigned']))
            ->assertOk()->assertJsonPath('recordsFiltered', 2);
        $this->getJson(route('admin.sidebar.menu', ['attention' => 'unassigned']))
            ->assertOk()->assertJsonMissing(['label' => 'Unassigned leads']);
    }

    public function test_filter_fields_match_the_form_and_ignore_other_columns(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        Lead::create(['name' => 'Database buyer', 'status' => 'database-status', 'type' => 'database-type']);
        $html = $this->actingAs($admin)->get(route('lead-management.index'))->assertOk()->getContent();
        $document = new \DOMDocument();
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query("//input[@id='leadDateFrom' and @type='date']")->length);
        $this->assertSame(1, $xpath->query("//input[@id='leadDateTo' and @type='date']")->length);
        $formFields = [];
        foreach ($xpath->query("//*[@id='addLeadForm']//*[@name]") as $input) {
            $formFields[] = $input->getAttribute('name');
        }
        foreach ($xpath->query('//*[@data-lead-filter]') as $input) {
            $field = preg_replace('/^((?:follow_up|visit)_date)_(from|to)$/', '$1', $input->getAttribute('data-lead-filter'));
            $this->assertContains($field, $formFields);
        }
        $filterIds = ['status' => 'leadStatusFilter', 'source_website' => 'leadSourceFilter',
            'project_id' => 'leadProjectFilter', 'assigned_to' => 'filterAssigned'];
        foreach ($xpath->query("//*[@id='addLeadForm']//select[@name]") as $dropdown) {
            $field = $dropdown->getAttribute('name');
            $id = $filterIds[$field] ?? 'lead-filter-'.$field;
            $this->assertSame(1, $xpath->query("//select[@id='{$id}']")->length, $field.' must have a dropdown filter');
            $this->assertSame(0, $xpath->query("//input[@data-lead-filter='{$field}']")->length, $field.' must not have a text filter');
        }
        foreach ($formFields as $field) {
            if (in_array($field, ['status', 'project_id', 'source_website'], true)) continue;
            if (in_array($field, ['visit_date', 'follow_up_date'], true)) $field .= '_from';
            $this->assertSame(1, $xpath->query("//*[@data-lead-filter='{$field}']")->length, $field.' needs a filter');
        }
        $statusValues = [];
        foreach ($xpath->query("//*[@id='leadStatusFilter']/option[@value!='']") as $option) {
            $statusValues[] = $option->getAttribute('value');
        }
        $this->assertSame(['database-status'], $statusValues);
        $this->assertSame(0, $xpath->query("//*[@id='filterResponse']")->length);
        $this->getJson(route('lead-management.ajax-list', [
            'start' => 0, 'length' => 20,
            'filters' => ['external_id' => '999', 'updated_at_from' => '2099-01-01', 'sub_response_id' => 999],
        ]))->assertOk()->assertJsonPath('recordsFiltered', 1);
    }

    public function test_top_date_range_limits_arrivals_and_can_be_cleared(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        Lead::create(['name' => 'Inside range'])->forceFill(['created_at' => '2026-11-02 12:00:00'])->save();
        Lead::create(['name' => 'Outside range'])->forceFill(['created_at' => '2026-10-01 12:00:00'])->save();
        $this->actingAs($admin)->getJson(route('lead-management.ajax-list', [
            'start' => 0, 'length' => 20, 'date_from' => '2026-11-01', 'date_to' => '2026-11-03',
        ]))->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.edit_data.name', 'Inside range');
        $this->getJson(route('lead-management.ajax-list', ['start' => 0, 'length' => 20]))
            ->assertOk()->assertJsonPath('recordsFiltered', 2);
    }

    public function test_extended_filters_support_owner_text_dates_and_budget_without_exposing_other_leads(): void
    {
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
            'filter_project' => $project->id, 'filter_inventory' => 'Premium 3 BHK', 'filter_visit' => 'scheduled',
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
