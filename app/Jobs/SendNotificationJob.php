<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Services\ChannelRegistry;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [5, 15, 30];

    public function __construct(
        public readonly int $notificationId,
    ) {}

    public function handle(ChannelRegistry $channelRegistry, NotificationService $notificationService): void
    {
        $notification = Notification::query()->find($this->notificationId);

        if ($notification === null || $notification->status !== NotificationStatus::Processing) {
            return;
        }

        $channel = $channelRegistry->resolve($notification->channel);

        try {
            $channel->send($notification);
            $notificationService->markAsSent($notification, $this->attempts());
        } catch (Throwable $exception) {
            $notificationService->recordFailedAttempt(
                $notification,
                $this->attempts(),
                $exception->getMessage(),
            );

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        app(NotificationService::class)->markAsFailed($this->notificationId);
    }
}
