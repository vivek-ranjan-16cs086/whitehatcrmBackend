<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApiLeadAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_leads_from_either_api_route(): void
    {
        $this->getJson('/api/v1/leads')->assertUnauthorized();
        $this->getJson('/api/v1/get-leads')->assertUnauthorized();
    }

    public function test_authenticated_user_without_view_permission_cannot_list_leads(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/leads')
            ->assertForbidden();
    }

    public function test_user_with_view_permission_can_list_leads(): void
    {
        $user = $this->userWithLeadViewPermission();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/leads')
            ->assertOk()
            ->assertJsonPath('status', true);
    }

    public function test_super_admin_can_list_leads_without_an_explicit_permission(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/leads')
            ->assertOk()
            ->assertJsonPath('status', true);
    }

    public function test_salesperson_cannot_fetch_another_users_lead_by_id(): void
    {
        $user = $this->userWithLeadViewPermission('salesperson');
        $lead = Lead::create([
            'name' => 'Private lead',
            'assigned_to' => User::factory()->create()->id,
        ]);

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/leads/' . $lead->id)
            ->assertNotFound();
    }

    private function userWithLeadViewPermission(string $roleName = 'lead-reader'): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'view.lead',
            'guard_name' => 'web',
        ]);
        $role = Role::create([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
