<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poc_demo_logs', function (Blueprint $table) {
            $table->id();
            $table->string('scenario', 80);
            $table->string('subject_or_connection', 120)->nullable();
            $table->json('payload')->nullable();
            $table->boolean('success');
            $table->text('message')->nullable();
            $table->timestamps();
            $table->index(['scenario', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poc_demo_logs');
    }
};
