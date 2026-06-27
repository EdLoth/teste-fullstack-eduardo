<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'external_id',
        'affiliate_id',
        'status',
        'total',
    ];

    // Constantes da máquina de estados
    const STATUS_PENDING   = 'pending';
    const STATUS_APPROVED  = 'approved';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED  = 'refunded';

    // Transições válidas
    const VALID_TRANSITIONS = [
        self::STATUS_PENDING  => [self::STATUS_APPROVED, self::STATUS_CANCELLED],
        self::STATUS_APPROVED => [self::STATUS_REFUNDED],
    ];

    // Um pedido pertence a um afiliado
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    // Um pedido tem muitos itens
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Um pedido tem muitos logs de status
    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class);
    }

    // Verifica se uma transição de status é válida
    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::VALID_TRANSITIONS[$this->status] ?? []);
    }
}