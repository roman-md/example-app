<?php

namespace Tests\Unit;

use App\Contracts\NotificationChannelInterface;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Exceptions\ChannelDeliveryException;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Services\ChannelRegistry;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_notification_as_sent_on_success(): void
    {
        $notification = Notification::query()->create([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'Hello',
            'status' => NotificationStatus::Processing,
        ]);

        $channel = Mockery::mock(NotificationChannelInterface::class);
        $channel->shouldReceive('channel')->andReturn(NotificationChannel::Email);
        $channel->shouldReceive('send')->once()->with(Mockery::type(Notification::class));

        $registry = Mockery::mock(ChannelRegistry::class);
        $registry->shouldReceive('resolve')->once()->andReturn($channel);

        $job = new SendNotificationJob($notification->id);
        $job->handle($registry, app(NotificationService::class));

        $notification->refresh();

        $this->assertSame(NotificationStatus::Sent, $notification->status);
        $this->assertCount(1, $notification->deliveryAttempts);
        $this->assertTrue($notification->deliveryAttempts->first()->success);
    }

    #[Test]
    public function it_records_failed_attempt_and_rethrows_on_delivery_error(): void
    {
        $notification = Notification::query()->create([
            'user_id' => 1,
            'channel' => NotificationChannel::Telegram,
            'message' => 'Hello',
            'status' => NotificationStatus::Processing,
        ]);

        $channel = Mockery::mock(NotificationChannelInterface::class);
        $channel->shouldReceive('channel')->andReturn(NotificationChannel::Telegram);
        $channel->shouldReceive('send')->once()->andThrow(new ChannelDeliveryException('Network error'));

        $registry = Mockery::mock(ChannelRegistry::class);
        $registry->shouldReceive('resolve')->once()->andReturn($channel);

        $job = new SendNotificationJob($notification->id);

        try {
            $job->handle($registry, app(NotificationService::class));
            $this->fail('Expected ChannelDeliveryException was not thrown.');
        } catch (ChannelDeliveryException) {
            // expected
        }

        $notification->refresh();

        $this->assertSame(NotificationStatus::Processing, $notification->status);
        $this->assertCount(1, $notification->deliveryAttempts);
        $this->assertFalse($notification->deliveryAttempts->first()->success);
        $this->assertSame('Network error', $notification->deliveryAttempts->first()->error_message);
    }

    #[Test]
    public function it_marks_notification_as_error_when_job_fails_permanently(): void
    {
        $notification = Notification::query()->create([
            'user_id' => 1,
            'channel' => NotificationChannel::Email,
            'message' => 'Hello',
            'status' => NotificationStatus::Processing,
        ]);

        $job = new SendNotificationJob($notification->id);
        $job->failed(new ChannelDeliveryException('Permanent failure'));

        $notification->refresh();

        $this->assertSame(NotificationStatus::Error, $notification->status);
    }
}
