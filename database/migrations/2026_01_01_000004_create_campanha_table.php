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
        Schema::create('campanha', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hemocentro_id')->nullable();
            $table->string('titulo', 255)->nullable();
            $table->string('subtitulo', 255)->nullable();
            $table->string('descricao', 255)->nullable();
            $table->string('img', 255)->nullable();
            $table->string('criado_por', 255)->nullable();
            $table->dateTime('data_publi')->nullable();
            $table->dateTime('data_expiracao')->nullable();
            $table->enum('tipo_sangue', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->boolean('status')->nullable();
            $table->dateTime('criacao_em')->nullable();
            $table->dateTime('atualizado_em')->nullable();
            $table->dateTime('deletado_em')->nullable();
            $table->timestamps();

            $table->foreign('hemocentro_id')
            ->references('id')
            ->on('hemocentros')
            ->nullOnDelete();       
             });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campanha');
    }
};
