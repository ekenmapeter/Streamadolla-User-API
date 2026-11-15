<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1.1.1 resets the Firebase installation once per phone so cloned
        // installs stop sharing one FCM token. Force every vendor onto it.
        foreach ([
            'vendor_app_version' => '1.1.1',
            'vendor_min_app_version' => '1.1.1',
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
                'value' => json_encode('1.1.0'),
                'group' => 'app',
                'description' => 'Current vendor app release version',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'vendor_min_app_version'],
            [
                'value' => json_encode('1.0.9'),
                'group' => 'app',
                'description' => 'Minimum vendor app version the server will accept',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }
};
