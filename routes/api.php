<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\CommandController;
use App\Http\Controllers\Api\DeviceLogController;
use App\Http\Controllers\Api\AssignmentController;

Route::prefix('devices')->group(function () {
    Route::post('/register', [DeviceController::class, 'register']);
    Route::post('/heartbeat/{device}', [DeviceController::class, 'heartbeat']);
    Route::get('/', [DeviceController::class, 'index']);
    Route::put('/{device}', [DeviceController::class, 'update']);
    Route::delete('/{device}', [DeviceController::class, 'destroy']);

    // Device logging
    Route::post('/log', [DeviceLogController::class, 'store']);
    Route::post('/logs/batch', [DeviceLogController::class, 'storeBatch']);
    Route::get('/logs', [DeviceLogController::class, 'index']);
});

Route::prefix('commands')->group(function () {
    Route::post('/send-to-all', [CommandController::class, 'sendToAll']);
    Route::post('/send-to-device/{device}', [CommandController::class, 'sendToDevice']);
    Route::post('/send-to-group', [CommandController::class, 'sendToGroup']);
});

// ── Assignment routes ────────────────────────────────────────────────────
Route::prefix('assignments')->group(function () {
    Route::get('/', [AssignmentController::class, 'index']);
    Route::post('/', [AssignmentController::class, 'store']);
    Route::put('/{assignment}/status', [AssignmentController::class, 'updateStatus']);
    Route::post('/{assignment}/control', [AssignmentController::class, 'control']);
    Route::post('/{assignment}/next', [AssignmentController::class, 'nextTrack']);
    Route::delete('/{assignment}', [AssignmentController::class, 'destroy']);
});

// ── Campaign routes ─────────────────────────────────────────────────────
use App\Http\Controllers\CampaignController;

Route::prefix('campaigns')->group(function () {
    Route::get('/', [CampaignController::class, 'index']);
    Route::post('/', [CampaignController::class, 'store']);
    Route::post('/bulk-delete', [CampaignController::class, 'bulkDestroy']);
    Route::put('/{campaign}', [CampaignController::class, 'update']);
    Route::delete('/{campaign}', [CampaignController::class, 'destroy']);
    Route::post('/{campaign}/deploy', [CampaignController::class, 'deploy']);
});

// ── Proxy Automation routes ─────────────────────────────────────────────
use App\Http\Controllers\ProxyAutomationController;
Route::get('/proxies/refresh', [ProxyAutomationController::class, 'refresh']);

// ── Dashboard routes ─────────────────────────────────────────────────────
use App\Http\Controllers\DashboardController;
Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
