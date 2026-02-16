<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('reference', 20)->nullable()->unique()->after('id');
        });

        $prefix = 'CL-';
        $existing = [];
        foreach (DB::table('clients')->whereNull('reference')->get() as $row) {
            do {
                $ref = $prefix . strtoupper(Str::random(11));
            } while (in_array($ref, $existing, true) || DB::table('clients')->where('reference', $ref)->exists());
            $existing[] = $ref;
            DB::table('clients')->where('id', $row->id)->update(['reference' => $ref]);
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn('reference');
        });
    }
};
