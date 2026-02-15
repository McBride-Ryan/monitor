<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttributeNormalization extends Model
{
    use HasFactory;

    protected $fillable = [
        'attribute_type',
        'raw_value',
        'normalized_value',
    ];

    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('attribute_type', $type);
    }
}
