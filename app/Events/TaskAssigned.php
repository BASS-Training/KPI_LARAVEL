<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskAssigned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly User $assignee,
        public readonly User $assigner,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->assignee->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.assigned';
    }

    public function broadcastWith(): array
    {
        return [
            'task_id'    => $this->task->id,
            'title'      => $this->task->judul,
            'start_date' => optional($this->task->start_date)->toDateString(),
            'end_date'   => optional($this->task->end_date)->toDateString(),
            'weight'     => (float) $this->task->weight,
            'assigner'   => $this->assigner->nama,
            'message'    => "Task '{$this->task->judul}' diberikan oleh {$this->assigner->nama}.",
        ];
    }
}
