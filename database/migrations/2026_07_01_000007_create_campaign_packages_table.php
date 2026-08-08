<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->bigInteger('price_ngn');
            $table->integer('listen_target');
            $table->integer('review_target');
            $table->decimal('margin_pct', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_packages');
    }
};