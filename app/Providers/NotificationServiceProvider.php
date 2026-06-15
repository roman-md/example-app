<?php

namespace App\Providers;

use App\Contracts\NotificationChannelInterface;
use App\Services\ChannelRegistry;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ChannelRegistry::class, function ($app): ChannelRegistry {
            $channels = array_map(
                fn (string $class): NotificationChannelInterface => $app->make($class),
                config('notifications.channels', []),
            );

            return new ChannelRegistry($channels);
        });
    }
}
