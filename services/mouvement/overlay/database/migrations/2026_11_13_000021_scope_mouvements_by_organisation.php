<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CENTRAL_ID = '00000000-0000-4000-8000-000000000001';

    public function up(): void
    {
        Schema::table('mouvements', function (Blueprint $table) {
            $table->uuid('organisation_id')->nullable();
        });

        DB::table('mouvements')->whereNull('organisation_id')->update([
            'organisation_id' => self::CENTRAL_ID,
        ]);

        Schema::table('mouvements', function (Blueprint $table) {
            $table->index(['organisation_id', 'bien_id']);
        });
    }

    public function down(): void
    {
        Schema::table('mouvements', function (Blueprint $table) {
            $table->dropIndex(['organisation_id', 'bien_id']);
            $table->dropColumn('organisation_id');
        });
    }
};
