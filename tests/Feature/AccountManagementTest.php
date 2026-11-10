<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_their_account_details(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Profil akun')
            ->assertSee('Admin (Kasir)');

        $this->put(route('profile.update'), [
            'name' => 'Admin Toko',
            'email' => 'admin-toko@example.test',
        ])->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Admin Toko',
            'email' => 'admin-toko@example.test',
        ]);
    }

    public function test_admin_must_provide_the_current_password_to_change_it(): void
    {
        $admin = User::factory()->create(['password' => 'original-password']);

        $this->actingAs($admin)
            ->put(route('profile.password'), [
                'current_password' => 'incorrect-password',
                'password' => 'replacement-password',
                'password_confirmation' => 'replacement-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->put(route('profile.password'), [
            'current_password' => 'original-password',
            'password' => 'replacement-password',
            'password_confirmation' => 'replacement-password',
        ])->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('replacement-password', $admin->fresh()->password));
    }

    public function test_cashier_account_management_routes_are_removed(): void
    {
        $this->assertFalse(collect(app('router')->getRoutes()->getRoutes())->contains(
            fn ($route): bool => str_starts_with($route->getName() ?? '', 'cashiers.'),
        ));

        $this->assertDatabaseHas('users', ['id' => User::factory()->create()->id]);
        $this->assertFalse(Schema::hasTable('roles'));
        $this->assertFalse(Schema::hasTable('permissions'));
        $this->assertFalse(Schema::hasColumn('users', 'role_id'));
    }
}
