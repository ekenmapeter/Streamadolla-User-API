<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The original unique index is also the supporting index for the
        // device_assignments foreign key, so drop the FK before the index.
        Schema::table('stream_plays', function (Blueprint $table) {
            $table->dropForeign(['device_assignment_id']);
        });

        Schema::table('stream_plays', function (Blueprint $table) {
            $table->dropUnique(['device_assignment_id']);
            $table->index('device_assignment_id');
        });

        Schema::table('stream_plays', function (Blueprint $table) {
            $table->foreign('device_assignment_id')
                ->references('id')
                ->on('device_assignments')
                ->nullOnDelete();

            // Campaigns reuse one assignment across many tracks, so a reward
            // must be unique per vendor session (track play), not per
            // assignment.
            $table->foreignId('device_session_id')
                ->nullable()
                ->after('device_assignment_id')
                ->constrained('device_sessions')
                ->nullOnDelete();

            $table->string('media_title')->nullable()->after('media_url');
            $table->unique('device_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('stream_plays', function (Blueprint $table) {
            $table->dropUnique(['device_session_id']);
        });

        Schema::table('stream_plays', function (Blueprint $table) {
            $table->dropConstrainedForeignId('device_session_id');
            $table->dropColumn('media_title');
        });

        Schema::table('stream_plays', function (Blueprint $table) {
            $table->dropForeign(['device_assignment_id']);
            $table->dropIndex(['device_assignment_id']);
        });

        Schema::table('stream_plays', function (Blueprint $table) {
            $table->unique('device_assignment_id');
            $table->foreign('device_assignment_id')
                ->references('id')
                ->on('device_assignments')
                ->nullOnDelete();
        });
    }
};
