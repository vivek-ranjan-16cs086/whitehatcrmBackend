<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_directory_has_separate_navigation_and_access_control_is_last(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        $this->actingAs($admin);
        $menus = $this->getJson(route('admin.sidebar.menu'))->assertOk()->json('data.menus');
        $this->assertSame('Access Control', end($menus)['label']);
        $directory = collect($menus)->firstWhere('label', 'User Management');
        $this->assertSame(route('admin.employees.index'), $directory['url']);
        $this->get(route('admin.employees.index'))->assertOk()->assertSee('Employees')->assertSee('Mobile')->assertSee('Project');
        $this->get(route('admin.users.index'))->assertOk()->assertSee('All Users')->assertDontSee('id="employeeSearch"', false);
    }

    public function test_employee_details_are_saved_and_searchable(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super-admin', 'guard_name' => 'web']));
        Role::create(['name' => 'sales', 'guard_name' => 'web']);
        $project = Project::create(['name' => 'Employee Project', 'email' => 'project@example.com', 'phone' => '9876543210']);
        $this->actingAs($admin)->withHeader('X-Requested-With', 'XMLHttpRequest');
        $this->postJson(route('admin.employees.store'), [
            'name' => 'Employee', 'username' => 'employee.one', 'email' => 'employee@example.com',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'role' => 'sales', 'phone' => '9876543210', 'project_id' => $project->id,
        ])->assertOk();
        $employee = User::where('username', 'employee.one')->firstOrFail();
        $this->getJson(route('admin.employees.index', ['start' => 0, 'length' => 20, 'search' => ['value' => $project->name]]))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.project', $project->name)
            ->assertJsonPath('data.0.phone', '9876543210');
        $this->putJson(route('admin.employees.update', $employee), [
            'name' => $employee->name, 'email' => $employee->email, 'role' => 'sales',
            'phone' => '1234567890', 'project_id' => null,
        ])->assertOk();
        $this->assertNull($employee->fresh()->project_id);
        $this->assertSame('1234567890', $employee->fresh()->phone);
    }

    public function test_non_admin_cannot_access_employee_management(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.employees.index'))->assertForbidden();
    }
}
