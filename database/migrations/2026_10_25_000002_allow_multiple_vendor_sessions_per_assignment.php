<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A campaign assignment now spans many tracks, so each track play
        // needs its own vendor session and reward record.
        Schema::table('device_sessions', function (Blueprint $table) {
            $table->dropForeign(['device_assignment_id']);
        });

        Schema::table('device_sessions', function (Blueprint $table) {
            $table->dropUnique(['device_assignment_id']);
            $table->index('device_assignment_id');
        });

        Schema::table('device_sessions', function (Blueprint $table) {
            $table->foreign('device_assignment_id')
                ->references('id')
                ->on('device_assignments')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('device_sessions', function (Blueprint $table) {
            $table->dropForeign(['device_assignment_id']);
            $table->dropIndex(['device_assignment_id']);
        });

        Schema::table('device_sessions', function (Blueprint $table) {
            $table->unique('device_assignment_id');
            $table->foreign('device_assignment_id')
                ->references('id')
                ->on('device_assignments')
                ->cascadeOnDelete();
        });
    }
};
