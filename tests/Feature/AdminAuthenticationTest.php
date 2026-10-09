<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_redirect_guests_to_login(): void
    {
        foreach ([
            '/dashboard',
            '/lead-managment/',
            '/projects/',
            '/targets/',
            '/notifications/',
            '/admin/users',
            '/admin/roles',
            '/admin/permissions',
            '/admin/settings',
        ] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_guest_cannot_update_admin_settings(): void
    {
        $this->post('/admin/settings', [
            'maintenance_mode' => true,
            'maintenance_message' => 'Maintenance',
        ])->assertRedirect(route('login'));
    }
}
