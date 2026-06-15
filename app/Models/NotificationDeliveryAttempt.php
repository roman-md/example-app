<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $notification_id
 * @property int $attempt_number
 * @property bool $success
 * @property string|null $error_message
 */
class NotificationDeliveryAttempt extends Model
{
    protected $fillable = [
        'notification_id',
        'attempt_number',
        'success',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'success' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Notification, $this>
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
