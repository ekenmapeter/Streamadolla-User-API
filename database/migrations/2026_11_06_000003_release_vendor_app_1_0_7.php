<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            'vendor_app_version' => '1.0.7',
            'vendor_min_app_version' => '1.0.7',
        ] as $key => $version) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => json_encode($version),
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

        foreach ([
            'vendor_app_version' => '1.0.6',
            'vendor_min_app_version' => '1.0.6',
        ] as $key => $version) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => json_encode($version),
                    'group' => 'app',
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
};
