<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_root')->default(false)->after('external_entity_code');
            $table->string('user_role_code', 80)->nullable()->after('is_root');
            $table->string('user_role_name', 80)->nullable()->after('user_role_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_root', 'user_role_code', 'user_role_name']);
        });
    }
};
