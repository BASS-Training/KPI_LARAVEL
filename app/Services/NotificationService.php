<?php

namespace App\Services;

use App\Models\KpiNotification;
use App\Models\Setting;
use App\Models\User;

class NotificationService
{
    public function checkLowPerformance(User $user, float $kpiScore): void
    {
        $threshold = (float) (Setting::getValue('low_performance_threshold') ?? 3.0);

        if ($kpiScore < $threshold) {
            KpiNotification::create([
                'user_id' => $user->id,
                'type' => 'low_performance',
                'title' => 'Performa KPI Di Bawah Standar',
                'body' => "Nilai KPI Anda bulan ini adalah {$kpiScore}, di bawah ambang batas {$threshold}. Segera konsultasikan dengan HR.",
                'payload' => ['kpi_score' => $kpiScore, 'threshold' => $threshold],
            ]);
        }
    }

    public function checkLowPercentage(User $user, float $percentage, string $componentName): void
    {
        if ($percentage < 50) {
            KpiNotification::create([
                'user_id' => $user->id,
                'type' => 'low_performance',
                'title' => 'Pencapaian KPI Rendah',
                'body' => "Pencapaian komponen \"{$componentName}\" baru {$percentage}% dari target. Tingkatkan performa Anda.",
                'payload' => ['percentage' => $percentage, 'component' => $componentName],
            ]);
        }
    }

    public function sendDeadlineReminder(User $user, string $componentName, string $deadline): void
    {
        KpiNotification::create([
            'user_id' => $user->id,
            'type' => 'deadline_reminder',
            'title' => 'Pengingat: Batas Waktu Laporan KPI',
            'body' => "Laporan KPI untuk komponen \"{$componentName}\" harus disubmit sebelum {$deadline}.",
            'payload' => ['component' => $componentName, 'deadline' => $deadline],
        ]);
    }

    public function sendNotification(User $user, string $type, string $title, string $body, array $payload = []): KpiNotification
    {
        return KpiNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'payload' => $payload ?: null,
        ]);
    }

    public function broadcastToAllEmployees(string $type, string $title, string $body, array $payload = []): int
    {
        $users = User::where('role', 'pegawai')->get();
        $count = 0;

        foreach ($users as $user) {
            KpiNotification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'payload' => $payload ?: null,
            ]);
            $count++;
        }

        return $count;
    }
}
