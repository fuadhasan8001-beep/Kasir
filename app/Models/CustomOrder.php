<?php

namespace App\Models;

use Database\Factories\CustomOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomOrder extends Model
{
    /** @use HasFactory<CustomOrderFactory> */
    use HasFactory;

    protected $fillable = [
        'order_number', 'request_key', 'created_by', 'product_id', 'product_name', 'customer_name', 'customer_phone',
        'order_date', 'specifications', 'diameter', 'shape', 'quantity', 'stock_basis', 'stock_unit', 'estimated_weight', 'actual_weight',
        'price_basis', 'agreed_price', 'total_price', 'estimated_completion_date', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'estimated_completion_date' => 'date',
            'specifications' => 'array',
            'quantity' => 'decimal:3',
            'estimated_weight' => 'decimal:3',
            'actual_weight' => 'decimal:3',
            'agreed_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomOrderPayment::class)->orderBy('paid_at')->orderBy('id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomOrderItem::class)->orderBy('id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(CustomOrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->orderBy('created_at')->orderBy('id');
    }
}
