<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'timestamp',
        'amount',
        'description',
        'account_type',
        'order_origin',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(TransactionLog::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }
}
