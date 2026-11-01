<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_user_can_log_in_and_view_dashboard(): void
    {
        $role = Role::create(['name' => 'admin', 'label' => 'Admin']);
        $user = User::factory()->create(['role_id' => $role->id, 'password' => 'secret-password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertRedirect(route('dashboard'));

        $this->get('/')->assertOk()->assertSee('Ringkasan toko');
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->from('/login')->post('/login', ['email' => 'missing@example.test', 'password' => 'wrong'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
    }

    public function test_permission_middleware_rejects_users_without_the_permission(): void
    {
        $role = Role::create(['name' => 'kasir', 'label' => 'Kasir']);
        $user = User::factory()->create(['role_id' => $role->id]);

        Route::middleware(['web', 'auth', 'permission:inventory.manage'])
            ->get('/permission-probe', fn () => response('allowed'));

        $this->actingAs($user)->get('/permission-probe')->assertForbidden();
    }
}
