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
        $this->get('/login')->assertOk()->assertSee('Masuk')->assertSee('Gunawan Jaya Steel');
    }

    public function test_user_can_log_in_and_view_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertRedirect(route('kasir'));

        $this->get(route('dashboard'))->assertOk()->assertSee('Ringkasan aktivitas Gunawan Jaya Steel');
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

        $dashboard = $this->get(route('dashboard'))
            ->assertSee('Administrator')
            ->assertSee('Riwayat Penjualan')
            ->assertSee('Data Barang')
            ->assertSee('Kategori')
            ->assertSee('Satuan')
            ->assertSee('Inventaris')
            ->assertSee('Pengeluaran')
            ->assertSee('Laporan')
            ->assertSee('Akun Saya')
            ->assertSee('Ubah Password')
            ->assertSee('Logout')
            ->assertSee('images/logo-gjs.png')
            ->assertDontSee('gjs-logo.svg')
            ->assertDontSee('Kelola kasir');

        $this->assertDoesNotMatchRegularExpression('/<a class="nav-item[^\"]*" href="[^\"]*orders\/custom"/', $dashboard->getContent());
        $cashierPage = $this->get(route('kasir'))->assertOk();
        $this->assertStringNotContainsString('href="'.route('orders.custom').'"', $cashierPage->getContent());
    }
}
