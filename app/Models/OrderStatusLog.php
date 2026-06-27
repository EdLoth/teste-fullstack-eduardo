<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusLog extends Model
{
    protected $fillable = [
        'order_id',
        'previous_status',
        'new_status',
        'changed_by',
        'reason',
    ];

    // Um log pertence a um pedido
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}