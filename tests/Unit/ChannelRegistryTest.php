<?php

namespace Tests\Unit;

use App\Channels\EmailChannel;
use App\Channels\TelegramChannel;
use App\Enums\NotificationChannel;
use App\Services\ChannelRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChannelRegistryTest extends TestCase
{
    #[Test]
    public function it_resolves_registered_channels(): void
    {
        $registry = new ChannelRegistry([
            new EmailChannel,
            new TelegramChannel,
        ]);

        $this->assertInstanceOf(EmailChannel::class, $registry->resolve(NotificationChannel::Email));
        $this->assertInstanceOf(TelegramChannel::class, $registry->resolve(NotificationChannel::Telegram));
    }

    #[Test]
    public function it_throws_for_unknown_channel(): void
    {
        $registry = new ChannelRegistry([new EmailChannel]);

        $this->expectException(InvalidArgumentException::class);

        $registry->resolve(NotificationChannel::Telegram);
    }
}
