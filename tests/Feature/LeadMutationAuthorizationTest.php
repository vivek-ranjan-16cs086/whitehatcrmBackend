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
        $lead = Lead::create(['name' => 'Editable lead', 'status' => 'active']);

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

    public function test_custom_role_with_edit_permission_can_view_an_unassigned_lead(): void
    {
        $user = $this->userWithPermissions('regional-manager', ['view.lead', 'edit.lead']);
        $lead = Lead::create([
            'name' => 'Another user lead',
            'status' => 'active',
            'assigned_to' => User::factory()->create()->id,
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/leads/' . $lead->id)
            ->assertOk()
            ->assertJsonPath('status', true);
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
