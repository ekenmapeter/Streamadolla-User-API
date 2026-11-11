<?php

use App\Models\User;
use App\Services\VendorDeviceService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FCM tokens are not a stable device identity: a restored/cloned
        // install can briefly reuse the source phone's token, and a removed
        // (soft-deleted) row keeps its token. A unique index turns those
        // normal cases into a registration failure, which stops the app from
        // heartbeating and makes the phone vanish from the fleet.
        $this->dropUniqueTokenIndex();

        // Repair phones that already failed to mirror because of the index.
        $service = app(VendorDeviceService::class);

        User::where('role', User::ROLE_VENDOR)
            ->with('userDevices')
            ->each(function (User $user) use ($service) {
                foreach ($user->userDevices as $userDevice) {
                    try {
                        $service->syncFleetDevice($user, $userDevice);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            });
    }

    public function down(): void
    {
        // Restore the old constraint, keeping only one row per token so the
        // migration does not fail on data created after the index was dropped.
        DB::table('devices')
            ->whereNotNull('fcm_token')
            ->select('fcm_token')
            ->groupBy('fcm_token')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('fcm_token')
            ->each(function (string $token) {
                $keepId = DB::table('devices')
                    ->where('fcm_token', $token)
                    ->orderByDesc('id')
                    ->value('id');

                DB::table('devices')
                    ->where('fcm_token', $token)
                    ->where('id', '!=', $keepId)
                    ->update(['fcm_token' => null]);
            });

        Schema::table('devices', function (Blueprint $table) {
            $table->unique('fcm_token');
        });
    }

    private function dropUniqueTokenIndex(): void
    {
        if (! Schema::hasTable('devices')) {
            return;
        }

        try {
            Schema::table('devices', function (Blueprint $table) {
                $table->dropUnique('devices_fcm_token_unique');
            });
        } catch (\Throwable $e) {
            // The index may already be gone (fresh installs migrate the
            // unique column first), so this is safe to ignore.
        }
    }
};
