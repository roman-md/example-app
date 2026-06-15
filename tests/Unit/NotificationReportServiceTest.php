<?php

namespace Tests\Unit;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\ReportStatus;
use App\Models\Notification;
use App\Models\NotificationReport;
use App\Services\NotificationReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationReportServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_generates_report_with_channel_statistics(): void
    {
        Storage::fake('local');

        $from = Carbon::parse('2026-06-01 00:00:00');
        $to = Carbon::parse('2026-06-30 23:59:59');

        Notification::query()->create([
            'user_id' => 42,
            'channel' => NotificationChannel::Email,
            'message' => 'One',
            'status' => NotificationStatus::Sent,
            'created_at' => '2026-06-10 12:00:00',
        ]);

        Notification::query()->create([
            'user_id' => 42,
            'channel' => NotificationChannel::Email,
            'message' => 'Two',
            'status' => NotificationStatus::Error,
            'created_at' => '2026-06-11 12:00:00',
        ]);

        Notification::query()->create([
            'user_id' => 42,
            'channel' => NotificationChannel::Telegram,
            'message' => 'Three',
            'status' => NotificationStatus::Sent,
            'created_at' => '2026-06-12 12:00:00',
        ]);

        $report = NotificationReport::query()->create([
            'user_id' => 42,
            'status' => ReportStatus::Pending,
            'period_from' => $from,
            'period_to' => $to,
        ]);

        app(NotificationReportService::class)->generate($report);

        $report->refresh();

        $this->assertSame(ReportStatus::Completed, $report->status);
        $this->assertNotNull($report->file_path);
        Storage::disk('local')->assertExists($report->file_path);

        $payload = json_decode(Storage::disk('local')->get($report->file_path), true);

        $this->assertSame(2, $payload['channels']['email']['total']);
        $this->assertSame(1, $payload['channels']['email']['errors']);
        $this->assertSame(1, $payload['channels']['telegram']['total']);
        $this->assertSame(0, $payload['channels']['telegram']['errors']);
    }

    #[Test]
    public function it_marks_report_as_failed_and_removes_partial_file(): void
    {
        Storage::fake('local');

        $report = NotificationReport::query()->create([
            'user_id' => 7,
            'status' => ReportStatus::Processing,
            'period_from' => now()->subDay(),
            'period_to' => now(),
            'file_path' => 'reports/user-7/report-1.json',
        ]);

        Storage::disk('local')->put($report->file_path, '{"partial": true}');

        app(NotificationReportService::class)->markAsFailed($report, 'Generation crashed');

        $report->refresh();

        $this->assertSame(ReportStatus::Failed, $report->status);
        $this->assertNull($report->file_path);
        $this->assertSame('Generation crashed', $report->error_message);
        Storage::disk('local')->assertMissing('reports/user-7/report-1.json');
    }
}
