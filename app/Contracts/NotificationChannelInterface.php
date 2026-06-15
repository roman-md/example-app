<?php

namespace App\Contracts;

use App\Enums\NotificationChannel;
use App\Models\Notification;

interface NotificationChannelInterface
{
    public function channel(): NotificationChannel;

    public function send(Notification $notification): void;
}
