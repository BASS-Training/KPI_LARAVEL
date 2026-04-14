<?php

namespace App\Services;

use App\Events\KPIUpdated;
use App\Models\KpiIndicator;
use App\Models\KpiScore;
use App\Models\KpiTarget;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskScore;
use App\Models\User;
use App\Notifications\KpiLowNotification;
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
        $score = DB::transaction(function () use ($payload) {
            /** @var User $user */
            $user = User::query()->with('roleRef')->findOrFail($payload['user_id']);

            if (!$user->role_id) {
                throw new InvalidArgumentException('User belum memiliki role yang terhubung.');
            }

            $indicator = $this->indicatorRepository->findById((int) $payload['indicator_id']);

            if (!$indicator) {
                throw (new ModelNotFoundException())->setModel('App\\Models\\KpiIndicator', [$payload['indicator_id']]);
            }

            if ((int) $indicator->role_id !== (int) $user->role_id) {
                throw new InvalidArgumentException('Indicator KPI tidak sesuai dengan role user.');
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

            return $this->recalculateUserScore($user, $period['type'], $period['start']->toDateString(), $indicator);
        });

        $this->flushDashboardCaches($score->period_type, optional($score->period_start)->toDateString());

        return $score;
    }

    public function generateMonthlyKPI(?string $period = null): int
    {
        $resolvedPeriod = $this->resolvePeriod('monthly', $period ?? now()->toDateString());
        $users = User::query()
            ->select(['id', 'role_id', 'role', 'nama', 'email', 'jabatan', 'nip'])
            ->with(['roleRef:id,name,slug'])
            ->whereNotNull('role_id')
            ->get();

        foreach ($users as $user) {
            $indicators = $this->indicatorRepository->getForUser(
                (int) $user->role_id,
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
    ): KpiScore {
        $indicators = $user->role_id
            ? $this->indicatorRepository->getForUser((int) $user->role_id, $user->department_id ? (int) $user->department_id : null)
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
                'role_id' => $user->role_id,
                'period_end' => $period['end']->toDateString(),
                'raw_score' => $rawScore,
                'normalized_score' => $normalizedScore,
                'status' => $status,
                'grade' => $this->resolveGrade($normalizedScore),
                'breakdown' => $breakdown,
            ]
        )->loadMissing(['user.roleRef', 'role']);

        $this->notifyLowPerformance($user, $score);
        event(new KPIUpdated($score, $changedIndicator));

        return $score;
    }

    public function getDashboard(array $filters): array
    {
        $period = $this->resolvePeriod($filters['period_type'], $filters['period']);
        $cacheKey = $this->dashboardCacheKey($period['type'], $period['start']->toDateString(), $filters['role_id'] ?? null);

        return Cache::remember($cacheKey, now()->addMinutes(config('kpi.cache_ttl')), function () use ($period, $filters) {
            $roleId = $filters['role_id'] ?? null;
            $scores = $this->scoreRepository->getLeaderboard(
                $period['type'],
                $period['start']->toDateString(),
                $roleId
            );

            $average = round((float) $scores->avg('normalized_score'), 2);
            $topPerformer = $scores->first();
            $lowPerformer = $scores->sortBy('normalized_score')->first();

            return [
                'filters' => [
                    'period_type' => $period['type'],
                    'period' => $period['start']->toDateString(),
                    'role_id' => $roleId,
                ],
                'summary' => [
                    'average_kpi' => $average,
                    'top_performer' => $topPerformer,
                    'low_performer' => $lowPerformer,
                    'employee_count' => $scores->count(),
                ],
                'ranking' => $this->attachRank($scores),
            ];
        });
    }

    public function getUserScore(int $userId, array $filters, ?User $actor = null): KpiScore
    {
        $period = $this->resolvePeriod($filters['period_type'], $filters['period']);

        /** @var User $user */
        $user = User::query()
            ->select(['id', 'nip', 'nama', 'jabatan', 'departemen', 'email', 'role', 'role_id'])
            ->with('roleRef:id,name,slug')
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

    public function getTrend(int $months = 6, ?int $roleId = null, ?string $period = null): array
    {
        $current = $this->resolvePeriod('monthly', $period ?? now()->toDateString())['start'];

        return collect(range($months - 1, 0))
            ->map(function (int $offset) use ($current, $roleId) {
                $point = $current->subMonths($offset);
                $dashboard = $this->getDashboard([
                    'period_type' => 'monthly',
                    'period' => $point->toDateString(),
                    'role_id' => $roleId,
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
        $role = !empty($filters['role_id']) ? Role::query()->find($filters['role_id']) : null;

        return [
            'company' => config('kpi.company_name'),
            'generated_at' => now(),
            'period_label' => $period['start']->translatedFormat('F Y'),
            'role_label' => $role?->name,
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

    private function notifyLowPerformance(User $user, KpiScore $score): void
    {
        $threshold = (float) config('kpi.low_performance_threshold', 60);

        if ((float) $score->normalized_score >= $threshold) {
            return;
        }

        $recommendation = 'Tinjau indikator dengan pencapaian terendah dan susun rencana perbaikan bersama atasan.';
        $user->notify(new KpiLowNotification($score, $recommendation));
    }

    private function resolveTaskScores(User $user, string $periodType, array $period): Collection
    {
        $query = TaskScore::query()
            ->with('task')
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

    private function dashboardCacheKey(string $periodType, string $periodStart, ?int $roleId): string
    {
        return sprintf('kpi.dashboard.%s.%s.%s', $periodType, $periodStart, $roleId ?? 'all');
    }

    private function flushDashboardCaches(string $periodType, ?string $periodStart = null): void
    {
        $start = $periodStart ?? now()->startOfMonth()->toDateString();

        Cache::forget($this->dashboardCacheKey($periodType, $start, null));

        Role::query()->pluck('id')->each(function ($roleId) use ($periodType, $start) {
            Cache::forget($this->dashboardCacheKey($periodType, $start, (int) $roleId));
        });
    }
}
