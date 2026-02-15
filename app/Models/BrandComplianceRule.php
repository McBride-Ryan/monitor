<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrandComplianceRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand',
        'rule_type',
        'rule_config',
        'is_active',
    ];

    protected $casts = [
        'rule_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForBrand(Builder $query, string $brand): Builder
    {
        return $query->where('brand', $brand);
    }
}
