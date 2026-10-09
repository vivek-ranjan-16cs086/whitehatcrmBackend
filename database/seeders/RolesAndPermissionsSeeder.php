<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // The super-admin role is reserved; other roles are configured in the admin panel.
        $roles = ['super-admin'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $this->command->info('Roles created: ' . implode(', ', $roles));

        // Sync permissions from models
        Artisan::call('permission:sync');
        $this->command->info('Permissions synced from models.');

        // Create default super admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@crm.test'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );

        $superAdmin->syncRoles(['super-admin']);

        $this->command->info('');
        $this->command->info('=== Super Admin Login Credentials ===');
        $this->command->info('URL:      http://localhost:8000/login');
        $this->command->info('Email:    superadmin@crm.test');
        $this->command->info('Password: password');
        $this->command->info('=====================================');
    }
}
