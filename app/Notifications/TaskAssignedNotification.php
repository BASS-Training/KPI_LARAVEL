<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
        private readonly User $assigner,
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
        return (new MailMessage)
            ->subject('Task KPI Baru: ' . $this->task->judul)
            ->greeting('Halo ' . ($notifiable->nama ?? 'Pegawai') . ',')
            ->line("{$this->assigner->nama} telah memberikan task KPI baru kepada Anda.")
            ->line('Task: ' . $this->task->judul)
            ->line('Mulai: ' . optional($this->task->start_date)->format('d M Y'))
            ->line('Selesai: ' . optional($this->task->end_date)->format('d M Y'))
            ->line('Bobot: ' . (float) $this->task->weight . ' poin')
            ->action('Lihat Task Saya', url('/my-tasks'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'task_assigned',
            'title'      => 'Task KPI baru diberikan',
            'message'    => "Task \"{$this->task->judul}\" diberikan oleh {$this->assigner->nama}.",
            'task_id'    => $this->task->id,
            'task_title' => $this->task->judul,
            'start_date' => optional($this->task->start_date)->toDateString(),
            'end_date'   => optional($this->task->end_date)->toDateString(),
            'weight'     => (float) $this->task->weight,
            'assigner'   => $this->assigner->nama,
        ];
    }
}
