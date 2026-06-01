<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendamento', function (Blueprint $table) {
            // 1. Usar o padrão moderno (BigIncrements) para bater com a tabela Users
            $table->id(); 

            // 2. Definir as colunas com foreignId (já cria como UnsignedBigInteger)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            
            // A coluna do Membro 3 (Funcionário)
            $table->foreignId('coletador_id')->nullable()->constrained('users')->onDelete('set null');

            // Cuidado aqui: sua tabela de hemocentro está no singular ou plural? 
            // Se for 'hemocentros', mude aqui embaixo:
            $table->foreignId('hemocentro_id')->nullable()->constrained('hemocentros')->onDelete('cascade');
            
            $table->dateTime('data_hora_doacao')->nullable();
            $table->string('status_agendamento')->default('AGE');
            
            $table->timestamp('criado_em')->useCurrent();
            $table->timestamp('atualizado_em')->useCurrent()->useCurrentOnUpdate();
            
            // Forma correta de renomear o softDeletes
            $table->softDeletes('deletado_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamento');
    }
};