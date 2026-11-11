<?php

namespace App\Models;

use Database\Factories\CustomOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomOrderItem extends Model
{
    /** @use HasFactory<CustomOrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'custom_order_id', 'product_id', 'product_name', 'quantity', 'stock_basis', 'stock_unit',
        'estimated_weight', 'actual_weight', 'price_basis', 'agreed_price', 'total_price',
        'specifications', 'diameter', 'shape', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'estimated_weight' => 'decimal:3',
            'actual_weight' => 'decimal:3',
            'agreed_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'specifications' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomOrder::class, 'custom_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
