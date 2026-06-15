<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\ReportStatus;
use App\Jobs\GenerateNotificationReportJob;
use App\Models\NotificationReport;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class NotificationReportService
{
    public function request(int $userId, CarbonInterface $from, CarbonInterface $to): NotificationReport
    {
        $report = NotificationReport::query()->create([
            'user_id' => $userId,
            'status' => ReportStatus::Pending,
            'period_from' => $from,
            'period_to' => $to,
        ]);

        GenerateNotificationReportJob::dispatch($report->id);

        return $report->refresh();
    }

    public function find(int $id): ?NotificationReport
    {
        return NotificationReport::query()->find($id);
    }

    public function generate(NotificationReport $report): void
    {
        $report->update(['status' => ReportStatus::Processing]);

        $disk = $this->disk();
        $relativePath = $this->buildFilePath($report);

        try {
            $payload = $this->buildReportPayload($report);
            $disk->put($relativePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            $report->update([
                'status' => ReportStatus::Completed,
                'file_path' => $relativePath,
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            if ($disk->exists($relativePath)) {
                $disk->delete($relativePath);
            }

            $report->update([
                'status' => ReportStatus::Failed,
                'file_path' => null,
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function markAsFailed(NotificationReport $report, string $errorMessage): void
    {
        if ($report->file_path !== null && $this->disk()->exists($report->file_path)) {
            $this->disk()->delete($report->file_path);
        }

        $report->update([
            'status' => ReportStatus::Failed,
            'file_path' => null,
            'error_message' => $errorMessage,
        ]);
    }

    public function getDownloadPath(NotificationReport $report): ?string
    {
        if ($report->status !== ReportStatus::Completed || $report->file_path === null) {
            return null;
        }

        return $this->disk()->path($report->file_path);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReportPayload(NotificationReport $report): array
    {
        /** @var Collection<int, object{channel: string, status: string, total: int|string}> $rows */
        $rows = DB::table('notifications')
            ->select('channel', 'status', DB::raw('count(*) as total'))
            ->where('user_id', $report->user_id)
            ->whereBetween('created_at', [$report->period_from, $report->period_to])
            ->groupBy('channel', 'status')
            ->get();

        $channels = [];

        foreach (NotificationChannel::cases() as $channel) {
            $channels[$channel->value] = [
                'total' => 0,
                'errors' => 0,
            ];
        }

        foreach ($rows as $row) {
            $channelValue = (string) $row->channel;

            if (! isset($channels[$channelValue])) {
                continue;
            }

            $channels[$channelValue]['total'] += (int) $row->total;

            if (NotificationStatus::from((string) $row->status) === NotificationStatus::Error) {
                $channels[$channelValue]['errors'] += (int) $row->total;
            }
        }

        return [
            'user_id' => $report->user_id,
            'period' => [
                'from' => $report->period_from->toIso8601String(),
                'to' => $report->period_to->toIso8601String(),
            ],
            'channels' => $channels,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function buildFilePath(NotificationReport $report): string
    {
        return sprintf(
            'reports/user-%d/report-%d.json',
            $report->user_id,
            $report->id,
        );
    }

    private function disk(): Filesystem
    {
        return Storage::disk('local');
    }
}
