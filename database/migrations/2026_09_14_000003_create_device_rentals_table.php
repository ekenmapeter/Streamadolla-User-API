<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->bigInteger('rate_per_stream')->default(5);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('terminated_at')->nullable();
            $table->unsignedInteger('total_streams')->default(0);
            $table->bigInteger('total_earned')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_rentals');
    }
};