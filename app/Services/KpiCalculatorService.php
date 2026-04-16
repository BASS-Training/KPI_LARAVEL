<?php

namespace App\Services;

use App\Models\KpiComponent;
use App\Models\User;
use Illuminate\Support\Collection;

class KpiCalculatorService
{
    public function calculateForUser(User $user, ?int $month = null, ?int $year = null): array
    {
        $components = KpiComponent::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query
                ->whereNull('department_id')
                ->orWhere('department_id', $user->department_id))
            ->where(fn ($query) => $query
                ->whereNull('position_id')
                ->orWhere('position_id', $user->position_id))
            ->where(fn ($query) => $query
                ->where('jabatan', $user->jabatan)
                ->orWhere('jabatan', 'Semua Jabatan'))
            ->get();

        $tasks = $user->tasks()
            ->when($month, fn ($query) => $query->whereMonth('tanggal', $month))
            ->when($year, fn ($query) => $query->whereYear('tanggal', $year))
            ->get();

        $total = 0.0;
        $results = [];

        foreach ($components as $component) {
            $componentTasks = $tasks->where('kpi_component_id', $component->id);
            $score = $this->calculateComponentScore($component->tipe, $componentTasks, (float) $component->target);
            $weighted = round(((float) $component->bobot) * $score, 2);

            $total += $weighted;

            $results[] = [
                'id' => $component->id,
                'objectives' => $component->objectives,
                'strategy' => $component->strategy,
                'tipe' => $component->tipe,
                'bobot' => (float) $component->bobot,
                'target' => $component->target !== null ? (float) $component->target : null,
                'manual_score_total' => round($this->sumTaskActuals($componentTasks), 2),
                'jumlah_task' => $componentTasks->count(),
                'skor' => $score,
                'nilai_bobot' => $weighted,
            ];
        }

        return [
            'user' => $user,
            'total' => round($total, 2),
            'predikat' => $this->resolvePredikat($total),
            'components' => $results,
        ];
    }

    public function ranking(?int $month = null, ?int $year = null): Collection
    {
        return User::query()
            ->get()
            ->map(fn (User $user) => $this->calculateForUser($user, $month, $year))
            ->sortByDesc('total')
            ->values();
    }

    private function calculateComponentScore(string $type, Collection $tasks, float $target): int
    {
        return match ($type) {
            'zero_delay' => $tasks->contains('ada_delay', true) ? 0 : 5,
            'zero_error' => $tasks->contains('ada_error', true) ? 0 : 5,
            'zero_complaint' => $tasks->contains('ada_komplain', true) ? 0 : 5,
            'achievement', 'csi' => $this->resolveAchievementScore($this->sumTaskActuals($tasks), $target),
            default => 0,
        };
    }

    private function sumTaskActuals(Collection $tasks): float
    {
        return (float) $tasks->sum(fn ($task) => $task->manual_score ?? $task->actual_value ?? 0);
    }

    private function resolveAchievementScore(float $manualScoreTotal, float $target): int
    {
        if ($target <= 0) {
            return 0;
        }

        $percentage = ($manualScoreTotal / $target) * 100;

        return match (true) {
            $percentage > 99.9 => 5,
            $percentage >= 90 => 4,
            $percentage >= 70 => 3,
            $percentage >= 60 => 2,
            $percentage >= 50 => 1,
            default => 0,
        };
    }

    private function resolvePredikat(float $score): string
    {
        return match (true) {
            $score >= 5 => 'Baik Sekali',
            $score >= 4 => 'Baik',
            $score >= 3 => 'Cukup',
            $score >= 2 => 'Kurang',
            default => 'Buruk',
        };
    }
}
