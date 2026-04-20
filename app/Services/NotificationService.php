<?php

namespace App\Services;

use App\Events\UserNotificationCreated;
use App\Models\KpiNotification;
use App\Models\Setting;
use App\Models\User;

class NotificationService
{
    public function __construct(private readonly FcmService $fcm) {}

    public function sendNotification(User $user, string $type, string $title, string $body, array $payload = []): KpiNotification
    {
        $notification = KpiNotification::create([
            'user_id' => $user->id,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'payload' => $payload ?: null,
        ]);

        UserNotificationCreated::dispatch($user->id, [
            'id'         => $notification->id,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'payload'    => $payload,
            'is_read'    => false,
            'read_at'    => null,
            'created_at' => $notification->created_at->toISOString(),
        ]);

        $this->fcm->sendToUser($user, $title, $body, array_merge($payload, ['type' => $type]));

        return $notification;
    }

    public function checkLowPerformance(User $user, float $kpiScore): void
    {
        $threshold = (float) (Setting::getValue('low_performance_threshold') ?? 3.0);

        if ($kpiScore < $threshold) {
            $this->sendNotification(
                $user,
                'low_performance',
                'Performa KPI Di Bawah Standar',
                "Nilai KPI Anda bulan ini adalah {$kpiScore}, di bawah ambang batas {$threshold}. Segera konsultasikan dengan HR.",
                ['kpi_score' => $kpiScore, 'threshold' => $threshold],
            );
        }
    }

    public function checkLowPercentage(User $user, float $percentage, string $componentName): void
    {
        if ($percentage < 50) {
            $this->sendNotification(
                $user,
                'low_performance',
                'Pencapaian KPI Rendah',
                "Pencapaian komponen \"{$componentName}\" baru {$percentage}% dari target. Tingkatkan performa Anda.",
                ['percentage' => $percentage, 'component' => $componentName],
            );
        }
    }

    public function sendDeadlineReminder(User $user, string $componentName, string $deadline): void
    {
        $this->sendNotification(
            $user,
            'deadline_reminder',
            'Pengingat: Batas Waktu Laporan KPI',
            "Laporan KPI untuk komponen \"{$componentName}\" harus disubmit sebelum {$deadline}.",
            ['component' => $componentName, 'deadline' => $deadline],
        );
    }

    public function broadcastToAllEmployees(string $type, string $title, string $body, array $payload = []): int
    {
        $users = User::where('role', 'pegawai')->get();
        $count = 0;

        foreach ($users as $user) {
            $this->sendNotification($user, $type, $title, $body, $payload);
            $count++;
        }

        return $count;
    }
}
