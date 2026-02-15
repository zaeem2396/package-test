<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nats_activities', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32); // published, job_dispatched, job_processed, job_failed, request_reply, delayed_scheduled
            $table->string('summary');
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::table('nats_activities', function (Blueprint $table) {
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nats_activities');
    }
};
