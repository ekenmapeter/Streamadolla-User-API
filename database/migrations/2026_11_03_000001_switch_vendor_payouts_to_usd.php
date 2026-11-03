<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Money columns need fractional precision now that vendor streams pay
        // $0.001 each. Listener amounts remain whole Naira.
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->decimal('amount', 18, 6)->change();
            $table->decimal('balance_before', 18, 6)->default(0)->change();
            $table->decimal('balance_after', 18, 6)->default(0)->change();
        });

        Schema::table('payout_requests', function (Blueprint $table) {
            $table->decimal('amount', 18, 6)->change();
        });

        Schema::table('stream_plays', function (Blueprint $table) {
            $table->decimal('reward_amount', 18, 6)->default(0)->change();
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->decimal('total_earned', 18, 6)->default(0)->change();
        });

        if (! Schema::hasColumn('wallet_transactions', 'currency')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->string('currency', 3)->default('NGN')->after('type');
            });
        }

        if (! Schema::hasColumn('payout_requests', 'currency')) {
            Schema::table('payout_requests', function (Blueprint $table) {
                $table->string('currency', 3)->default('NGN')->after('amount');
            });
        }

        // Vendor wallets move from Naira to USD. Per product decision the
        // existing vendor balance and earnings history start from zero.
        $vendorIds = DB::table('users')->where('role', 'vendor')->pluck('id');

        if ($vendorIds->isNotEmpty()) {
            DB::table('wallet_transactions')->whereIn('user_id', $vendorIds)->delete();
            DB::table('payout_requests')->whereIn('user_id', $vendorIds)->delete();
            DB::table('stream_plays')->whereIn('user_id', $vendorIds)->update(['reward_amount' => 0]);
            DB::table('devices')->whereIn('user_id', $vendorIds)->update(['total_earned' => 0]);
        }

        $now = now();
        $settings = [
            'vendor_rate_per_stream' => [
                0.001,
                'vendors',
                'Amount paid to a vendor per streamed assignment on their fleet device (USD)',
            ],
            'vendor_min_payout' => [
                1.0,
                'vendors',
                'Minimum vendor wallet balance (USD) required to request a payout',
            ],
            'usd_ngn_rate' => [
                1344.50,
                'vendors',
                'USD to NGN conversion rate used when paying vendor withdrawals through Paystack',
            ],
            'vendor_app_version' => [
                '1.0.4',
                'app',
                'Current vendor app release version',
            ],
            'vendor_min_app_version' => [
                '1.0.4',
                'app',
                'Minimum vendor app version the server will accept',
            ],
        ];

        foreach ($settings as $key => [$value, $group, $description]) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => json_encode($value),
                    'group' => $group,
                    'description' => $description,
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
            ['key' => 'vendor_rate_per_stream'],
            [
                'value' => json_encode(5),
                'group' => 'vendors',
                'description' => 'Amount paid to a vendor per streamed assignment on their fleet device',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('app_settings')->whereIn('key', [
            'vendor_min_payout',
            'usd_ngn_rate',
            'vendor_app_version',
            'vendor_min_app_version',
        ])->delete();

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('payout_requests', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->bigInteger('amount')->change();
            $table->bigInteger('balance_before')->default(0)->change();
            $table->bigInteger('balance_after')->default(0)->change();
        });

        Schema::table('payout_requests', function (Blueprint $table) {
            $table->bigInteger('amount')->change();
        });

        Schema::table('stream_plays', function (Blueprint $table) {
            $table->bigInteger('reward_amount')->default(0)->change();
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedBigInteger('total_earned')->default(0)->change();
        });
    }
};
