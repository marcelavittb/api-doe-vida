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
        Schema::create('doacao', function (Blueprint $table) {
    $table->id(); // ✅ correto

    $table->foreignId('user_id')->nullable()
          ->constrained('users');

    $table->foreignId('hemocentro_id')->nullable()
          ->constrained('hemocentros');

    $table->foreignId('funcionario_id')->nullable()
          ->constrained('users');

    $table->foreignId('retirado_por')->nullable()
          ->constrained('users');

    $table->dateTime('data_hora_doacao')->nullable();
    $table->dateTime('data_validade_sangue')->nullable();
    $table->dateTime('retirada_em')->nullable();
    
    $table->enum('tipo_sangue', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
    $table->float('quantidade')->nullable();
    $table->float('quantidade_retirada')->nullable();
    $table->dateTime('atualizado_em')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doacao');
    }
};
