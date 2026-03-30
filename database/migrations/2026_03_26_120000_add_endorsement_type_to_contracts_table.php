<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('endorsement_type', 40)->nullable()->after('parent_id');
        });

        if (! Schema::hasColumn('contracts', 'metadata')) {
            return;
        }

        DB::table('contracts')->orderBy('id')->chunk(100, function ($rows): void {
            foreach ($rows as $row) {
                if (($row->endorsement_type ?? null) !== null && $row->endorsement_type !== '') {
                    continue;
                }
                $meta = json_decode($row->metadata ?? 'null', true);
                if (! is_array($meta) || empty($meta['endorsement_type'])) {
                    continue;
                }
                DB::table('contracts')->where('id', $row->id)->update([
                    'endorsement_type' => $meta['endorsement_type'],
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn('endorsement_type');
        });
    }
};
