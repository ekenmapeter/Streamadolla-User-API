<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1.1.3 pauses media at the end of a track when the next campaign
        // command is late, ignores stale/out-of-order pushes, and heartbeats
        // every minute so the backend can advance due tracks per phone.
        foreach ([
            'vendor_app_version' => '1.1.3',
            'vendor_min_app_version' => '1.1.3',
        ] as $key => $value) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => json_encode($value),
                    'group' => 'app',
                    'description' => $key === 'vendor_app_version'
                        ? 'Current vendor app release version'
                        : 'Minimum vendor app version the server will accept',
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $now = now();

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'vendor_app_version'],
            [
                'value' => json_encode('1.1.2'),
                'group' => 'app',
                'description' => 'Current vendor app release version',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'vendor_min_app_version'],
            [
                'value' => json_encode('1.1.2'),
                'group' => 'app',
                'description' => 'Minimum vendor app version the server will accept',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }
};
