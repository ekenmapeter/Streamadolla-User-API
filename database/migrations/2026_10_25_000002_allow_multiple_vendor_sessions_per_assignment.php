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
        $table = 'device_sessions';

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
                ->cascadeOnDelete());
        }
    }

    public function down(): void
    {
        $table = 'device_sessions';

        $foreignKeys = $this->foreignKeys($table, 'device_assignment_id');
        foreach ($foreignKeys as $foreignKey) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign($foreignKey));
        }

        $indexes = $this->indexes($table, 'device_assignment_id')
            ->where('NON_UNIQUE', 1);
        foreach ($indexes as $index) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($index->INDEX_NAME));
        }

        $hasUnique = $this->indexes($table, 'device_assignment_id')
            ->where('NON_UNIQUE', 0)
            ->isNotEmpty();
        if (! $hasUnique) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique('device_assignment_id'));
        }

        if ($foreignKeys->isNotEmpty()) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint
                ->foreign('device_assignment_id')
                ->references('id')
                ->on('device_assignments')
                ->cascadeOnDelete());
        }
    }
};
