<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'vendor_app_version'],
            [
                'value' => json_encode('1.1.0'),
                'group' => 'app',
                'description' => 'Current vendor app release version',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        $now = now();

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'vendor_app_version'],
            [
                'value' => json_encode('1.0.9'),
                'group' => 'app',
                'description' => 'Current vendor app release version',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }
};
