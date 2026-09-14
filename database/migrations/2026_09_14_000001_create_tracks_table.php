<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('campaign_packages')->nullOnDelete();
            $table->string('title');
            $table->string('artist_name')->nullable();
            $table->string('platform', 30)->default('youtube');
            $table->string('media_url', 500);
            $table->string('genre', 50)->nullable();
            $table->unsignedInteger('duration_seconds')->default(180);
            $table->string('status', 20)->default('draft')->index();
            $table->string('payment_reference', 100)->nullable()->unique();
            $table->bigInteger('amount_paid_ngn')->default(0);
            $table->unsignedInteger('stream_budget')->default(0);
            $table->unsignedInteger('streams_served')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};