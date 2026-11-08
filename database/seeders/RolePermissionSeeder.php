<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect([
            'dashboard.view' => 'Lihat dashboard',
            'sales.create' => 'Buat transaksi penjualan',
            'sales.view_all' => 'Lihat semua transaksi',
            'inventory.manage' => 'Kelola barang dan stok',
            'purchases.manage' => 'Kelola pembelian',
            'reports.view' => 'Lihat laporan',
            'users.manage' => 'Kelola pengguna',
            'finance.manage' => 'Kelola keuangan',
            'catalog.view' => 'Lihat katalog barang',
            'catalog.manage' => 'Kelola katalog barang',
        ])->mapWithKeys(fn ($label, $name) => [$name => Permission::updateOrCreate(['name' => $name], ['label' => $label])]);

        $grants = [
            'admin' => $permissions->keys()->all(),
            'kasir' => ['dashboard.view', 'sales.create', 'catalog.view', 'inventory.manage'],
            'pemilik' => $permissions->keys()->all(),
        ];

        foreach ($grants as $name => $allowed) {
            $role = Role::updateOrCreate(['name' => $name], ['label' => ucfirst($name)]);
            $role->permissions()->sync($permissions->only($allowed)->pluck('id'));
        }
    }
}
