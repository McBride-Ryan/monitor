<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    protected $fillable = [
        'transaction_id',
        'carrier',
        'tracking_number',
        'status',
        'estimated_delivery',
    ];

    protected $casts = [
        'estimated_delivery' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ShipmentLog::class);
    }
}
