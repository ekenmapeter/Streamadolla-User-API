<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('campaign_reviews');
    }

    public function down(): void
    {
        Schema::create('campaign_reviews', function ($table) {
            $table->id();
        });
    }
};
