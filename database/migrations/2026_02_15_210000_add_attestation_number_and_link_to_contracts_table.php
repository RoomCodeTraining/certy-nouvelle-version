<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('attestation_number', 100)->nullable()->after('attestation_issued_at');
            $table->string('attestation_link', 500)->nullable()->after('attestation_number');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['attestation_number', 'attestation_link']);
        });
    }
};
