<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'category',
        'brand',
        'vendor_id',
        'cost',
        'msrp',
        'retail_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'msrp' => 'decimal:2',
            'retail_price' => 'decimal:2',
        ];
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(ProductAsset::class);
    }
}
