<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\CommandController;
use App\Http\Controllers\Api\DeviceLogController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ProxyAutomationController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Fleet Zone — device automation API (secured with X-API-Key)
|--------------------------------------------------------------------------
| These endpoints control the Android device fleet. They are machine-only
| and must never be callable anonymously.
*/
Route::middleware(['api.key'])->group(function () {
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
    Route::prefix('campaigns')->group(function () {
        Route::get('/', [CampaignController::class, 'index']);
        Route::post('/', [CampaignController::class, 'store']);
        Route::post('/bulk-delete', [CampaignController::class, 'bulkDestroy']);
        Route::put('/{campaign}', [CampaignController::class, 'update']);
        Route::delete('/{campaign}', [CampaignController::class, 'destroy']);
        Route::post('/{campaign}/deploy', [CampaignController::class, 'deploy']);
    });

    // ── Proxy Automation routes ─────────────────────────────────────────────
    Route::get('/proxies/refresh', [ProxyAutomationController::class, 'refresh']);

    // ── Dashboard routes ─────────────────────────────────────────────────────
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
});

/*
|--------------------------------------------------------------------------
| AudioReach v1 — listener mobile API (Sanctum token auth)
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\FeedController;
use App\Http\Controllers\Api\V1\ListenController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\DeviceController as ListenerDeviceController;
use App\Http\Controllers\Api\V1\PaystackWebhookController;

Route::prefix('v1')->group(function () {
    // Public
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::get('/app/latest', [SettingsController::class, 'latest']);
    Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle']);

    // Authenticated (listener only)
    Route::middleware(['auth:sanctum', 'role:listener'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateMe']);

        Route::get('/feed', [FeedController::class, 'index']);
        Route::get('/activities', [ActivityController::class, 'index']);
        Route::post('/listen/{campaign}/start', [ListenController::class, 'start']);
        Route::post('/listen/{session}/checkpoint', [ListenController::class, 'checkpoint']);
        Route::post('/listen/{session}/complete', [ListenController::class, 'complete']);

        Route::get('/wallet', [WalletController::class, 'index']);
        Route::post('/wallet/payout-request', [WalletController::class, 'requestPayout']);

        Route::get('/settings', [SettingsController::class, 'index']);

        Route::post('/device/register', [ListenerDeviceController::class, 'register']);
        Route::post('/device/heartbeat', [ListenerDeviceController::class, 'heartbeat']);
    });
});