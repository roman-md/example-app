<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    /**
     * @param  array{user_id: int, channel: NotificationChannel, message: string}  $data
     */
    public function create(array $data): Notification
    {
        $notification = Notification::query()->create([
            'user_id' => $data['user_id'],
            'channel' => $data['channel'],
            'message' => $data['message'],
            'status' => NotificationStatus::Processing,
        ]);

        SendNotificationJob::dispatch($notification->id);

        return $notification->refresh();
    }

    public function find(int $id): ?Notification
    {
        return Notification::query()->find($id);
    }

    /**
     * @return LengthAwarePaginator<int, Notification>
     */
    public function listForUser(
        int $userId,
        ?NotificationStatus $status = null,
        ?NotificationChannel $channel = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = Notification::query()
            ->where('user_id', $userId)
            ->latest();

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($channel !== null) {
            $query->where('channel', $channel);
        }

        return $query->paginate($perPage);
    }

    public function markAsSent(Notification $notification, int $attemptNumber): void
    {
        $notification->deliveryAttempts()->create([
            'attempt_number' => $attemptNumber,
            'success' => true,
        ]);

        $notification->update(['status' => NotificationStatus::Sent]);
    }

    public function recordFailedAttempt(Notification $notification, int $attemptNumber, string $errorMessage): void
    {
        $notification->deliveryAttempts()->create([
            'attempt_number' => $attemptNumber,
            'success' => false,
            'error_message' => $errorMessage,
        ]);
    }

    public function markAsFailed(int $notificationId): void
    {
        Notification::query()
            ->whereKey($notificationId)
            ->where('status', NotificationStatus::Processing)
            ->update(['status' => NotificationStatus::Error]);
    }
}
