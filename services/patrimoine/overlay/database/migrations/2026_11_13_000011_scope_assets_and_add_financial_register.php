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
        Schema::table('biens', function (Blueprint $table) {
            $table->date('date_acquisition')->nullable()->change();
            $table->decimal('valeur_acquisition', 15, 2)->nullable()->change();
            $table->unsignedSmallInteger('duree_amortissement')->nullable()->change();
            $table->uuid('organisation_id')->nullable();
            $table->string('type_patrimoine')->default('materiel');
            $table->boolean('est_public')->default(false);
            $table->string('zone_publique')->nullable();
            $table->text('description_publique')->nullable();
            $table->index(['organisation_id', 'type_patrimoine', 'est_public']);
        });

        DB::table('biens')->whereNull('organisation_id')->update([
            'organisation_id' => self::CENTRAL_ID,
        ]);

        Schema::table('biens', function (Blueprint $table) {
            $table->uuid('organisation_id')->nullable(false)->change();
            $table->dropUnique(['code']);
            $table->unique(['organisation_id', 'code']);
        });

        Schema::create('engagements_financiers', function (Blueprint $table) {
            $table->id();
            $table->uuid('organisation_id');
            $table->string('code', 50);
            $table->string('nature');
            $table->string('designation', 255);
            $table->string('contrepartie', 255);
            $table->decimal('montant_initial', 15, 2);
            $table->decimal('solde_courant', 15, 2);
            $table->date('date_effet');
            $table->date('date_echeance')->nullable();
            $table->string('statut')->default('actif');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['organisation_id', 'code']);
            $table->index(['organisation_id', 'nature', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagements_financiers');
        Schema::table('biens', function (Blueprint $table) {
            $table->dropIndex(['organisation_id', 'type_patrimoine', 'est_public']);
            $table->dropUnique(['organisation_id', 'code']);
            $table->unique('code');
            $table->dropColumn([
                'organisation_id', 'type_patrimoine', 'est_public',
                'zone_publique', 'description_publique',
            ]);
        });
    }
};
