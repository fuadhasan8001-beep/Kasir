<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomOrderStatusHistory extends Model
{
    protected $fillable = ['custom_order_id', 'user_id', 'from_status', 'to_status', 'reason'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomOrder::class, 'custom_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
