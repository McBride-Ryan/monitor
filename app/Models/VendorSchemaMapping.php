<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorSchemaMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_name',
        'vendor_column',
        'erp_column',
        'transform_rule',
    ];

    protected $casts = [
        'transform_rule' => 'array',
    ];

    public function scopeForVendor(Builder $query, string $vendor): Builder
    {
        return $query->where('vendor_name', $vendor);
    }
}
