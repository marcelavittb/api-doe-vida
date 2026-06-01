<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_triagem_respostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('pergunta_id')
                ->constrained('triagem_perguntas')
                ->onDelete('cascade');
            $table->foreignId('opcao_id')
                ->constrained('triagem_opcoes')
                ->onDelete('cascade');
            $table->string('resultado_geral')->nullable()
                ->comment('Resultado geral calculado ao final do questionario');
            $table->timestamp('respondido_em')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_triagem_respostas');
    }
};
