<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    public const TYPE_LEGACY = 'legacy';
    public const TYPE_MANUAL_ASSIGNMENT = 'manual_assignment';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ON_PROGRESS = 'on_progress';
    public const STATUS_DONE = 'done';

    protected $fillable = [
        'task_type',
        'user_id',
        'assigned_by',
        'assigned_to',
        'tanggal',
        'start_date',
        'end_date',
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
        'weight',
        'target_value',
        'actual_value',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'ada_delay' => 'boolean',
            'ada_error' => 'boolean',
            'ada_komplain' => 'boolean',
            'manual_score' => 'decimal:2',
            'mapped_at' => 'datetime',
            'weight' => 'decimal:2',
            'target_value' => 'decimal:2',
            'actual_value' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function kpiComponent(): BelongsTo
    {
        return $this->belongsTo(KpiComponent::class);
    }

    public function mapper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mapped_by');
    }

    public function taskScores(): HasMany
    {
        return $this->hasMany(TaskScore::class);
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

    public function getDescriptionAttribute(): ?string
    {
        return $this->deskripsi;
    }

    public function getAssignedToUserIdAttribute(): ?int
    {
        return $this->assigned_to ?: $this->user_id;
    }

    public function getStatusCodeAttribute(): string
    {
        return self::normalizeStatus($this->attributes['status'] ?? null);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabel($this->status_code);
    }

    public function getTaskPeriodAttribute(): string
    {
        return $this->resolvePeriodDate()->format('Y-m');
    }

    public function isManualAssignment(): bool
    {
        return $this->task_type === self::TYPE_MANUAL_ASSIGNMENT;
    }

    public function resolvePeriodDate(): CarbonInterface
    {
        return $this->end_date ?: $this->start_date ?: $this->tanggal ?: now();
    }

    public static function normalizeStatus(?string $status): string
    {
        return match ($status) {
            self::STATUS_DONE, 'Selesai' => self::STATUS_DONE,
            self::STATUS_ON_PROGRESS, 'Dalam Proses' => self::STATUS_ON_PROGRESS,
            default => self::STATUS_PENDING,
        };
    }

    public static function statusLabel(?string $status): string
    {
        return match (self::normalizeStatus($status)) {
            self::STATUS_DONE => 'Selesai',
            self::STATUS_ON_PROGRESS => 'Dalam Proses',
            default => 'Pending',
        };
    }

    public static function statusForStorage(?string $status): string
    {
        return self::statusLabel($status);
    }
}
