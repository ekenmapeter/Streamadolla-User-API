<?php

use App\Models\User;
use App\Services\VendorDeviceService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Some phones saved their UserDevice row but failed to create the
        // corresponding fleet Device (the old NOT NULL fcm_token error). Make
        // those devices visible immediately, without waiting for a re-login.
        $service = app(VendorDeviceService::class);

        User::where('role', User::ROLE_VENDOR)
            ->with('userDevices')
            ->each(function (User $user) use ($service) {
                foreach ($user->userDevices as $userDevice) {
                    $service->syncFleetDevice($user, $userDevice);
                }
            });
    }

    public function down(): void
    {
        // No data rollback.
    }
};
