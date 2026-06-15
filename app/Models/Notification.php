<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property NotificationChannel $channel
 * @property string $message
 * @property NotificationStatus $status
 */
class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'channel',
        'message',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => NotificationStatus::class,
        ];
    }

    /**
     * @return HasMany<NotificationDeliveryAttempt, $this>
     */
    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(NotificationDeliveryAttempt::class);
    }
}
