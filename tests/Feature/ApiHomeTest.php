<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Target;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_requires_authentication_and_lead_permission(): void
    {
        $this->getJson('/api/v1/home')->assertUnauthorized();
        $this->homeFor(User::factory()->create())->assertForbidden();
    }

    public function test_sales_home_uses_token_identity_even_if_request_asks_for_team_data(): void
    {
        $sales = $this->viewer('sales');
        $other = User::factory()->create();
        Lead::create(['name' => 'My follow-up', 'status' => 'follow_up', 'assigned_to' => $sales->id, 'follow_up_date' => now()->addHour()]);
        Lead::create(['name' => 'Unassigned']);
        Lead::create(['name' => 'Other follow-up', 'status' => 'follow_up', 'assigned_to' => $other->id, 'follow_up_date' => now()->addHour()]);
        Notification::create(['user_id' => $sales->id, 'title' => 'Mine', 'message' => 'Test', 'type' => 'TEST', 'is_read' => false]);
        Notification::create(['user_id' => $other->id, 'title' => 'Other', 'message' => 'Test', 'type' => 'TEST', 'is_read' => false]);

        $response = $this->homeFor($sales, '?scope=team&role=super-admin&user_id='.$other->id)
            ->assertOk()
            ->assertJsonPath('data.user.id', $sales->id)
            ->assertJsonPath('data.user.roles', ['sales'])
            ->assertJsonPath('data.scope', 'self')
            ->assertJsonPath('data.unread_notifications', 1)
            ->assertJsonPath('data.decision_focus', null)
            ->assertJsonPath('data.next_best_actions.due_followups.count', 1);

        $cards = array_column($response->json('data.cards'), 'value', 'key');
        $this->assertSame(1, $cards['total_leads']);
        $this->assertArrayNotHasKey('unassigned_leads', $cards);
        $this->assertArrayNotHasKey('expired_leads', $cards);
    }

    public function test_super_admin_home_can_see_team_metrics_and_aggregates_weekly_targets(): void
    {
        $admin = $this->viewer('super-admin');
        $project = Project::create(['name' => 'Sample Project', 'email' => 'project@example.test', 'phone' => '1234567890']);
        foreach (User::factory()->count(2)->create() as $agent) {
            Target::create([
                'user_id' => $agent->id, 'project_id' => $project->id,
                'start_day' => now()->toDateString(), 'end_day' => now()->addDays(6)->toDateString(),
            ]);
            Lead::create([
                'name' => 'Booking', 'assigned_to' => $agent->id, 'status' => 'booked',
                'visit_date' => now()->toDateString(), 'visit_status' => 'visited',
            ]);
        }
        Lead::create(['name' => 'Unassigned']);
        Lead::create(['name' => 'Expired', 'status' => 'follow_up_expired', 'follow_up_date' => now()->subDay()]);

        $response = $this->homeFor($admin)
            ->assertOk()
            ->assertJsonPath('data.scope', 'team')
            ->assertJsonPath('data.decision_focus.breakdown.overdue_followups', 1)
            ->assertJsonPath('data.next_best_actions.due_followups.scope', 'self');
        $cards = array_column($response->json('data.cards'), 'value', 'key');
        $this->assertSame(4, $cards['total_leads']);
        $this->assertSame(2, $cards['unassigned_leads']);
        $this->assertSame(1, $cards['expired_leads']);
        $this->assertSame(2, $cards['weekly_visit_target']);
        $this->assertSame(2, $cards['weekly_booking_target']);
    }

    public function test_custom_team_viewer_gets_team_scope_from_permissions(): void
    {
        $manager = $this->viewer('manager');
        $manager->givePermissionTo(Permission::create(['name' => 'view_team.lead', 'guard_name' => 'web']));

        $this->homeFor($manager)->assertOk()
            ->assertJsonPath('data.scope', 'team')
            ->assertJsonPath('data.user.roles', ['manager']);
    }

    private function viewer(string $name): User
    {
        $role = Role::create(['name' => $name, 'guard_name' => 'web']);
        if ($name !== 'super-admin') {
            $role->givePermissionTo(Permission::create(['name' => 'view.lead', 'guard_name' => 'web']));
        }

        return User::factory()->create()->assignRole($role);
    }

    private function homeFor(User $user, string $query = ''): TestResponse
    {
        config(['jwt.secret' => str_repeat('test-key-', 8)]);

        return $this->getJson('/api/v1/home'.$query, ['Authorization' => 'Bearer '.JWTAuth::fromUser($user)]);
    }
}
