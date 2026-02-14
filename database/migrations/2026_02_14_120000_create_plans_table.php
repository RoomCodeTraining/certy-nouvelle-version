<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('price_monthly')->default(0); // XOF
            $table->unsignedInteger('limits_documents')->default(10);
            $table->unsignedInteger('limits_assistant_calls_per_month')->default(100);
            $table->unsignedBigInteger('limits_ai_tokens_per_month')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
