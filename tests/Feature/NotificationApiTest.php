<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Exceptions\ChannelDeliveryException;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Services\ChannelRegistry;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_notification_and_delivers_it(): void
    {
        $response = $this->postJson('/api/notifications', [
            'user_id' => 10,
            'channel' => 'email',
            'message' => 'Payment received',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', NotificationStatus::Sent->value)
            ->assertJsonPath('data.channel', 'email')
            ->assertJsonPath('data.user_id', 10);

        $this->assertDatabaseHas('notifications', [
            'user_id' => 10,
            'status' => NotificationStatus::Sent->value,
        ]);
    }

    #[Test]
    public function it_validates_notification_payload(): void
    {
        $response = $this->postJson('/api/notifications', [
            'user_id' => 0,
            'channel' => 'sms',
            'message' => str_repeat('a', 501),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'channel', 'message']);
    }

    #[Test]
    public function it_returns_notification_status(): void
    {
        $notification = Notification::query()->create([
            'user_id' => 5,
            'channel' => NotificationChannel::Telegram,
            'message' => 'Status check',
            'status' => NotificationStatus::Processing,
        ]);

        $this->getJson("/api/notifications/{$notification->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $notification->id)
            ->assertJsonPath('data.status', NotificationStatus::Processing->value);
    }

    #[Test]
    public function it_lists_user_notifications_with_filters(): void
    {
        Notification::query()->create([
            'user_id' => 3,
            'channel' => NotificationChannel::Email,
            'message' => 'Email sent',
            'status' => NotificationStatus::Sent,
        ]);

        Notification::query()->create([
            'user_id' => 3,
            'channel' => NotificationChannel::Telegram,
            'message' => 'Telegram sent',
            'status' => NotificationStatus::Sent,
        ]);

        Notification::query()->create([
            'user_id' => 3,
            'channel' => NotificationChannel::Email,
            'message' => 'Email failed',
            'status' => NotificationStatus::Error,
        ]);

        $this->getJson('/api/users/3/notifications?status=sent&channel=email')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.channel', 'email')
            ->assertJsonPath('data.0.status', 'sent');
    }

    #[Test]
    public function it_marks_notification_as_error_after_delivery_failures(): void
    {
        $notification = Notification::query()->create([
            'user_id' => 99,
            'channel' => NotificationChannel::Email,
            'message' => '[fail] cannot deliver',
            'status' => NotificationStatus::Processing,
        ]);

        $notificationId = $notification->id;

        $job = new SendNotificationJob($notificationId);

        for ($attempt = 1; $attempt <= $job->tries; $attempt++) {
            try {
                $job->handle(app(ChannelRegistry::class), app(NotificationService::class));
            } catch (\Throwable) {
                if ($attempt === $job->tries) {
                    $job->failed(new ChannelDeliveryException('Delivery failed'));
                }
            }
        }

        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
            'status' => NotificationStatus::Error->value,
        ]);

        $this->assertSame($job->tries, Notification::query()->find($notificationId)->deliveryAttempts()->count());
    }
}
