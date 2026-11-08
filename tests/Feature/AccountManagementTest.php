<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_and_update_their_profile(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Profil akun')
            ->assertSee('Admin');

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

    public function test_user_must_provide_the_current_password_to_change_it(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->userWithRole('admin', ['password' => 'original-password']);

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

    public function test_admin_can_create_update_and_delete_cashier_accounts(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->userWithRole('admin');
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $cashierRole = Role::where('name', 'kasir')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('cashiers.index'))
            ->assertOk()
            ->assertSee('Tambah akun kasir');

        $this->post(route('cashiers.store'), [
            'name' => 'Kasir Baru',
            'email' => 'kasir@example.test',
            'password' => 'cashier-password',
            'password_confirmation' => 'cashier-password',
            'role_id' => $adminRole->id,
        ])->assertRedirect(route('cashiers.index'));

        $cashier = User::where('email', 'kasir@example.test')->firstOrFail();
        $this->assertSame($cashierRole->id, $cashier->role_id);

        $this->put(route('cashiers.update', $cashier), [
            'name' => 'Kasir Toko',
            'email' => 'kasir-toko@example.test',
        ])->assertRedirect(route('cashiers.index'));

        $this->assertDatabaseHas('users', [
            'id' => $cashier->id,
            'name' => 'Kasir Toko',
            'email' => 'kasir-toko@example.test',
            'role_id' => $cashierRole->id,
        ]);

        $this->delete(route('cashiers.destroy', $cashier))
            ->assertRedirect(route('cashiers.index'));

        $this->assertDatabaseMissing('users', ['id' => $cashier->id]);
    }

    public function test_users_without_management_permission_cannot_manage_cashiers(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $cashier = $this->userWithRole('kasir');

        $this->actingAs($cashier)
            ->get(route('cashiers.index'))
            ->assertForbidden();
    }

    public function test_non_cashier_accounts_cannot_be_changed_or_deleted_through_cashier_routes(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('cashiers.edit', $admin))
            ->assertNotFound();

        $this->delete(route('cashiers.destroy', $admin))
            ->assertNotFound();

        $this->assertModelExists($admin);
    }

    public function test_cashier_with_transaction_history_cannot_be_deleted(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->userWithRole('admin');
        $cashier = $this->userWithRole('kasir');
        Sale::create([
            'invoice_number' => 'INV-TEST-001',
            'request_key' => (string) Str::uuid(),
            'user_id' => $cashier->id,
            'subtotal' => 100,
            'total' => 100,
            'payment_method' => 'cash',
            'amount_received' => 100,
        ]);

        $this->actingAs($admin)
            ->delete(route('cashiers.destroy', $cashier))
            ->assertSessionHasErrors('cashier');

        $this->assertModelExists($cashier);
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();

        return User::factory()->create([...$attributes, 'role_id' => $role->id]);
    }
}
