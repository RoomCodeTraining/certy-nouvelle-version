<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false)->comment('Recevoir l\'export automatiquement');
            $table->string('frequency', 20)->default('quinzaine')->comment('daily, weekly, monthly, quinzaine');
            $table->unsignedTinyInteger('day_of_week')->nullable()->comment('1=lundi..7=dimanche pour weekly');
            $table->unsignedTinyInteger('day_of_month')->nullable()->comment('1-31 pour monthly');
            $table->time('time')->default('08:00')->comment('Heure d\'envoi');
            $table->text('emails')->nullable()->comment('Emails séparés par virgule pour recevoir l\'export');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_settings');
    }
};
