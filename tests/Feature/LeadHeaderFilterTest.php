<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use App\Services\LeadHeaderFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadHeaderFilterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));

        return $user;
    }

    public function test_header_choices_are_distinct_database_values_and_related_names(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create(['name' => 'Actual Owner']);
        $project = Project::create(['name' => 'Actual Project', 'email' => 'project@example.com', 'phone' => '1234567890']);
        foreach (['Buyer One', 'Buyer Two'] as $name) {
            $lead = Lead::create(['name' => $name, 'assigned_to' => $owner->id, 'source_website' => 'db.example', 'source_page_name' => 'DB Page', 'type' => 'custom-type', 'status' => 'custom-status', 'inventory' => 'Penthouse', 'visit_date' => '2026-11-20', 'visit_status' => 'custom-visit']);
            $lead->forceFill(['project_id' => $project->id])->save();
        }
        $options = app(LeadHeaderFilterService::class)->forUser($admin);
        $this->assertSame([['value' => 'db.example', 'label' => 'db.example']], $options['source_website']);
        $this->assertSame([['value' => 'DB Page', 'label' => 'DB Page']], $options['source_page_name']);
        $this->assertSame([['value' => 'custom-type', 'label' => 'Custom Type']], $options['type']);
        $this->assertSame([['value' => (string) $owner->id, 'label' => 'Actual Owner']], $options['assigned_to']);
        $this->assertSame([['value' => (string) $project->id, 'label' => 'Actual Project']], $options['project_id']);
        $this->assertSame([['value' => '2026-11-20', 'label' => '20 Nov 2026']], $options['visit_date']);
        $this->actingAs($admin)->get(route('lead-management.index'))->assertOk()->assertSee('data-lead-column-filter="source_page_name"', false);
    }

    public function test_header_filters_match_exact_values_combine_and_clear(): void
    {
        $admin = $this->admin();
        $match = Lead::create(['name' => 'Matching', 'source_website' => 'db.example', 'source_page_name' => 'Page', 'status' => 'active', 'type' => 'cold', 'inventory' => '3 BHK', 'visit_date' => '2026-11-20', 'visit_status' => 'visited', 'assigned_to' => $admin->id]);
        Lead::create(['name' => 'Similar page', 'source_website' => 'db.example', 'source_page_name' => 'Page Extended', 'status' => 'active']);
        $this->actingAs($admin)->getJson(route('lead-management.ajax-list', ['header_filters' => ['source_website' => 'db.example', 'source_page_name' => 'Page', 'assigned_to' => (string) $admin->id, 'status' => 'active', 'type' => 'cold', 'inventory' => '3 BHK', 'visit_date' => '2026-11-20', 'visit_status' => 'visited']]))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.lead_id', $match->id);
        $this->getJson(route('lead-management.ajax-list'))->assertOk()->assertJsonPath('recordsFiltered', 2);
        $this->getJson(route('lead-management.ajax-list', ['header_filters' => ['assigned_to' => '__empty__']]))->assertOk()->assertJsonPath('recordsFiltered', 1);
        $this->getJson(route('lead-management.ajax-list', ['header_filters' => ['visit_date' => 'bad']]))->assertUnprocessable();
        $this->getJson(route('lead-management.ajax-list', ['header_filters' => 'bad']))->assertUnprocessable();
    }

    public function test_meta_app_name_is_shown_separately_from_the_page_name(): void
    {
        $admin = $this->admin();
        $lead = Lead::create([
            'name' => 'Wooden Strings lead',
            'source_website' => 'facebook.com',
            'source_page_name' => 'TWS Instant Form',
            'meta_app_name' => 'The Wooden Strings',
        ]);

        $this->actingAs($admin)
            ->get(route('lead-management.index'))
            ->assertOk()
            ->assertSee('<th>Meta App</th>', false);

        $this->getJson(route('lead-management.ajax-list'))
            ->assertOk()
            ->assertJsonPath('data.0.source_page_name', 'TWS Instant Form')
            ->assertJsonPath('data.0.meta_app_name', 'The Wooden Strings')
            ->assertJsonPath('data.0.lead_id', $lead->id);
    }

    public function test_filter_options_and_results_do_not_expose_other_owners_leads(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'reader', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::create(['name' => 'view.lead', 'guard_name' => 'web']));
        $user->assignRole($role);
        Lead::create(['name' => 'Mine', 'source_website' => 'mine.example', 'assigned_to' => $user->id]);
        Lead::create(['name' => 'Private', 'source_website' => 'secret.example', 'source_page_name' => 'Secret page']);
        $options = app(LeadHeaderFilterService::class)->forUser($user);
        $this->assertSame(['mine.example'], array_column($options['source_website'], 'value'));
        $this->assertNotContains('Secret page', array_column($options['source_page_name'], 'value'));
        $this->actingAs($user)->getJson(route('lead-management.ajax-list', ['header_filters' => ['source_website' => 'secret.example']]))
            ->assertOk()->assertJsonPath('recordsFiltered', 0);
    }
}
