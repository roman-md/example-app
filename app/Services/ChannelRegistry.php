<?php

namespace App\Services;

use App\Contracts\NotificationChannelInterface;
use App\Enums\NotificationChannel;
use InvalidArgumentException;

class ChannelRegistry
{
    /**
     * @param  iterable<NotificationChannelInterface>  $channels
     */
    public function __construct(
        private readonly iterable $channels,
    ) {}

    public function resolve(NotificationChannel $channel): NotificationChannelInterface
    {
        foreach ($this->channels as $implementation) {
            if ($implementation->channel() === $channel) {
                return $implementation;
            }
        }

        throw new InvalidArgumentException("Notification channel [{$channel->value}] is not registered.");
    }
}
