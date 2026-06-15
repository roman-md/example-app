<?php

use App\Channels\EmailChannel;
use App\Channels\TelegramChannel;

return [
    'channels' => [
        EmailChannel::class,
        TelegramChannel::class,
    ],

    'delivery' => [
        'max_attempts' => 3,
        'backoff_seconds' => [5, 15, 30],
    ],
];
