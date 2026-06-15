<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property ReportStatus $status
 * @property Carbon $period_from
 * @property Carbon $period_to
 * @property string|null $file_path
 * @property string|null $error_message
 */
class NotificationReport extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'period_from',
        'period_to',
        'file_path',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'period_from' => 'datetime',
            'period_to' => 'datetime',
        ];
    }
}
