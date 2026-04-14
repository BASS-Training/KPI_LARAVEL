<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDeadlineNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
        private readonly int $daysRemaining,
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('kpi.mail_low_performance_notification') && filled($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urgency = $this->daysRemaining <= 1 ? 'SEGERA' : "{$this->daysRemaining} hari lagi";

        return (new MailMessage)
            ->subject("Deadline Task [{$urgency}]: {$this->task->judul}")
            ->greeting('Halo ' . ($notifiable->nama ?? 'Pegawai') . ',')
            ->line("Task \"{$this->task->judul}\" akan berakhir {$urgency}.")
            ->line('Deadline: ' . optional($this->task->end_date)->format('d M Y'))
            ->line('Status saat ini: ' . $this->task->status_label)
            ->action('Perbarui Status Task', url('/my-tasks'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'task_deadline',
            'title'           => $this->daysRemaining <= 1
                ? 'Deadline task hari ini!'
                : "Deadline task {$this->daysRemaining} hari lagi",
            'message'         => "Task \"{$this->task->judul}\" akan berakhir pada "
                . optional($this->task->end_date)->format('d M Y') . '.',
            'task_id'         => $this->task->id,
            'task_title'      => $this->task->judul,
            'end_date'        => optional($this->task->end_date)->toDateString(),
            'days_remaining'  => $this->daysRemaining,
            'status'          => $this->task->status_code,
        ];
    }
}
