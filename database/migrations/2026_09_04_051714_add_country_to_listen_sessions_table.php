<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('listen_sessions', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable()->after('session_token');
            $table->string('ip_address', 45)->nullable()->after('country_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listen_sessions', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'ip_address']);
        });
    }
};