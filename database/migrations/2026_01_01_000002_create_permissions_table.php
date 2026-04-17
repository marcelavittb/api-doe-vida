<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();           
            $table->string('nome', 255)->nullable();
            $table->string('modulo', 255)->nullable();
            $table->string('descricao', 255)->nullable();
            $table->dateTime('deletado_em')->nullable();
            $table->dateTime('criado_em')->nullable();
            $table->dateTime('atualizado_em')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
