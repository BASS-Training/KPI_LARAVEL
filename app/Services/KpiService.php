<?php

namespace App\Services;

use App\Events\KPIUpdated;
use App\Models\KpiIndicator;
use App\Models\KpiNotification;
use App\Models\KpiScore;
use App\Models\KpiTarget;
use App\Models\Task;
use App\Models\TaskScore;
use App\Models\User;
use App\Repositories\Contracts\KpiIndicatorRepositoryInterface;
use App\Repositories\Contracts\KpiRecordRepositoryInterface;
use App\Repositories\Contracts\KpiScoreRepositoryInterface;
use App\Repositories\Contracts\KpiTargetRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class KpiService
{
    public function __construct(
        private readonly KpiIndicatorRepositoryInterface $indicatorRepository,
        private readonly KpiTargetRepositoryInterface $targetRepository,
        private readonly KpiRecordRepositoryInterface $recordRepository,
        private readonly KpiScoreRepositoryInterface $scoreRepository,
        private readonly KpiFormulaEngine $formulaEngine = new KpiFormulaEngine(),
    ) {
    }

    public function inputRecord(array $payload): KpiScore
    {
        $notificationData = null;

        $score = DB::transaction(function () use ($payload, &$notificationData) {
            /** @var User $user */
            $user = User::query()->findOrFail($payload['user_id']);

            if (! $user->department_id) {
                throw new InvalidArgumentException('User belum memiliki departemen yang terhubung.');
            }

            $indicator = $this->indicatorRepository->findById((int) $payload['indicator_id']);

            if (! $indicator) {
                throw (new ModelNotFoundException())->setModel('App\\Models\\KpiIndicator', [$payload['indicator_id']]);
            }

            // Validate: indicator must match user's department
            if ($indicator->department_id && $user->department_id) {
                if ((int) $indicator->department_id !== (int) $user->department_id) {
                    throw new InvalidArgumentException('Indicator KPI tidak sesuai dengan departemen user.');
                }
            }

            $period = $this->resolvePeriod($payload['period_type'], $payload['period']);
            $targetValue = round((float) $payload['target_value'], 2);
            $actualValue = round((float) $payload['actual_value'], 2);
            $achievementRatio = $this->calculateAchievementRatio($actualValue, $targetValue);
            $recordScore = round($achievementRatio * (float) $indicator->weight, 2);

            $this->targetRepository->upsert(
                [
                    'indicator_id' => $indicator->id,
                    'period_type' => $period['type'],
                    'period_start' => $period['start']->toDateString(),
                ],
                [
                    'period_end' => $period['end']->toDateString(),
                    'target_value' => $targetValue,
                ]
            );

            $this->recordRepository->upsert(
                [
                    'user_id' => $user->id,
                    'indicator_id' => $indicator->id,
                    'period_type' => $period['type'],
                    'period_start' => $period['start']->toDateString(),
                ],
                [
                    'period_end' => $period['end']->toDateString(),
                    'target_value' => $targetValue,
                    'actual_value' => $actualValue,
                    'achievement_ratio' => $achievementRatio,
                    'score' => $recordScore,
                ]
            );

            // Collect notification data to dispatch outside transaction
            $notificationData = [
                'user' => $user,
                'indicator' => $indicator,
                'actual_value' => $actualValue,
                'target_value' => $targetValue,
                'achievement_ratio' => $achievementRatio,
                'period_label' => $period['start']->translatedFormat('F Y'),
            ];

            return $this->recalculateUserScore($user, $period['type'], $period['start']->toDateString(), $indicator);
        });

        // Dispatch KPI updated notification outside transaction to avoid partial writes
        if ($notificationData) {
            $this->notifyKpiUpdated(
                $notificationData['user'],
                $notificationData['indicator'],
                $notificationData['actual_value'],
                $notificationData['target_value'],
                $notificationData['achievement_ratio'],
                $notificationData['period_label'],
            );
        }

        $this->flushDashboardCaches($score->period_type, optional($score->period_start)->toDateString());

        return $score;
    }

    public function generateMonthlyKPI(?string $period = null): int
    {
        $resolvedPeriod = $this->resolvePeriod('monthly', $period ?? now()->toDateString());
        $users = User::query()
            ->select(['id', 'department_id', 'role', 'nama', 'email', 'jabatan', 'nip'])
            ->whereNotNull('department_id')
            ->get();

        foreach ($users as $user) {
            $indicators = $this->indicatorRepository->getForUser(
                null,
                $user->department_id ? (int) $user->department_id : null
            );

            foreach ($indicators as $indicator) {
                $targetValue = (float) ($indicator->default_target_value ?: config('kpi.default_target_value'));

                $this->targetRepository->upsert(
                    [
                        'indicator_id' => $indicator->id,
                        'period_type' => 'monthly',
                        'period_start' => $resolvedPeriod['start']->toDateString(),
                    ],
                    [
                        'period_end' => $resolvedPeriod['end']->toDateString(),
                        'target_value' => $targetValue,
                    ]
                );

                $this->recordRepository->upsert(
                    [
                        'user_id' => $user->id,
                        'indicator_id' => $indicator->id,
                        'period_type' => 'monthly',
                        'period_start' => $resolvedPeriod['start']->toDateString(),
                    ],
                    [
                        'period_end' => $resolvedPeriod['end']->toDateString(),
                        'target_value' => $targetValue,
                        'actual_value' => 0,
                        'achievement_ratio' => 0,
                        'score' => 0,
                    ]
                );
            }

            $this->recalculateUserScore($user, 'monthly', $resolvedPeriod['start']->toDateString());
        }

        $this->flushDashboardCaches('monthly', $resolvedPeriod['start']->toDateString());

        return $users->count();
    }

    public function recalculateUserScore(
        User $user,
        string $periodType,
        string $periodStart,
        ?KpiIndicator $changedIndicator = null,
        bool $silent = false,
    ): KpiScore {
        $indicators = $user->department_id
            ? $this->indicatorRepository->getForUser(null, (int) $user->department_id)
            : collect();
        $records = $this->recordRepository->getUserRecordsForPeriod($user->id, $periodType, $periodStart);
        $targets = $indicators->isNotEmpty()
            ? $this->targetRepository->getByIndicatorsAndPeriod(
                $indicators->pluck('id')->all(),
                $periodType,
                $periodStart
            )
            : collect();
        $period = $this->resolvePeriod($periodType, $periodStart);
        $taskScores = $this->resolveTaskScores($user, $periodType, $period);

        $indicatorBreakdown = $indicators->map(function (KpiIndicator $indicator) use ($records, $targets) {
            $record = $records->get($indicator->id);
            $target = $targets->get($indicator->id);
            $targetValue      = (float) ($record?->target_value ?? $target?->target_value ?? $indicator->default_target_value ?? 0);
            $actualValue      = (float) ($record?->actual_value ?? 0);
            $indicatorScore   = $this->formulaEngine->evaluate($indicator, $actualValue, $targetValue);
            $achievementRatio = $this->formulaEngine->achievementRatio($indicator, $actualValue, $targetValue);

            return [
                'type' => 'indicator',
                'indicator_id' => $indicator->id,
                'name' => $indicator->name,
                'description' => $indicator->description,
                'weight' => (float) $indicator->weight,
                'target_value' => round($targetValue, 2),
                'actual_value' => round($actualValue, 2),
                'achievement_ratio' => round($achievementRatio * 100, 2),
                'score' => $indicatorScore,
            ];
        })->values()->all();

        $taskBreakdown = $this->mergeTaskWithKPI($taskScores);
        $breakdown = array_values(array_merge($indicatorBreakdown, $taskBreakdown));

        $rawScore = round(collect($indicatorBreakdown)->sum('score') + collect($taskBreakdown)->sum('score'), 2);
        $normalizedScore = round(min($rawScore, 100), 2);
        $status = $this->resolveStatus($normalizedScore);

        $score = $this->scoreRepository->upsert(
            [
                'user_id' => $user->id,
                'period_type' => $periodType,
                'period_start' => $period['start']->toDateString(),
            ],
            [
                'period_end' => $period['end']->toDateString(),
                'raw_score' => $rawScore,
                'normalized_score' => $normalizedScore,
                'status' => $status,
                'grade' => $this->resolveGrade($normalizedScore),
                'breakdown' => $breakdown,
            ]
        )->loadMissing(['user']);

        if (! $silent) {
            $this->notifyLowPerformance($user, $score);
        }

        $this->flushDashboardCaches($periodType, $period['start']->toDateString());

        if (! $silent) {
            event(new KPIUpdated($score, $changedIndicator));
        }

        return $score;
    }

    public function getDashboard(array $filters): array
    {
        $period = $this->resolvePeriod($filters['period_type'], $filters['period']);
        $roleId = $filters['role_id'] ?? null;
        $employeeId = $filters['employee_id'] ?? null;

        $this->ensureScoresForDashboard($period['type'], $period['start']->toDateString(), $roleId, $employeeId);

        return (function () use ($period, $roleId, $employeeId) {
            $scores = $this->scoreRepository->getLeaderboard(
                $period['type'],
                $period['start']->toDateString()
            );

            if ($roleId) {
                $scores = $scores
                    ->filter(fn (KpiScore $score) => (int) $score->user?->position_id === (int) $roleId)
                    ->values();
            }

            if ($employeeId) {
                $scores = $scores
                    ->filter(fn (KpiScore $score) => (int) $score->user_id === (int) $employeeId)
                    ->values();
            }

            $average = round((float) $scores->avg('normalized_score'), 2);
            $topPerformer = $scores->first();
            $lowPerformer = $scores->sortBy('normalized_score')->first();

            return [
                'filters' => [
                    'period_type' => $period['type'],
                    'period' => $period['start']->toDateString(),
                ],
                'summary' => [
                    'average_kpi' => $average,
                    'top_performer' => $topPerformer,
                    'low_performer' => $lowPerformer,
                    'employee_count' => $scores->count(),
                ],
                'ranking' => $this->attachRank($scores),
            ];
        })();
    }

    public function getUserScore(int $userId, array $filters, ?User $actor = null): KpiScore
    {
        $period = $this->resolvePeriod($filters['period_type'], $filters['period']);

        /** @var User $user */
        $user = User::query()
            ->select(['id', 'nip', 'nama', 'jabatan', 'departemen', 'email', 'role', 'department_id', 'position_id'])
            ->findOrFail($userId);

        if ($actor && !$this->canAccessUserKpi($actor, $userId)) {
            throw new InvalidArgumentException('Anda tidak diizinkan melihat KPI user ini.');
        }

        $score = $this->scoreRepository->findUserScore(
            $userId,
            $period['type'],
            $period['start']->toDateString()
        );

        return $score ?: $this->recalculateUserScore($user, $period['type'], $period['start']->toDateString());
    }

    public function getRanking(array $filters): Collection
    {
        return collect($this->getDashboard($filters)['ranking']);
    }

    public function calculateTaskScore(Task $task): float
    {
        $weight = max(0, (float) $task->weight);

        if ($weight <= 0) {
            return 0;
        }

        if ($task->target_value !== null && (float) $task->target_value > 0) {
            $ratio = $this->calculateAchievementRatio((float) ($task->actual_value ?? 0), (float) $task->target_value);

            return round(min($ratio * $weight, $weight), 2);
        }

        return round(match ($task->status_code) {
            Task::STATUS_DONE => $weight,
            Task::STATUS_ON_PROGRESS => $weight * 0.5,
            default => 0,
        }, 2);
    }

    public function mergeTaskWithKPI(Collection $taskScores): array
    {
        return $taskScores
            ->filter(fn (TaskScore $taskScore) => $taskScore->task)
            ->map(function (TaskScore $taskScore) {
                $task = $taskScore->task;

                return [
                    'type' => 'task',
                    'indicator_id' => null,
                    'task_id' => $task->id,
                    'kpi_component_id' => $task->kpi_component_id,
                    'kpi_component' => $task->kpiComponent ? [
                        'id' => $task->kpiComponent->id,
                        'objectives' => $task->kpiComponent->objectives,
                        'tipe' => $task->kpiComponent->tipe,
                    ] : null,
                    'name' => $task->judul,
                    'description' => $task->deskripsi,
                    'weight' => $task->weight !== null ? (float) $task->weight : null,
                    'target_value' => $task->target_value !== null ? (float) $task->target_value : null,
                    'actual_value' => $task->actual_value !== null ? (float) $task->actual_value : null,
                    'achievement_ratio' => $task->target_value
                        ? round($this->calculateAchievementRatio((float) ($task->actual_value ?? 0), (float) $task->target_value) * 100, 2)
                        : match ($task->status_code) {
                            Task::STATUS_DONE => 100,
                            Task::STATUS_ON_PROGRESS => 50,
                            default => 0,
                        },
                    'score' => (float) $taskScore->score,
                    'status' => $task->status_code,
                    'status_label' => $task->status_label,
                    'period' => $taskScore->period,
                    'start_date' => optional($task->start_date)->toDateString(),
                    'end_date' => optional($task->end_date)->toDateString(),
                ];
            })
            ->values()
            ->all();
    }

    public function canAccessUserKpi(User $actor, int $targetUserId): bool
    {
        if ($actor->id === $targetUserId) {
            return true;
        }

        return collect(['admin', 'hr_manager', 'direktur'])
            ->contains(fn (string $role) => $actor->hasKpiRole($role));
    }

    public function getTrend(int $months = 6, ?string $period = null): array
    {
        $current = $this->resolvePeriod('monthly', $period ?? now()->toDateString())['start'];

        return collect(range($months - 1, 0))
            ->map(function (int $offset) use ($current) {
                $point = $current->subMonths($offset);
                $dashboard = $this->getDashboard([
                    'period_type' => 'monthly',
                    'period' => $point->toDateString(),
                ]);

                return [
                    'period' => $point->toDateString(),
                    'label' => $point->translatedFormat('M Y'),
                    'average_kpi' => $dashboard['summary']['average_kpi'],
                    'employee_count' => $dashboard['summary']['employee_count'],
                ];
            })
            ->values()
            ->all();
    }

    public function buildTeamPdfData(array $filters): array
    {
        $dashboard = $this->getDashboard($filters);
        $period = $this->resolvePeriod($filters['period_type'], $filters['period']);
        return [
            'company' => config('kpi.company_name'),
            'generated_at' => now(),
            'period_label' => $period['start']->translatedFormat('F Y'),
            'summary' => $dashboard['summary'],
            'ranking' => $dashboard['ranking'],
        ];
    }

    private function attachRank(Collection $scores): Collection
    {
        return $scores->values()->map(function (KpiScore $score, int $index) {
            $score->setAttribute('rank', $index + 1);

            return $score;
        });
    }

    private function ensureScoresForDashboard(
        string $periodType,
        string $periodStart,
        ?int $roleId = null,
        ?int $employeeId = null,
    ): void {
        $users = User::query()
            ->select(['id', 'nip', 'nama', 'jabatan', 'departemen', 'email', 'role', 'department_id', 'position_id'])
            ->where('role', 'pegawai')
            ->when($roleId, fn ($query) => $query->where('position_id', $roleId))
            ->when($employeeId, fn ($query) => $query->whereKey($employeeId))
            ->get();

        foreach ($users as $user) {
            if ($this->scoreRepository->findUserScore($user->id, $periodType, $periodStart)) {
                continue;
            }

            $this->recalculateUserScore($user, $periodType, $periodStart, silent: true);
        }
    }

    private function notifyLowPerformance(User $user, KpiScore $score): void
    {
        $threshold = (float) config('kpi.low_performance_threshold', 60);

        if ((float) $score->normalized_score >= $threshold) {
            return;
        }

        // Deduplication: only send one low-performance notification per period per user
        $periodStart = optional($score->period_start);
        $month = $periodStart->month ?? now()->month;
        $year  = $periodStart->year ?? now()->year;

        $alreadyNotified = KpiNotification::query()
            ->where('user_id', $user->id)
            ->where('type', 'low_performance')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        KpiNotification::create([
            'user_id' => $user->id,
            'type'    => 'low_performance',
            'title'   => 'Peringatan: KPI Di Bawah Standar',
            'body'    => sprintf(
                'Skor KPI Anda %.2f berada di bawah ambang batas %.0f. Tinjau indikator dengan pencapaian terendah dan susun rencana perbaikan bersama atasan.',
                (float) $score->normalized_score,
                $threshold
            ),
            'payload' => [
                'score'        => (float) $score->normalized_score,
                'threshold'    => $threshold,
                'status'       => $score->status,
                'grade'        => $score->grade,
                'period_start' => $periodStart->toDateString(),
                'period_type'  => $score->period_type,
            ],
        ]);
    }

    private function notifyKpiUpdated(
        User $user,
        KpiIndicator $indicator,
        float $actualValue,
        float $targetValue,
        float $achievementRatio,
        string $periodLabel,
    ): void {
        KpiNotification::create([
            'user_id' => $user->id,
            'type'    => 'kpi_updated',
            'title'   => 'Data KPI Anda Diperbarui',
            'body'    => sprintf(
                'HR telah memperbarui KPI "%s" untuk periode %s. Aktual: %s, Target: %s, Pencapaian: %.1f%%.',
                $indicator->name,
                $periodLabel,
                $actualValue,
                $targetValue,
                $achievementRatio * 100
            ),
            'payload' => [
                'indicator_id'      => $indicator->id,
                'indicator_name'    => $indicator->name,
                'actual_value'      => $actualValue,
                'target_value'      => $targetValue,
                'achievement_ratio' => round($achievementRatio * 100, 2),
            ],
        ]);
    }

    private function resolveTaskScores(User $user, string $periodType, array $period): Collection
    {
        $query = TaskScore::query()
            ->with('task.kpiComponent')
            ->where('user_id', $user->id);

        if ($periodType === 'monthly') {
            $query->where('period', $period['start']->format('Y-m'));
        } else {
            $taskIds = Task::query()
                ->where('assigned_to', $user->id)
                ->whereBetween('end_date', [$period['start']->toDateString(), $period['end']->toDateString()])
                ->pluck('id');

            $query->whereIn('task_id', $taskIds);
        }

        return $query->get();
    }

    private function calculateAchievementRatio(float $actualValue, float $targetValue): float
    {
        if ($targetValue <= 0) {
            return 0;
        }

        return round(min($actualValue / $targetValue, 1), 4);
    }

    private function resolveGrade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'E',
        };
    }

    private function resolveStatus(float $score): string
    {
        return match (true) {
            $score >= 80 => 'good',
            $score >= 60 => 'average',
            default => 'bad',
        };
    }

    private function resolvePeriod(string $periodType, string $period): array
    {
        $date = CarbonImmutable::parse($period);

        return match ($periodType) {
            'weekly' => [
                'type' => 'weekly',
                'start' => $date->startOfWeek(),
                'end' => $date->endOfWeek(),
            ],
            'monthly' => [
                'type' => 'monthly',
                'start' => $date->startOfMonth(),
                'end' => $date->endOfMonth(),
            ],
            default => throw new InvalidArgumentException('Period type tidak valid.'),
        };
    }

    private function dashboardCacheKey(
        string $periodType,
        string $periodStart,
        ?int $roleId,
        ?int $employeeId = null
    ): string
    {
        return sprintf(
            'kpi.dashboard.%s.%s.%s.%s',
            $periodType,
            $periodStart,
            $roleId ?? 'all',
            $employeeId ?? 'all'
        );
    }

    private function flushDashboardCaches(string $periodType, ?string $periodStart = null): void
    {
        $start = $periodStart ?? now()->startOfMonth()->toDateString();

        Cache::forget($this->dashboardCacheKey($periodType, $start, null));
    }
}
