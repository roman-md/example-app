<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNotificationReportRequest;
use App\Http\Resources\NotificationReportResource;
use App\Services\NotificationReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NotificationReportController extends Controller
{
    public function __construct(
        private readonly NotificationReportService $reportService,
    ) {}

    public function store(StoreNotificationReportRequest $request, int $userId): JsonResponse
    {
        $report = $this->reportService->request(
            $userId,
            Carbon::parse($request->string('period_from')->toString()),
            Carbon::parse($request->string('period_to')->toString()),
        );

        return (new NotificationReportResource($report))
            ->response()
            ->setStatusCode(202);
    }

    public function show(int $report): JsonResponse|NotificationReportResource
    {
        $model = $this->reportService->find($report);

        if ($model === null) {
            return response()->json(['message' => 'Report not found.'], 404);
        }

        return new NotificationReportResource($model);
    }

    public function download(int $report): JsonResponse|BinaryFileResponse
    {
        $model = $this->reportService->find($report);

        if ($model === null) {
            return response()->json(['message' => 'Report not found.'], 404);
        }

        if ($model->status !== ReportStatus::Completed) {
            return response()->json([
                'message' => 'Report is not ready for download.',
                'status' => $model->status->value,
            ], 409);
        }

        $path = $this->reportService->getDownloadPath($model);

        if ($path === null || ! is_file($path)) {
            return response()->json(['message' => 'Report file is missing.'], 404);
        }

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/json',
        ]);
    }
}
