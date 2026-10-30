<?php

namespace Tests\Feature;

use App\Models\Project;
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
            ['Whitehat lead', 'whitehat.realty'],
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
            ->assertSee('Whitehat Realty')
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

    public function test_import_can_fill_all_lead_fields_present_in_the_database(): void
    {
        $this->mock(NotificationService::class);
        $user = $this->userWithCreateLeadPermission();
        $project = Project::create([
            'name' => 'Import Project',
            'email' => 'project@example.com',
            'phone' => '1112223333',
        ]);
        $headers = \App\Imports\AdminLeadsImport::HEADINGS;
        $values = array_fill_keys($headers, '');
        $values = array_merge($values, [
            'name' => 'Full details lead',
            'phone' => '0987654321',
            'email' => 'full@example.com',
            'message' => 'Facebook form answers and lead details',
            'external_id' => '78901',
            'status' => 'active',
            'type' => 'warm',
            'assigned_to' => (string) $user->id,
            'follow_up_date' => '2026-11-15',
            'notes' => 'Call next week',
            'visit_status' => 'scheduled',
            'visit_date' => '2026-11-20 00:00:00',
            'project_id' => (string) $project->id,
            'deactivate_reason' => '',
            'source_page_name' => 'Facebook Form',
            'source_form_name' => 'Forest Walk Diwali 2026',
            'purchase_timeline' => 'Immediate',
            'site_visit_preference' => 'Weekend',
            'funding_method' => 'Loan',
            'source_submitted_at' => '2026-10-29 17:16:55',
        ]);
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $headers);
        fputcsv($stream, array_map(fn ($header) => $values[$header], $headers));
        rewind($stream);
        $csvContents = stream_get_contents($stream);
        fclose($stream);
        $csv = UploadedFile::fake()->createWithContent('all-fields.csv', $csvContents);

        $this->actingAs($user)
            ->post(route('lead-managment.import'), [
                'source_website' => 'facebook.com',
                'file' => $csv,
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('leads', [
            'name' => 'Full details lead',
            'external_id' => 78901,
            'assigned_to' => $user->id,
            'project_id' => $project->id,
            'visit_status' => 'scheduled',
            'visit_date' => '2026-11-20 00:00:00',
            'source_website' => 'facebook.com',
            'message' => 'Facebook form answers and lead details',
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
                'source_website' => 'whitehat.realty',
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
            ->assertDownload('lead-import-template.csv')
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringStartsWith(
            implode(',', \App\Imports\AdminLeadsImport::HEADINGS),
            file_get_contents(resource_path('templates/lead-import-template.csv'))
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
