<?php

namespace App\Models;

use Database\Factories\ProductSpecificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSpecification extends Model
{
    /** @use HasFactory<ProductSpecificationFactory> */
    use HasFactory;

    protected $fillable = ['category_id', 'name', 'key', 'data_type', 'measurement_unit', 'is_required', 'sort_order'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'sort_order' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductSpecificationValue::class);
    }
}
