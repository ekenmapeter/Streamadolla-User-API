<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('device_assignments', function (Blueprint $table) {
            $table->foreignId('track_id')->nullable()->after('campaign_track_id')->constrained('tracks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('device_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('track_id');
        });
    }
};