<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku', 'barcode', 'name', 'category_id', 'brand_id', 'unit_id', 'product_type',
        'pricing_method', 'description', 'image_path', 'purchase_price', 'selling_price', 'minimum_stock', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'minimum_stock' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'product_units')->withPivot(['conversion_factor', 'is_base'])->withTimestamps();
    }

    public function specificationValues(): HasMany
    {
        return $this->hasMany(ProductSpecificationValue::class);
    }

    public function inventoryLevels(): HasMany
    {
        return $this->hasMany(InventoryLevel::class);
    }
}
