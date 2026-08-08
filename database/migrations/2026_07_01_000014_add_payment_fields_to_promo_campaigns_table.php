<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_campaigns', function (Blueprint $table) {
            $table->string('payment_reference', 100)->nullable()->unique()->after('status');
            $table->bigInteger('amount_paid_ngn')->nullable()->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('promo_campaigns', function (Blueprint $table) {
            $table->dropColumn(['payment_reference', 'amount_paid_ngn']);
        });
    }
};