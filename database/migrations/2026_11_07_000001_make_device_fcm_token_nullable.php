<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // VendorDeviceService clears a token from an old device row when a
        // phone re-registers. The column must allow NULL for that update to
        // succeed instead of aborting registration.
        Schema::table('devices', function (Blueprint $table) {
            $table->text('fcm_token')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->text('fcm_token')->nullable(false)->change();
        });
    }
};
