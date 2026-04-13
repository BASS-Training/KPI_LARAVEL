<?php

namespace App\Notifications;

use App\Models\KpiScore;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KpiLowNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly KpiScore $score,
        private readonly string $recommendation,
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
            ->subject('Peringatan KPI Rendah')
            ->greeting('Halo ' . ($notifiable->nama ?? 'User') . ',')
            ->line('Skor KPI Anda berada di bawah ambang batas minimum.')
            ->line('Skor: ' . (float) $this->score->normalized_score)
            ->line('Status: ' . strtoupper($this->score->status))
            ->line('Rekomendasi: ' . $this->recommendation)
            ->action('Lihat Dashboard KPI', url('/dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'KPI di bawah threshold',
            'user_id' => $this->score->user_id,
            'score' => (float) $this->score->normalized_score,
            'status' => $this->score->status,
            'grade' => $this->score->grade,
            'period_start' => optional($this->score->period_start)->toDateString(),
            'period_type' => $this->score->period_type,
            'recommendation' => $this->recommendation,
            'message' => sprintf(
                'Skor KPI Anda %.2f berada di bawah threshold. %s',
                (float) $this->score->normalized_score,
                $this->recommendation
            ),
        ];
    }
}
