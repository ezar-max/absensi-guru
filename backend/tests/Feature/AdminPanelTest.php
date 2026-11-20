<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin_test@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);
    }

    public function test_secretary_cannot_access_admin_panel(): void
    {
        $secretary = User::create([
            'name' => 'Secretary Test',
            'email' => 'sec_test@test.com',
            'password' => bcrypt('password'),
            'role' => 'secretary',
            'is_active' => true,
        ]);

        $response = $this->actingAs($secretary)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_inactive_admin_cannot_access_admin_panel(): void
    {
        $inactiveAdmin = User::create([
            'name' => 'Inactive Admin',
            'email' => 'inactive_admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => false,
        ]);

        $response = $this->actingAs($inactiveAdmin)->get('/admin');
        $response->assertStatus(403);
    }
}

