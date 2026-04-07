<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\KpiNotificationResource;
use App\Models\KpiNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $notifications = KpiNotification::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $unreadCount = $notifications->whereNull('read_at')->count();

        return $this->success([
            'items' => KpiNotificationResource::collection($notifications)->resolve(),
            'unread_count' => $unreadCount,
        ], 'Notifikasi berhasil dimuat');
    }

    public function markRead(Request $request, KpiNotification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return $this->error('Akses ditolak.', [], 403);
        }

        $notification->markAsRead();

        return $this->success(null, 'Notifikasi ditandai sudah dibaca');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        KpiNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success(null, 'Semua notifikasi ditandai sudah dibaca');
    }
}
