<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminManualLeadSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_lead_with_a_supported_source(): void
    {
        $this->mock(NotificationService::class);
        $user = $this->userWithCreateLeadPermission();

        $this->actingAs($user)
            ->post(route('lead-managment.store'), [
                'name' => 'Facebook lead',
                'phone' => '1234567890',
                'source_website' => 'facebook.com',
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('leads', [
            'name' => 'Facebook lead',
            'source_website' => 'facebook.com',
        ]);
    }

    public function test_admin_cannot_create_a_lead_with_an_unsupported_source(): void
    {
        $this->mock(NotificationService::class);
        $user = $this->userWithCreateLeadPermission();

        $this->actingAs($user)
            ->post(route('lead-managment.store'), [
                'name' => 'Invalid source lead',
                'phone' => '1234567890',
                'source_website' => 'unknown.example',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('source_website');

        $this->assertDatabaseMissing('leads', [
            'name' => 'Invalid source lead',
        ]);
    }

    public function test_admin_can_create_leads_from_whitehat_and_360propguide(): void
    {
        $this->mock(NotificationService::class);
        $user = $this->userWithCreateLeadPermission();

        foreach ([
            ['Whitehat lead', 'Whitehat'],
            ['360PropGuide lead', '360propguide.com'],
        ] as [$name, $source]) {
            $this->actingAs($user)
                ->post(route('lead-managment.store'), [
                    'name' => $name,
                    'phone' => '1234567890',
                    'source_website' => $source,
                ])
                ->assertOk()
                ->assertJsonPath('status', true);

            $this->assertDatabaseHas('leads', [
                'name' => $name,
                'source_website' => $source,
            ]);
        }
    }

    public function test_add_lead_form_lists_the_supported_sources(): void
    {
        $user = $this->userWithCreateLeadPermission();

        $this->actingAs($user)
            ->get(route('lead-management.index'))
            ->assertOk()
            ->assertSee('Facebook')
            ->assertSee('Whitehat')
            ->assertSee('360PropGuide');
    }

    private function userWithCreateLeadPermission(): User
    {
        $permissions = [
            Permission::create([
                'name' => 'create.lead',
                'guard_name' => 'web',
            ]),
            Permission::create([
                'name' => 'view.lead',
                'guard_name' => 'web',
            ]),
        ];

        $role = Role::create([
            'name' => 'lead-creator',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
