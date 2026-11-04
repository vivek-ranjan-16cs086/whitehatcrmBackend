<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadFormWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_and_edit_have_matching_fields_and_edit_locks_original_details(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        $html = $this->actingAs($admin)->get(route('lead-management.index'))->assertOk()->getContent();
        $document = new \DOMDocument();
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $fields = [];
        foreach (['addLeadForm', 'editLeadForm'] as $form) {
            foreach ($xpath->query("//*[@id='{$form}']//*[@name and not(@type='hidden')]") as $input) {
                $fields[$form][$input->getAttribute('name')] = $input;
            }
        }
        $this->assertSame(array_keys($fields['addLeadForm']), array_keys($fields['editLeadForm']));
        foreach (['name', 'phone', 'email', 'message', 'source_page_name'] as $field) {
            $this->assertTrue($fields['editLeadForm'][$field]->hasAttribute('readonly'));
            $this->assertFalse($fields['addLeadForm'][$field]->hasAttribute('readonly'));
        }
        $this->assertTrue($fields['editLeadForm']['project_id']->hasAttribute('disabled'));
        $this->assertFalse($fields['editLeadForm']['assigned_to']->hasAttribute('disabled'));
    }

    public function test_add_and_edit_save_all_workflow_fields_and_preserve_original_details(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        $project = Project::create(['name' => 'Project', 'email' => 'project@example.com', 'phone' => '1234567890']);
        $this->actingAs($admin);
        $payload = [
            'name' => 'Original buyer', 'phone' => '1234567890', 'source_website' => 'facebook.com',
            'status' => 'visit', 'type' => 'ready-to-buy', 'visit_status' => 're-visit-plan',
            'visit_date' => '2026-11-10', 'follow_up_date' => '2026-11-09T14:35',
            'lead_source' => 'Referral', 'inventory' => '3 BHK', 'project_id' => $project->id,
            'budget_min' => 100000, 'budget_max' => 200000, 'notes' => 'Buyer notes',
        ];
        // Dropdown choices are supplied by existing database values.
        \Illuminate\Support\Facades\DB::table('leads')->insert([
            'name' => 'Existing options', 'status' => 'visit', 'type' => 'ready-to-buy',
            'visit_status' => 're-visit-plan', 'lead_source' => 'Referral', 'inventory' => '3 BHK',
        ]);
        Lead::create(['name' => 'Existing visit', 'visit_status' => 'visited']);
        $id = $this->postJson(route('lead-managment.store'), $payload)->assertOk()->json('data.id');
        $lead = Lead::findOrFail($id);
        $this->assertSame('14:35', $lead->follow_up_date->format('H:i'));
        $this->assertSame('Buyer notes', $lead->notes);
        $this->assertSame('Referral', $lead->lead_source);
        $this->assertSame('100000.00', $lead->budget_min);
        $this->postJson(route('lead-managment.update'), [
            'id' => $id, 'name' => 'Tampered', 'phone' => '9999999999', 'source_website' => 'changed.example',
            'project_id' => null, 'email' => 'tampered@example.com', 'message' => 'Tampered message',
            'source_page_name' => 'Tampered page', 'assigned_to' => $admin->id,
            'follow_up_date' => '2026-11-10T16:45', 'notes' => 'Updated notes', 'visit_status' => 'visited',
        ])->assertOk();
        $lead->refresh();
        $this->assertSame('Original buyer', $lead->name);
        $this->assertEquals($project->id, $lead->project_id);
        $this->assertNull($lead->email);
        $this->assertNull($lead->message);
        $this->assertSame('1234567890', $lead->phone);
        $this->assertSame('facebook.com', $lead->source_website);
        $this->assertNull($lead->source_page_name);
        $this->assertEquals($admin->id, $lead->assigned_to);
        $this->assertSame('16:45', $lead->follow_up_date->format('H:i'));
        $this->assertSame('Updated notes', $lead->notes);
        $this->assertSame('visited', $lead->visit_status);
    }

    public function test_dropdown_options_include_existing_data_and_assignees_use_numeric_ids(): void
    {
        $admin = User::factory()->create(['name' => 'Directory person']);
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        Lead::create(['name' => 'Existing', 'lead_source' => 'Existing source', 'inventory' => 'Penthouse', 'type' => 'ready-to-buy']);
        $this->actingAs($admin)->get(route('lead-management.index'))->assertOk()
            ->assertSee('Existing source')->assertSee('Penthouse')->assertSee('ready-to-buy')
            ->assertSee('id="add_type"', false)->assertSee('datetime-local');
        $this->getJson(route('lead-management.assignees', ['q' => 'Directory person']))->assertOk()
            ->assertJsonPath('results.0.id', $admin->id)->assertJsonPath('pagination.more', false);
    }
}
