<?php

namespace App\Models;

use Database\Factories\ProductSpecificationValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpecificationValue extends Model
{
    /** @use HasFactory<ProductSpecificationValueFactory> */
    use HasFactory;

    protected $fillable = ['product_id', 'product_specification_id', 'value'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function specification(): BelongsTo
    {
        return $this->belongsTo(ProductSpecification::class, 'product_specification_id');
    }
}
