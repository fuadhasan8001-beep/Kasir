<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomOrderPayment extends Model
{
    protected $fillable = [
        'custom_order_id', 'user_id', 'request_key', 'amount', 'amount_received', 'change_amount',
        'balance_before', 'balance_after', 'payment_method', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_received' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomOrder::class, 'custom_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
