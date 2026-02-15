<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class DataAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_type',
        'severity',
        'entity_type',
        'entity_id',
        'details',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeBySeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('audit_type', $type);
    }
}
