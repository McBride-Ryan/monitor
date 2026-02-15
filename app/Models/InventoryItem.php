<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'warehouse_location',
        'qty_on_hand',
        'qty_committed',
        'ecommerce_status',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'qty_on_hand' => 'integer',
            'qty_committed' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
