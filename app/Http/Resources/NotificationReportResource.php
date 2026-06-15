<?php

namespace App\Http\Resources;

use App\Models\NotificationReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin NotificationReport
 */
class NotificationReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status->value,
            'period_from' => $this->period_from->toIso8601String(),
            'period_to' => $this->period_to->toIso8601String(),
            'file_path' => $this->file_path,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
