<?php

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationReportController;
use Illuminate\Support\Facades\Route;

Route::post('/notifications', [NotificationController::class, 'store']);
Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
Route::get('/users/{userId}/notifications', [NotificationController::class, 'index']);

Route::post('/users/{userId}/reports', [NotificationReportController::class, 'store']);
Route::get('/reports/{report}', [NotificationReportController::class, 'show']);
Route::get('/reports/{report}/download', [NotificationReportController::class, 'download']);
