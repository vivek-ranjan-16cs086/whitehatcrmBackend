<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_chart_filters_dates_and_preserves_assigned_lead_visibility(): void
    {
        $permission = Permission::create(['name' => 'view.lead', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'chart-viewer', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->assignRole($role);
        $inside = Lead::create(['name' => 'Visible', 'status' => 'new', 'assigned_to' => $user->id]);
        $inside->forceFill(['created_at' => '2026-10-15 12:00:00'])->save();
        $outside = Lead::create(['name' => 'Outside dates', 'status' => 'new', 'assigned_to' => $user->id]);
        $outside->forceFill(['created_at' => '2026-09-15 12:00:00'])->save();
        $other = Lead::create(['name' => 'Other owner', 'status' => 'new', 'assigned_to' => User::factory()->create()->id]);
        $other->forceFill(['created_at' => '2026-10-15 12:00:00'])->save();
        $this->actingAs($user)->get(route('dashboard', ['chart_start' => '2026-10-01', 'chart_end' => '2026-10-31']))
            ->assertOk()->assertViewHas('chartRows', fn ($rows) => $rows->sum('total') === 1)
            ->assertSee('Reset zoom');
    }

    public function test_chart_rejects_reversed_and_excessive_date_ranges(): void
    {
        $user = User::factory()->create();
        foreach ([['2026-10-31', '2026-10-01'], ['2024-01-01', '2026-10-31']] as [$start, $end]) {
            $this->actingAs($user)->get(route('dashboard', ['chart_start' => $start, 'chart_end' => $end]))
                ->assertSessionHasErrors('chart_end');
        }
    }
}
