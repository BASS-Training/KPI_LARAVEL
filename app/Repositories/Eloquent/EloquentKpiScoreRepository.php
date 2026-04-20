<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiScore;
use App\Repositories\Contracts\KpiScoreRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentKpiScoreRepository implements KpiScoreRepositoryInterface
{
    public function upsert(array $identity, array $values): KpiScore
    {
        $score = KpiScore::query()
            ->where('user_id', $identity['user_id'])
            ->where('period_type', $identity['period_type'])
            ->whereDate('period_start', $identity['period_start'])
            ->first() ?? new KpiScore($identity);

        return tap($score, function (KpiScore $score) use ($values) {
            $score->fill($values);
            $score->save();
        });
    }

    public function findUserScore(int $userId, string $periodType, string $periodStart): ?KpiScore
    {
        return KpiScore::query()
            ->with(['user.positionRef'])
            ->where('user_id', $userId)
            ->where('period_type', $periodType)
            ->whereDate('period_start', $periodStart)
            ->first();
    }

    public function getLeaderboard(string $periodType, string $periodStart, ?int $roleId = null): Collection
    {
        return KpiScore::query()
            ->with(['user.positionRef'])
            ->where('period_type', $periodType)
            ->whereDate('period_start', $periodStart)
            ->orderByDesc('normalized_score')
            ->orderByDesc('raw_score')
            ->get();
    }
}
