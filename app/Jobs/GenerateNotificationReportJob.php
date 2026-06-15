<?php

namespace App\Jobs;

use App\Enums\ReportStatus;
use App\Models\NotificationReport;
use App\Services\NotificationReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateNotificationReportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly int $reportId,
    ) {}

    public function handle(NotificationReportService $reportService): void
    {
        $report = NotificationReport::query()->find($this->reportId);

        if ($report === null || $report->status === ReportStatus::Completed) {
            return;
        }

        $reportService->generate($report);
    }

    public function failed(?Throwable $exception): void
    {
        $report = NotificationReport::query()->find($this->reportId);

        if ($report === null) {
            return;
        }

        app(NotificationReportService::class)->markAsFailed(
            $report,
            $exception?->getMessage() ?? 'Report generation failed.',
        );
    }
}
