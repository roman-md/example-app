<?php

namespace App\Channels;

use App\Contracts\NotificationChannelInterface;
use App\Enums\NotificationChannel;
use App\Exceptions\ChannelDeliveryException;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class TelegramChannel implements NotificationChannelInterface
{
    public function channel(): NotificationChannel
    {
        return NotificationChannel::Telegram;
    }

    public function send(Notification $notification): void
    {
        if (str_starts_with($notification->message, '[fail]')) {
            throw new ChannelDeliveryException('Simulated telegram delivery failure.');
        }

        Log::info('Telegram notification sent (stub).', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'message' => $notification->message,
        ]);
    }
}
