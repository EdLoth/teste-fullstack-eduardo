<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'email',
        'phone',
        'city',
        'state',
        'zipcode',
        'status',
    ];

    // Um afiliado tem muitos pedidos
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}