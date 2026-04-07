<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiComponent extends Model
{
    protected $fillable = [
        'jabatan',
        'division_id',
        'position_id',
        'objectives',
        'strategy',
        'bobot',
        'target',
        'satuan',
        'tipe',
        'kpi_type',
        'period',
        'catatan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bobot' => 'decimal:2',
            'target' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function positionRef(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function kpiReports(): HasMany
    {
        return $this->hasMany(KpiReport::class);
    }

    public function getObjectiveAttribute(): string
    {
        return $this->objectives;
    }

    public function getWeightAttribute(): float
    {
        return (float) $this->bobot;
    }

    public function getTypeAttribute(): string
    {
        return $this->tipe;
    }

    public function getNoteAttribute(): ?string
    {
        return $this->catatan;
    }

    public function getPositionAttribute(): string
    {
        return $this->jabatan;
    }
}
