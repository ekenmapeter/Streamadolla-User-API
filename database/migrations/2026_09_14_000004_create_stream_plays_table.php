<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stream_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rental_id')->nullable()->constrained('device_rentals')->nullOnDelete();
            $table->foreignId('device_assignment_id')->nullable()->unique()->constrained('device_assignments')->nullOnDelete();
            $table->foreignId('track_id')->nullable()->constrained('tracks')->nullOnDelete();
            $table->string('platform', 30)->nullable();
            $table->string('media_url', 500)->nullable();
            $table->bigInteger('reward_amount')->default(0);
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_plays');
    }
};