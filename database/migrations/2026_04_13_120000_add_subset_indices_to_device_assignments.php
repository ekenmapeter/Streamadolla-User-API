<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('device_assignments', function (Blueprint $table) {
            $table->unsignedInteger('subset_start_index')->nullable()->after('campaign_track_id');
            $table->unsignedInteger('subset_end_index')->nullable()->after('subset_start_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_assignments', function (Blueprint $table) {
            $table->dropColumn(['subset_start_index', 'subset_end_index']);
        });
    }
};
