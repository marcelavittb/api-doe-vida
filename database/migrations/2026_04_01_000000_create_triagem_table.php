<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
       Schema::create('triagem', function (Blueprint $table) {
    $table->id();

    // ✅ FKs corretas (BIGINT + padrão)
    $table->foreignId('user_id')->nullable()
          ->constrained('users')
          ->nullOnDelete();

    $table->foreignId('funcionario_id')->nullable()
          ->constrained('users')
          ->nullOnDelete();

    $table->foreignId('hemocentro_id')->nullable()
          ->constrained('hemocentros') // 👈 plural, como você quer
          ->nullOnDelete();

    $table->enum('tipo', ['doacao', 'avaliacao'])->nullable();
    $table->boolean('doenca')->nullable();
    $table->enum('lista_doencas', ['nenhuma', 'hepatite', 'hiv', 'outra'])->nullable();
    $table->enum('gravidez', ['sim', 'nao'])->nullable();
    $table->boolean('tatuagens')->nullable();
    $table->enum('tatuagem_recente', ['sim', 'nao'])->nullable();
    $table->boolean('medicamento')->nullable();
    $table->enum('lista_medicamentos', ['nenhum', 'antibiotico', 'outro'])->nullable();
    $table->enum('tipo_sangue', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
    $table->dateTime('data_hora_doacao')->nullable();
    $table->enum('status', ['aprovado', 'reprovado'])->nullable();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('triagem');
    }
};
