<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listen_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listener_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('campaign_assignments')->cascadeOnDelete();
            $table->string('session_token', 64)->unique();
            $table->integer('min_duration_seconds')->default(30);
            $table->integer('elapsed_seconds')->default(0);
            $table->json('checkpoints')->nullable();
            $table->boolean('foreground')->default(true);
            $table->string('status', 20)->default('open')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listen_sessions');
    }
};