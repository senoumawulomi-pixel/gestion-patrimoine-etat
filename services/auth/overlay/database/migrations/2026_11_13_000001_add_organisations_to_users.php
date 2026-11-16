<?php

use App\Models\Organisation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('code')->unique();
            $table->string('type');
            $table->uuid('parent_id')->nullable();
            $table->timestamps();
            $table->index(['type', 'parent_id']);
        });

        Schema::table('organisations', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('organisations');
        });

        DB::table('organisations')->insert([
            'id' => Organisation::CENTRAL_ID,
            'nom' => 'Administration centrale',
            'code' => 'ETAT-CENTRAL',
            'type' => 'administration_centrale',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignUuid('organisation_id')->nullable()->constrained('organisations');
            $table->index(['organisation_id', 'role']);
        });

        DB::table('users')->whereNull('organisation_id')->update([
            'organisation_id' => Organisation::CENTRAL_ID,
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('organisation_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['organisation_id', 'role']);
            $table->dropConstrainedForeignId('organisation_id');
        });

        Schema::dropIfExists('organisations');
    }
};
