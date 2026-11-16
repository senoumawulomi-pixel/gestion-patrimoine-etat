<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->timestamps();
        });

        Schema::create('biens', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('designation');
            $table->foreignId('categorie_id')->constrained('categories');
            $table->string('localisation')->nullable();
            $table->string('service_affecte')->nullable();
            $table->string('etat')->default('bon');      // neuf, bon, usage, hors_service
            $table->string('statut')->default('actif');  // actif, cede, reforme
            $table->date('date_acquisition');
            $table->decimal('valeur_acquisition', 15, 2);
            $table->unsignedSmallInteger('duree_amortissement')->default(5);
            $table->timestamps();
            $table->index(['statut', 'categorie_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biens');
        Schema::dropIfExists('categories');
    }
};
