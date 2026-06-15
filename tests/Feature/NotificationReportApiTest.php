<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\ReportStatus;
use App\Models\Notification;
use App\Models\NotificationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationReportApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_requests_report_generation_and_returns_status(): void
    {
        Storage::fake('local');

        Notification::query()->create([
            'user_id' => 15,
            'channel' => NotificationChannel::Email,
            'message' => 'Report me',
            'status' => NotificationStatus::Sent,
            'created_at' => '2026-06-10 10:00:00',
        ]);

        $createResponse = $this->postJson('/api/users/15/reports', [
            'period_from' => '2026-06-01T00:00:00Z',
            'period_to' => '2026-06-30T23:59:59Z',
        ]);

        $createResponse
            ->assertAccepted()
            ->assertJsonPath('data.status', ReportStatus::Completed->value);

        $reportId = $createResponse->json('data.id');

        $this->getJson("/api/reports/{$reportId}")
            ->assertOk()
            ->assertJsonPath('data.status', ReportStatus::Completed->value);

        $this->get("/api/reports/{$reportId}/download")
            ->assertOk()
            ->assertDownload();
    }

    #[Test]
    public function it_returns_conflict_when_report_is_not_ready(): void
    {
        $report = NotificationReport::query()->create([
            'user_id' => 2,
            'status' => ReportStatus::Processing,
            'period_from' => now()->subWeek(),
            'period_to' => now(),
        ]);

        $this->getJson("/api/reports/{$report->id}/download")
            ->assertConflict()
            ->assertJsonPath('status', ReportStatus::Processing->value);
    }

    #[Test]
    public function it_validates_report_period(): void
    {
        $this->postJson('/api/users/1/reports', [
            'period_from' => '2026-06-10',
            'period_to' => '2026-06-01',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['period_to']);
    }
}
