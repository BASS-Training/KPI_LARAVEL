<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class KpiReport extends Model
{
    protected $fillable = [
        'user_id',
        'kpi_component_id',
        'period_type',
        'tanggal',
        'period_label',
        'nilai_target',
        'nilai_aktual',
        'persentase',
        'score_label',
        'catatan',
        'file_evidence',
        'status',
        'submitted_by',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nilai_target' => 'decimal:4',
            'nilai_aktual' => 'decimal:4',
            'persentase' => 'decimal:2',
            'submitted_at' => 'datetime',
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

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function getFileEvidenceUrlAttribute(): ?string
    {
        if (! $this->file_evidence) {
            return null;
        }

        return Storage::disk('public')->url($this->file_evidence);
    }

    public function calculatePercentage(): float
    {
        if (! $this->nilai_target || $this->nilai_target == 0) {
            return $this->nilai_aktual > 0 ? 100.0 : 0.0;
        }

        return round(((float) $this->nilai_aktual / (float) $this->nilai_target) * 100, 2);
    }

    public static function resolveScoreLabel(float $percentage): string
    {
        if ($percentage > 100) {
            return 'excellent';
        }

        if ($percentage >= 80) {
            return 'good';
        }

        if ($percentage >= 50) {
            return 'average';
        }

        return 'bad';
    }
}
