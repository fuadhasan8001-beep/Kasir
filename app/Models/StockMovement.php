<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'sale_id', 'custom_order_id', 'movement_type', 'quantity', 'balance_after',
        'inventory_type', 'reference', 'notes',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'balance_after' => 'decimal:3'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customOrder(): BelongsTo
    {
        return $this->belongsTo(CustomOrder::class);
    }
}
