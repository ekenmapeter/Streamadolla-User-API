<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('campaign_packages')->nullOnDelete();
            $table->string('title');
            $table->string('track_url');
            $table->string('platform', 30)->default('youtube');
            $table->json('genres')->nullable();
            $table->bigInteger('reward_per_review')->default(100);
            $table->integer('listen_target')->default(100);
            $table->integer('review_target')->default(100);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('funded_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_campaigns');
    }
};