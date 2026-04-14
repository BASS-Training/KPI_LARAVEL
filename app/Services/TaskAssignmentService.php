<?php

namespace App\Services;

use App\Events\TaskAssigned;
use App\Models\Task;
use App\Models\TaskScore;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class TaskAssignmentService
{
    public function __construct(private readonly KpiService $kpiService)
    {
    }

    public function create(array $payload, User $actor): Task
    {
        $task = DB::transaction(function () use ($payload, $actor) {
            $task = Task::query()->create($this->buildPayload($payload, $actor));

            $this->syncTaskScore($task);

            return $task->loadMissing(['assignee', 'assigner', 'taskScores']);
        });

        // Dispatch notification & broadcast outside the transaction
        $assignee = $task->assignee;

        if ($assignee) {
            $assignee->notify(new TaskAssignedNotification($task, $actor));
            event(new TaskAssigned($task, $assignee, $actor));
        }

        return $task;
    }

    public function update(Task $task, array $payload, User $actor): Task
    {
        $previousPeriod = $task->task_period;
        $previousAssigneeId = $task->assigned_to_user_id;

        return DB::transaction(function () use ($task, $payload, $actor, $previousPeriod, $previousAssigneeId) {
            $task->update($this->buildPayload($payload, $actor, $task));

            $this->syncTaskScore($task, $previousPeriod, $previousAssigneeId);

            return $task->loadMissing(['assignee', 'assigner', 'taskScores']);
        });
    }

    public function updateStatus(Task $task, array $payload): Task
    {
        $previousPeriod = $task->task_period;

        return DB::transaction(function () use ($task, $payload, $previousPeriod) {
            $task->update([
                'status' => Task::statusForStorage($payload['status']),
                'actual_value' => array_key_exists('actual_value', $payload) ? $payload['actual_value'] : $task->actual_value,
                'end_date' => $payload['end_date'] ?? $task->end_date,
            ]);

            $this->syncTaskScore($task, $previousPeriod, $task->assigned_to_user_id);

            return $task->loadMissing(['assignee', 'assigner', 'taskScores']);
        });
    }

    public function delete(Task $task): void
    {
        $previousUser = $task->assignee ?: $task->user;
        $previousPeriod = $task->task_period;

        DB::transaction(function () use ($task) {
            $task->taskScores()->delete();
            $task->delete();
        });

        if ($previousUser) {
            $this->kpiService->recalculateUserScore(
                $previousUser,
                'monthly',
                CarbonImmutable::createFromFormat('Y-m', $previousPeriod)->startOfMonth()->toDateString()
            );
        }
    }

    private function buildPayload(array $payload, User $actor, ?Task $task = null): array
    {
        $assignedTo = (int) ($payload['assigned_to'] ?? $task?->assigned_to_user_id);

        return [
            'task_type' => Task::TYPE_MANUAL_ASSIGNMENT,
            'user_id' => $assignedTo,
            'assigned_by' => $task?->assigned_by ?: $actor->id,
            'assigned_to' => $assignedTo,
            'tanggal' => $payload['start_date'],
            'start_date' => $payload['start_date'],
            'end_date' => $payload['end_date'],
            'judul' => $payload['judul'] ?? $payload['title'],
            'jenis_pekerjaan' => 'Task KPI',
            'status' => Task::statusForStorage($payload['status']),
            'deskripsi' => $payload['deskripsi'] ?? $payload['description'] ?? null,
            'weight' => round((float) $payload['weight'], 2),
            'target_value' => array_key_exists('target_value', $payload) ? round((float) $payload['target_value'], 2) : null,
            'actual_value' => array_key_exists('actual_value', $payload) ? round((float) $payload['actual_value'], 2) : null,
        ];
    }

    private function syncTaskScore(Task $task, ?string $previousPeriod = null, ?int $previousAssigneeId = null): void
    {
        $period = $task->task_period;
        $assignee = $task->assignee()->firstOrFail();

        TaskScore::query()->updateOrCreate(
            ['task_id' => $task->id],
            [
                'user_id' => $assignee->id,
                'score' => $this->kpiService->calculateTaskScore($task),
                'period' => $period,
            ]
        );

        $this->kpiService->recalculateUserScore(
            $assignee,
            'monthly',
            CarbonImmutable::createFromFormat('Y-m', $period)->startOfMonth()->toDateString()
        );

        if ($previousAssigneeId && ($previousAssigneeId !== $assignee->id || $previousPeriod !== $period)) {
            $previousUser = User::query()->find($previousAssigneeId);

            if ($previousUser && $previousPeriod) {
                $this->kpiService->recalculateUserScore(
                    $previousUser,
                    'monthly',
                    CarbonImmutable::createFromFormat('Y-m', $previousPeriod)->startOfMonth()->toDateString()
                );
            }
        }
    }
}
