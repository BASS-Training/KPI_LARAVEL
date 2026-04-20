<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class NotifyDeadlineReminder extends Command
{
    protected $signature = 'notify:deadline-reminder';
    protected $description = 'Send deadline reminder notifications for tasks due in 1 or 3 days';

    public function __construct(private readonly NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $targetDates = [
            now()->addDay()->toDateString(),
            now()->addDays(3)->toDateString(),
        ];

        $tasks = Task::with('assignee')
            ->whereIn(\Illuminate\Support\Facades\DB::raw('DATE(end_date)'), $targetDates)
            ->whereNotNull('assigned_to')
            ->get();

        foreach ($tasks as $task) {
            if (! $task->assignee) {
                continue;
            }

            $daysLeft = (int) now()->startOfDay()->diffInDays($task->end_date, false);
            $daysLabel = $daysLeft === 1 ? 'besok' : "{$daysLeft} hari lagi";

            $this->notificationService->sendNotification(
                $task->assignee,
                'deadline_reminder',
                'Pengingat Deadline Task',
                "Task \"{$task->judul}\" jatuh tempo {$daysLabel}.",
                ['task_id' => $task->id, 'days_left' => $daysLeft],
            );
        }

        $this->info("Deadline reminders sent for {$tasks->count()} tasks.");

        return self::SUCCESS;
    }
}
