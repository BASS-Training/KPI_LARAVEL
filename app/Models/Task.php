<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = [
        'user_id',
        'tanggal',
        'judul',
        'jenis_pekerjaan',
        'status',
        'waktu_mulai',
        'waktu_selesai',
        'ada_delay',
        'ada_error',
        'ada_komplain',
        'deskripsi',
        'kpi_component_id',
        'manual_score',
        'mapped_by',
        'mapped_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'ada_delay' => 'boolean',
            'ada_error' => 'boolean',
            'ada_komplain' => 'boolean',
            'manual_score' => 'decimal:2',
            'mapped_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kpiComponent(): BelongsTo
    {
        return $this->belongsTo(KpiComponent::class);
    }

    public function mapper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mapped_by');
    }

    public function getTaskDateAttribute()
    {
        return $this->tanggal;
    }

    public function getTitleAttribute(): string
    {
        return $this->judul;
    }

    public function getTypeAttribute(): string
    {
        return $this->jenis_pekerjaan;
    }

    public function getHasDelayAttribute(): bool
    {
        return (bool) $this->ada_delay;
    }

    public function getHasErrorAttribute(): bool
    {
        return (bool) $this->ada_error;
    }

    public function getHasComplaintAttribute(): bool
    {
        return (bool) $this->ada_komplain;
    }
}
