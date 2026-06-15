<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListUserNotificationsRequest;
use App\Http\Requests\StoreNotificationRequest;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $notification = $this->notificationService->create([
            'user_id' => $request->integer('user_id'),
            'channel' => NotificationChannel::from($request->string('channel')->toString()),
            'message' => $request->string('message')->toString(),
        ]);

        return (new NotificationResource($notification))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $notification): JsonResponse|NotificationResource
    {
        $model = $this->notificationService->find($notification);

        if ($model === null) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        return new NotificationResource($model);
    }

    public function index(
        ListUserNotificationsRequest $request,
        int $userId,
    ): AnonymousResourceCollection {
        $status = $request->filled('status')
            ? NotificationStatus::from($request->string('status')->toString())
            : null;

        $channel = $request->filled('channel')
            ? NotificationChannel::from($request->string('channel')->toString())
            : null;

        $notifications = $this->notificationService->listForUser(
            $userId,
            $status,
            $channel,
            $request->integer('per_page', 15),
        );

        return NotificationResource::collection($notifications);
    }
}
