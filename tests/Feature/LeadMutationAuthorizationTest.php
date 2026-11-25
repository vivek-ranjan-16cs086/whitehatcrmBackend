<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LeadMutationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_executive_can_list_only_their_own_user_for_lead_assignment(): void
    {
        $executive = $this->userWithPermissions('business-executive', ['create.lead']);
        $other = User::factory()->create(['enable' => 1]);
        $executive->forceFill(['enable' => 1])->save();
        $this->actingAs($executive, 'api');

        $this->getJson('/api/v1/users')->assertOk()
            ->assertJsonPath('total_count', 1)
            ->assertJsonPath('data.0.id', $executive->id);
        $this->getJson('/api/v1/users?user_id='.$other->id)->assertOk()
            ->assertJsonPath('data', []);
        $this->getJson('/api/v1/users/'.$other->id)->assertForbidden();
    }

    public function test_business_executive_creates_a_self_assigned_lead_without_choosing_a_user(): void
    {
        $executive = $this->userWithPermissions('business-executive', ['create.lead']);
        $this->actingAs($executive, 'api');

        $this->getJson('/api/v1/all-users')->assertForbidden();
        $this->postJson('/api/v1/create-lead', ['name' => 'Self assigned lead'])->assertOk();
        $other = User::factory()->create();
        $this->postJson('/api/v1/create-lead', [
            'name' => 'Lead with another assignee in the request',
            'assigned_to' => $other->id,
        ])->assertOk();

        $this->assertDatabaseHas('leads', ['name' => 'Self assigned lead', 'assigned_to' => $executive->id]);
        $this->assertDatabaseHas('leads', ['name' => 'Lead with another assignee in the request', 'assigned_to' => $executive->id]);
    }

    public function test_super_admin_can_create_an_unassigned_lead_or_select_an_existing_user(): void
    {
        $admin = $this->userWithPermissions('super-admin', ['create.lead']);
        $assignee = User::factory()->create();
        $this->actingAs($admin, 'api');

        $this->postJson('/api/v1/create-lead', ['name' => 'Unassigned lead'])->assertOk();
        $this->postJson('/api/v1/create-lead', ['name' => 'Assigned lead', 'assigned_to' => $assignee->id])->assertOk();
        $this->postJson('/api/v1/create-lead', ['name' => 'Invalid assignee', 'assigned_to' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('assigned_to');

        $this->assertDatabaseHas('leads', ['name' => 'Unassigned lead', 'assigned_to' => null]);
        $this->assertDatabaseHas('leads', ['name' => 'Assigned lead', 'assigned_to' => $assignee->id]);
    }

    public function test_editor_can_mark_an_assigned_lead_as_not_connected_with_a_note(): void
    {
        $user = $this->userWithPermissions('lead-editor', ['edit.lead']);
        $lead = Lead::create(['name' => 'Buyer', 'status' => 'active', 'assigned_to' => $user->id]);
        $payload = ['status' => 'not_connected'];

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/leads/update-lead-status/'.$lead->id, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('note');

        $this->postJson('/api/v1/leads/update-lead-status/'.$lead->id, $payload + ['note' => 'Call went unanswered'])
            ->assertOk()
            ->assertJsonPath('data.status', 'not_connected');

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'not_connected']);
        $this->assertDatabaseHas('lead_timeline_events', ['lead_id' => $lead->id, 'event_type' => 'STATUS']);
    }

    public function test_user_without_edit_permission_cannot_update_lead_status(): void
    {
        $user = $this->userWithPermissions('business-associate', ['view.lead', 'create.lead']);
        $lead = Lead::create(['name' => 'Permission test lead', 'status' => 'active']);

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/leads/update-lead-status/' . $lead->id, [
                'status' => 'deactive',
                'note' => 'Updated by permitted role',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'active',
        ]);
    }

    public function test_user_with_edit_permission_passes_authorization_before_validation(): void
    {
        $user = $this->userWithPermissions('lead-editor', ['edit.lead']);
        $lead = Lead::create([
            'name' => 'Editable lead',
            'status' => 'active',
            'assigned_to' => $user->id,
        ]);

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/leads/update-lead-status/' . $lead->id, [
                'status' => 'invalid-status',
                'note' => 'Invalid status test',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'active',
        ]);
    }

    public function test_custom_role_with_edit_permission_cannot_view_an_unassigned_lead(): void
    {
        $user = $this->userWithPermissions('regional-manager', ['view.lead', 'edit.lead']);
        $lead = Lead::create([
            'name' => 'Another user lead',
            'status' => 'active',
            'assigned_to' => User::factory()->create()->id,
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/leads/' . $lead->id)
            ->assertNotFound();
    }

    public function test_user_without_edit_permission_cannot_shuffle_leads(): void
    {
        $user = $this->userWithPermissions('business-associate', ['view.lead', 'create.lead']);

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/shuffle-leads', [])
            ->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_create_or_import_leads(): void
    {
        $user = $this->userWithPermissions('lead-reader', ['view.lead']);
        $this->actingAs($user, 'api');

        $this->postJson('/api/v1/create-lead', [])->assertForbidden();
        $this->postJson('/api/v1/leads/import', [])->assertForbidden();
    }

    public function test_user_without_edit_permission_cannot_update_lead_from_web(): void
    {
        $user = $this->userWithPermissions('business-associate', ['view.lead', 'create.lead']);
        $lead = Lead::create(['name' => 'Web permission test lead', 'status' => 'active']);

        $this->actingAs($user)
            ->postJson(route('lead-managment.update'), [
                'id' => $lead->id,
                'status' => 'deactive',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'active',
        ]);
    }

    public function test_user_without_create_permission_cannot_create_lead_from_web(): void
    {
        $user = $this->userWithPermissions('lead-reader', ['view.lead']);

        $this->actingAs($user)
            ->postJson(route('lead-managment.store'), [])
            ->assertForbidden();
    }

    private function userWithPermissions(string $roleName, array $permissionNames): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        foreach ($permissionNames as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
            $role->givePermissionTo($permission);
        }

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
