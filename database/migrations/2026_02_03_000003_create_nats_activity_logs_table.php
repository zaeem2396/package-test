<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nats_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->index(); // task_queued, task_completed, task_failed, email_sent
            $table->string('message');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nats_activity_logs');
    }
};
