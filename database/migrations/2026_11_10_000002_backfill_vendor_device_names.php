<?php

use App\Services\VendorDeviceService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(VendorDeviceService::class)->backfillNames();
    }

    public function down(): void
    {
        // Display names are not destructive data; the next phone registration
        // will keep or regenerate them.
    }
};
