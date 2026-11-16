<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bien_id')->index(); // référence logique vers patrimoine_db
            $table->string('type');                          // transfert, maintenance, cession, reforme
            $table->string('depuis_service')->nullable();
            $table->string('vers_service')->nullable();
            $table->text('description')->nullable();
            $table->decimal('cout', 15, 2)->nullable();
            $table->date('date_mouvement');
            $table->string('user_name');                     // copie volontaire pour l'audit
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements');
    }
};
