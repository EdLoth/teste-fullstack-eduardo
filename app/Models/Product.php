<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'external_id',
        'title',
        'price',
        'description',
        'category',
        'image_url',
    ];

    // Um produto aparece em muitos itens de pedido
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}