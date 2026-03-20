<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->decimal('amount', 10, 2);
            $table->string('email');
            $table->string('status', 32)->default('pending'); // pending, processing, failed, completed
            $table->string('current_step', 64)->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->string('workflow_id', 64)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
