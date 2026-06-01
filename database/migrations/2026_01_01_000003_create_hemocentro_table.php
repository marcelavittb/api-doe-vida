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
        Schema::create('hemocentros', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('bairro', 255)->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('endereco', 255)->nullable();
            $table->string('cidade', 255)->nullable();
            $table->integer('numero')->nullable();
            $table->string('complemento', 255)->nullable();
            $table->string('razao_social', 255)->nullable();
            $table->string('cnpj', 20)->nullable();
            $table->string('status_agendamento')->nullable();
            $table->dateTime('criado_em')->nullable();
            $table->string('criado_por', 255)->nullable();
            $table->dateTime('deletado_em')->nullable();
            $table->dateTime('atualizado_em')->nullable();
            $table->boolean('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hemocentros');
    }
};
