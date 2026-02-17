<?php

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

// Notification routes
Route::prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
});

// Report routes
Route::prefix('reports')->group(function () {
    Route::post('/{id}/complete', [ReportController::class, 'markAsCompleted']);
    Route::get('/rescuer', [ReportController::class, 'getRescuerReports']);
});

// User routes
Route::post('/users/expo-token', [ReportController::class, 'updateExpoPushToken']);
