<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function database(): string
    {
        return Schema::getConnection()->getDatabaseName();
    }

    private function foreignKeys(string $table, string $column)
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $this->database())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME')
            ->unique()
            ->values();
    }

    private function indexes(string $table, string $column)
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $this->database())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->get(['INDEX_NAME', 'NON_UNIQUE'])
            ->unique('INDEX_NAME')
            ->values();
    }

    public function up(): void
    {
        $table = 'stream_plays';

        $foreignKeys = $this->foreignKeys($table, 'device_assignment_id');
        foreach ($foreignKeys as $foreignKey) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign($foreignKey));
        }

        $uniqueIndexes = $this->indexes($table, 'device_assignment_id')
            ->where('NON_UNIQUE', 0);
        foreach ($uniqueIndexes as $index) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique($index->INDEX_NAME));
        }

        $hasSupportingIndex = $this->indexes($table, 'device_assignment_id')
            ->where('NON_UNIQUE', 1)
            ->isNotEmpty();
        if (! $hasSupportingIndex) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index('device_assignment_id'));
        }

        // Only restore the FK if it existed before this migration.
        if ($foreignKeys->isNotEmpty()) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint
                ->foreign('device_assignment_id')
                ->references('id')
                ->on('device_assignments')
                ->nullOnDelete());
        }

        Schema::table($table, function (Blueprint $blueprint) {
            if (! Schema::hasColumn('stream_plays', 'device_session_id')) {
                $blueprint->foreignId('device_session_id')->nullable()->after('device_assignment_id');
            }
            if (! Schema::hasColumn('stream_plays', 'media_title')) {
                $blueprint->string('media_title')->nullable()->after('media_url');
            }
        });

        if ($this->foreignKeys($table, 'device_session_id')->isEmpty()) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint
                ->foreign('device_session_id')
                ->references('id')
                ->on('device_sessions')
                ->nullOnDelete());
        }

        $hasSessionUnique = $this->indexes($table, 'device_session_id')
            ->where('NON_UNIQUE', 0)
            ->isNotEmpty();
        if (! $hasSessionUnique) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique('device_session_id'));
        }
    }

    public function down(): void
    {
        $table = 'stream_plays';

        $sessionForeignKeys = $this->foreignKeys($table, 'device_session_id');
        foreach ($sessionForeignKeys as $foreignKey) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign($foreignKey));
        }

        $sessionUniqueIndexes = $this->indexes($table, 'device_session_id')
            ->where('NON_UNIQUE', 0);
        foreach ($sessionUniqueIndexes as $index) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique($index->INDEX_NAME));
        }

        Schema::table($table, function (Blueprint $blueprint) {
            if (Schema::hasColumn('stream_plays', 'media_title')) {
                $blueprint->dropColumn('media_title');
            }
            if (Schema::hasColumn('stream_plays', 'device_session_id')) {
                $blueprint->dropColumn('device_session_id');
            }
        });

        $assignmentForeignKeys = $this->foreignKeys($table, 'device_assignment_id');
        foreach ($assignmentForeignKeys as $foreignKey) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign($foreignKey));
        }

        $assignmentIndexes = $this->indexes($table, 'device_assignment_id')
            ->where('NON_UNIQUE', 1);
        foreach ($assignmentIndexes as $index) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($index->INDEX_NAME));
        }

        $hasAssignmentUnique = $this->indexes($table, 'device_assignment_id')
            ->where('NON_UNIQUE', 0)
            ->isNotEmpty();
        if (! $hasAssignmentUnique) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique('device_assignment_id'));
        }

        if ($assignmentForeignKeys->isNotEmpty()) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint
                ->foreign('device_assignment_id')
                ->references('id')
                ->on('device_assignments')
                ->nullOnDelete());
        }
    }
};
