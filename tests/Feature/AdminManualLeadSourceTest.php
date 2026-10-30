<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            ->assertSee('360PropGuide')
            ->assertSee('download', false)
            ->assertSee('name="_token"', false);
    }

    public function test_admin_can_import_csv_leads_for_the_selected_source(): void
    {
        $this->mock(NotificationService::class);
        $user = $this->userWithCreateLeadPermission();
        $csv = UploadedFile::fake()->createWithContent(
            'leads.csv',
            "name,phone,email,message,status,type,follow_up_date,notes\n"
                ."Imported lead,0123456789,lead@example.com,Interested,active,warm,2026-11-15,Call tomorrow\n"
        );

        $this->actingAs($user)
            ->post(route('lead-managment.import'), [
                'source_website' => 'facebook.com',
                'file' => $csv,
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('leads', [
            'name' => 'Imported lead',
            'phone' => '0123456789',
            'source_website' => 'facebook.com',
            'message' => 'Interested',
            'type' => 'warm',
            'follow_up_date' => '2026-11-15 00:00:00',
            'notes' => 'Call tomorrow',
        ]);
    }

    public function test_duplicate_phone_rejects_the_import_without_partially_inserting_rows(): void
    {
        $this->mock(NotificationService::class);
        $user = $this->userWithCreateLeadPermission();
        \App\Models\Lead::create([
            'name' => 'Existing lead',
            'phone' => '1234567890',
        ]);
        $csv = UploadedFile::fake()->createWithContent(
            'leads.csv',
            "name,phone\nNew lead,0987654321\nDuplicate lead,1234567890\n"
        );

        $this->actingAs($user)
            ->post(route('lead-managment.import'), [
                'source_website' => 'Whitehat',
                'file' => $csv,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('status', false);

        $this->assertDatabaseMissing('leads', ['name' => 'New lead']);
    }

    public function test_csv_template_has_the_import_headers(): void
    {
        $user = $this->userWithCreateLeadPermission();

        $response = $this->actingAs($user)
            ->get(route('lead-managment.import-template'));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringStartsWith(
            'name,phone,email,message,status,type,follow_up_date,notes',
            $response->streamedContent()
        );
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
