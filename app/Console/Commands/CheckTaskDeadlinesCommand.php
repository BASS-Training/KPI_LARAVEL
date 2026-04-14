<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskDeadlineNotification;
use Illuminate\Console\Command;

class CheckTaskDeadlinesCommand extends Command
{
    protected $signature   = 'kpi:check-deadlines {--days=3 : Notify tasks due within N days}';
    protected $description = 'Send deadline reminder notifications for upcoming manual KPI tasks.';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $tasks = Task::query()
            ->with(['assignee'])
            ->where('task_type', Task::TYPE_MANUAL_ASSIGNMENT)
            ->whereNotIn('status', ['Selesai'])
            ->whereBetween('end_date', [
                now()->toDateString(),
                now()->addDays($days)->toDateString(),
            ])
            ->get();

        $count = 0;

        foreach ($tasks as $task) {
            $assignee = $task->assignee;

            if (! $assignee) {
                continue;
            }

            $daysRemaining = (int) now()->startOfDay()->diffInDays($task->end_date->startOfDay());

            $assignee->notify(new TaskDeadlineNotification($task, $daysRemaining));
            $count++;
        }

        $this->info("Deadline reminders sent for {$count} task(s).");

        return self::SUCCESS;
    }
}
