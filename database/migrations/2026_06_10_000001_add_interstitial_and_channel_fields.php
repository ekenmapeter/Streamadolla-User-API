<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('channel_url')->nullable()->after('is_active');
            $table->unsignedInteger('interstitial_every')->nullable()->after('channel_url');
            $table->string('interstitial_media_url')->nullable()->after('interstitial_every');
            $table->unsignedInteger('interstitial_duration_seconds')->default(120)->after('interstitial_media_url');
        });

        Schema::table('campaign_tracks', function (Blueprint $table) {
            $table->string('track_type', 20)->default('playlist')->after('duration_seconds');
        });

        Schema::table('device_assignments', function (Blueprint $table) {
            $table->unsignedInteger('shuffled_index')->default(0)->after('subset_end_index');
            $table->unsignedInteger('cycle_track_count')->default(0)->after('shuffled_index');
            $table->boolean('is_interstitial')->default(false)->after('cycle_track_count');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['channel_url', 'interstitial_every', 'interstitial_media_url', 'interstitial_duration_seconds']);
        });

        Schema::table('campaign_tracks', function (Blueprint $table) {
            $table->dropColumn('track_type');
        });

        Schema::table('device_assignments', function (Blueprint $table) {
            $table->dropColumn(['shuffled_index', 'cycle_track_count', 'is_interstitial']);
        });
    }
};
