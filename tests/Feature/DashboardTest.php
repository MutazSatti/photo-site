<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_the_legacy_dashboard_path_redirects_to_the_admin_panel(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard'))->assertRedirect('/admin/dashboard');
    }

    public function test_authenticated_users_can_visit_the_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('لوحة المعلومات');
    }

    public function test_guests_cannot_reach_the_admin_panel(): void
    {
        $this->get(route('admin.posts'))->assertRedirect(route('login'));
        $this->get(route('admin.settings'))->assertRedirect(route('login'));
    }
}
