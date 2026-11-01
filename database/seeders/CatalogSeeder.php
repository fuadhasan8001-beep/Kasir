<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ProductSpecification;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Sak', 'abbreviation' => 'sak', 'decimal_places' => 0],
            ['name' => 'Batang', 'abbreviation' => 'btg', 'decimal_places' => 0],
            ['name' => 'Biji', 'abbreviation' => 'biji', 'decimal_places' => 0],
            ['name' => 'Pcs', 'abbreviation' => 'pcs', 'decimal_places' => 0],
            ['name' => 'Meter', 'abbreviation' => 'm', 'decimal_places' => 3],
            ['name' => 'Kilogram', 'abbreviation' => 'kg', 'decimal_places' => 3],
            ['name' => 'Roll', 'abbreviation' => 'roll', 'decimal_places' => 0],
            ['name' => 'Buah', 'abbreviation' => 'buah', 'decimal_places' => 0],
        ] as $unit) {
            Unit::updateOrCreate(['abbreviation' => $unit['abbreviation']], $unit + ['is_active' => true]);
        }

        $definitions = [
            'Material Bangunan' => [],
            'Cakar Ayam' => [
                ['name' => 'Panjang', 'type' => 'number', 'unit' => 'cm'],
                ['name' => 'Lebar', 'type' => 'number', 'unit' => 'cm'],
                ['name' => 'Kedalaman', 'type' => 'number', 'unit' => 'cm'],
                ['name' => 'Diameter besi', 'type' => 'number', 'unit' => 'mm'],
                ['name' => 'Berat aktual', 'type' => 'number', 'unit' => 'kg'],
            ],
            'Besi Lonjoran' => [
                ['name' => 'Diameter besi', 'type' => 'number', 'unit' => 'mm'],
                ['name' => 'Panjang batang', 'type' => 'number', 'unit' => 'm'],
                ['name' => 'Berat per batang', 'type' => 'number', 'unit' => 'kg'],
            ],
            'Begel' => [
                ['name' => 'Bentuk', 'type' => 'text', 'unit' => null],
                ['name' => 'Panjang', 'type' => 'number', 'unit' => 'cm'],
                ['name' => 'Lebar', 'type' => 'number', 'unit' => 'cm'],
                ['name' => 'Diameter besi', 'type' => 'number', 'unit' => 'mm'],
                ['name' => 'Berat aktual', 'type' => 'number', 'unit' => 'kg'],
            ],
            'Sloof' => [
                ['name' => 'Panjang', 'type' => 'number', 'unit' => 'm'],
                ['name' => 'Lebar', 'type' => 'number', 'unit' => 'cm'],
                ['name' => 'Tinggi', 'type' => 'number', 'unit' => 'cm'],
                ['name' => 'Jumlah tulangan', 'type' => 'integer', 'unit' => 'batang'],
                ['name' => 'Jarak begel', 'type' => 'number', 'unit' => 'cm'],
                ['name' => 'Berat aktual', 'type' => 'number', 'unit' => 'kg'],
            ],
            'Sloof Plus Sikilan' => [
                ['name' => 'Panjang sloof', 'type' => 'number', 'unit' => 'm'],
                ['name' => 'Ukuran cakar', 'type' => 'text', 'unit' => null],
                ['name' => 'Jumlah kaki', 'type' => 'integer', 'unit' => 'buah'],
                ['name' => 'Berat aktual', 'type' => 'number', 'unit' => 'kg'],
            ],
            'Bendrat dan Kawat' => [
                ['name' => 'Jenis kawat', 'type' => 'text', 'unit' => null],
                ['name' => 'Diameter', 'type' => 'number', 'unit' => 'mm'],
                ['name' => 'Berat aktual', 'type' => 'number', 'unit' => 'kg'],
            ],
            'Wiremesh' => [
                ['name' => 'Panjang', 'type' => 'number', 'unit' => 'm'],
                ['name' => 'Lebar', 'type' => 'number', 'unit' => 'm'],
                ['name' => 'Jarak antar besi', 'type' => 'number', 'unit' => 'cm'],
                ['name' => 'Diameter besi', 'type' => 'number', 'unit' => 'mm'],
                ['name' => 'Berat aktual', 'type' => 'number', 'unit' => 'kg'],
            ],
        ];

        foreach ($definitions as $categoryName => $specifications) {
            $category = Category::updateOrCreate(
                ['name' => $categoryName],
                ['slug' => Str::slug($categoryName), 'is_active' => true],
            );

            foreach ($specifications as $sortOrder => $specification) {
                $key = Str::snake($specification['name']);
                ProductSpecification::updateOrCreate(
                    ['category_id' => $category->id, 'key' => $key],
                    [
                        'name' => $specification['name'],
                        'data_type' => $specification['type'],
                        'measurement_unit' => $specification['unit'],
                        'is_required' => false,
                        'sort_order' => $sortOrder,
                    ],
                );
            }
        }
    }
}
