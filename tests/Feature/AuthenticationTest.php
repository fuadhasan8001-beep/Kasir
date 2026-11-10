<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_login_page_renders_with_the_built_stylesheets(): void
    {
        $this->get('/login')->assertOk()->assertSee('Selamat datang');
    }

    public function test_user_can_log_in_and_view_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertRedirect(route('dashboard'));

        $this->get('/')->assertOk()->assertSee('Ringkasan toko');
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->from('/login')->post('/login', ['email' => 'missing@example.test', 'password' => 'wrong'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
    }

    public function test_authenticated_admin_can_access_all_operational_areas(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        foreach (['dashboard', 'sales.create', 'orders.custom', 'sales.index', 'inventory.index', 'expenses.index', 'products.index', 'categories.index', 'units.index', 'profile.edit'] as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->get(route('dashboard'))
            ->assertSee('Admin (Kasir)')
            ->assertSee('Pesanan custom')
            ->assertSee('Inventaris')
            ->assertDontSee('Kelola kasir');
    }
}
