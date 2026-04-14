<?php

namespace App\Models;

use App\Services\KpiCalculatorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'nip',
        'nama',
        'jabatan',
        'departemen',
        'division_id',
        'department_id',
        'position_id',
        'status_karyawan',
        'tanggal_masuk',
        'no_hp',
        'email',
        'role',
        'role_id',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
            'password' => 'hashed',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function roleRef(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function positionRef(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_by');
    }

    public function mappedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'mapped_by');
    }

    public function taskScores(): HasMany
    {
        return $this->hasMany(TaskScore::class);
    }

    public function kpiReports(): HasMany
    {
        return $this->hasMany(KpiReport::class);
    }

    public function kpiNotifications(): HasMany
    {
        return $this->hasMany(KpiNotification::class);
    }

    public function hasKpiRole(string $role): bool
    {
        if (method_exists($this, 'hasRole')) {
            return $this->hasRole($role);
        }

        if ($this->role === $role) {
            return true;
        }

        return $this->roleRef?->slug === $role;
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isPegawai(): bool
    {
        return $this->role === 'pegawai';
    }

    public function isHrManager(): bool
    {
        return $this->role === 'hr_manager';
    }

    public function isDirektur(): bool
    {
        return $this->role === 'direktur';
    }

    public function canManageAllData(): bool
    {
        return $this->isHrManager() || $this->isDirektur();
    }

    public function isHR(): bool
    {
        return $this->canManageAllData();
    }

    public function getNameAttribute(): string
    {
        return $this->nama;
    }

    public function getPositionAttribute(): string
    {
        return $this->jabatan;
    }

    public function getDepartmentAttribute(): string
    {
        return $this->departemen;
    }

    public function calculateKpiScore(?int $month = null, ?int $year = null): object
    {
        $result = app(KpiCalculatorService::class)->calculateForUser($this, $month, $year);

        $components = collect($result['components'])->map(function (array $component) {
            return (object) [
                'component' => (object) [
                    'objective' => $component['objectives'],
                    'weight' => $component['bobot'],
                    'strategy' => $component['strategy'],
                    'type' => $component['tipe'],
                    'target' => $component['target'],
                ],
                'skor' => $component['skor'],
                'bobotSkor' => $component['nilai_bobot'],
                'linkedCount' => $component['jumlah_task'],
            ];
        })->all();

        return (object) [
            'total' => $result['total'],
            'predikat' => $result['predikat'],
            'components' => $components,
        ];
    }
}
