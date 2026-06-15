<?php

namespace App\Enums;

enum NotificationStatus: string
{
    case Processing = 'processing';
    case Sent = 'sent';
    case Error = 'error';
}
