<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\ArtistAuthController;
use App\Http\Controllers\DeviceAssignmentController;
use App\Http\Controllers\AdminCommandCenterController;

// Public landing page for AudioReach
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Artist signup / verify
Route::get('/artists/signup', [ArtistAuthController::class, 'showSignup'])->name('artist.signup');
Route::post('/artists/signup', [ArtistAuthController::class, 'signup'])->name('artist.signup.submit');
Route::get('/artists/verify', [ArtistAuthController::class, 'showVerify'])->name('artist.verify');
Route::post('/artists/verify', [ArtistAuthController::class, 'verify'])->name('artist.verify.submit');

// Auth Routes
Route::get('/login', [ArtistAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [ArtistAuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [ArtistAuthController::class, 'logout'])->name('logout');

// Artist portal (protected)
Route::middleware(['auth', 'role:artist,admin'])->prefix('artist')->name('artist.')->group(function () {
    Route::get('/dashboard', [ArtistController::class, 'dashboard'])->name('dashboard');
    Route::get('/campaigns/create', [ArtistController::class, 'createCampaign'])->name('campaign.create');
    Route::post('/campaigns', [ArtistController::class, 'storeCampaign'])->name('campaign.store');
    Route::get('/campaigns/{promo_campaign}', [ArtistController::class, 'showCampaign'])->name('campaign.show');
});

// Legacy fleet dashboard (admin only)
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/fleet', [DashboardController::class, 'index'])->name('fleet.dashboard');

    // Command sending route (broadcast to all)
    Route::post('/send-command', [DashboardController::class, 'sendCommand'])
        ->name('send.command');

    // Task assignment route (assign to specific devices)
    Route::post('/assign-task', [DashboardController::class, 'assignTask'])
        ->name('assign.task');

    Route::get('/device-assignments', [DeviceAssignmentController::class, 'index'])->name('assignments.index');
    Route::post('/logs/clear', [DashboardController::class, 'clearLogs'])->name('logs.clear');
    Route::get('/device-assignments/stats', [DeviceAssignmentController::class, 'stats'])->name('assignments.stats');
    Route::post('/device-assignments/clear', [DeviceAssignmentController::class, 'clearAll'])->name('assignments.clear');
    Route::post('/device-assignments/bulk-delete', [DeviceAssignmentController::class, 'bulkDelete'])->name('assignments.bulk-delete');
    Route::post('/device-assignments/run-worker', [DeviceAssignmentController::class, 'runWorker'])->name('assignments.worker');
    Route::post('/device-assignments/{assignment}/delete', [DeviceAssignmentController::class, 'destroySingle'])->name('assignments.destroy');

    // ── AudioReach Command Center (admin) ──
    Route::get('/admin', [AdminCommandCenterController::class, 'index'])->name('admin.center');
    Route::get('/admin/campaigns', [AdminCommandCenterController::class, 'campaigns'])->name('admin.campaigns');
    Route::post('/admin/campaigns/{campaign}/activate', [AdminCommandCenterController::class, 'activateCampaign'])->name('admin.campaigns.activate');
    Route::post('/admin/campaigns/{campaign}/pause', [AdminCommandCenterController::class, 'pauseCampaign'])->name('admin.campaigns.pause');
    Route::get('/admin/payouts', [AdminCommandCenterController::class, 'payouts'])->name('admin.payouts');
    Route::get('/admin/listeners', [AdminCommandCenterController::class, 'listeners'])->name('admin.listeners');
    Route::get('/admin/api-docs', [AdminCommandCenterController::class, 'apiDocs'])->name('admin.api-docs');
    Route::post('/admin/payouts/{payout}/mark-paid', [AdminCommandCenterController::class, 'markPayoutPaid'])->name('admin.payouts.mark-paid');
    Route::post('/admin/payouts/{payout}/reject', [AdminCommandCenterController::class, 'rejectPayout'])->name('admin.payouts.reject');
    Route::get('/admin/app-settings', [AdminCommandCenterController::class, 'appSettings'])->name('admin.settings');
    Route::post('/admin/app-settings/save', [AdminCommandCenterController::class, 'saveAppSettings'])->name('admin.settings.save');
    Route::post('/admin/push', [AdminCommandCenterController::class, 'sendPush'])->name('admin.push');
    Route::post('/admin/listeners/{user}/trust', [AdminCommandCenterController::class, 'setTrustLevel'])->name('admin.listeners.trust');
});