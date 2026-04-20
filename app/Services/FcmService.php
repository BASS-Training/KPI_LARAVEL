<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $user->fcmTokens()->pluck('token')->toArray();

        if (empty($tokens)) {
            return;
        }

        try {
            /** @var \Kreait\Firebase\Contract\Messaging $messaging */
            $messaging = app(\Kreait\Firebase\Contract\Messaging::class);

            $message = CloudMessage::new()
                ->withNotification(FcmNotification::create($title, $body))
                ->withData(array_map('strval', $data));

            $report = $messaging->sendMulticast($message, $tokens);

            if ($report->hasFailures()) {
                $invalidTokens = $report->invalidTokens();
                if (!empty($invalidTokens)) {
                    $user->fcmTokens()
                        ->whereIn('token', $invalidTokens)
                        ->delete();
                }
            }
        } catch (\Throwable $e) {
            Log::error('FCM send failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
