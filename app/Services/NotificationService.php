<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\UserDevice;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class NotificationService
{
    public function sendToUser(string $userId, array $payload): Notification
    {
        // 1. Simpan ke database
        $notification = Notification::create([
            'user_id' => $userId,
            'title'   => $payload['title'],
            'message' => $payload['message'],
            'type'    => $payload['type'],
            'link'    => $payload['link'] ?? null,
            'is_read' => false,
        ]);

        // 2. Kirim FCM ke semua device user
        $devices = UserDevice::where('user_id', $userId)->get();
        foreach ($devices as $device) {
            $this->sendFcm($device->fcm_token, $payload, $notification->id);
        }

        return $notification;
    }

    private function sendFcm(string $token, array $payload, int $notifId): void
    {
        try {
            $messaging = app('firebase.messaging');

            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification(FcmNotification::create($payload['title'], $payload['message']))
                    ->withData([
                        'notification_id' => (string) $notifId,
                        'type'            => $payload['type'],
                        'link'            => $payload['link'] ?? '',
                    ]);
            } catch (\Throwable) {
                $message = CloudMessage::new();
            }

            $messaging->send($message);
        } catch (\Throwable $e) {
            // FCM failure tidak menggagalkan penyimpanan DB
            \Log::warning('FCM send failed for token ' . $token . ': ' . $e->getMessage());
        }
    }
}
