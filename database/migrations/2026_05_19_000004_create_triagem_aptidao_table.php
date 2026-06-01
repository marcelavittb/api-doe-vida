<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triagem_aptidao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('triagem_id')
                ->unique()
                ->constrained('triagens')
                ->onDelete('cascade')
                ->comment('Uma aptidao por triagem');
            $table->string('resultado')
                ->comment('Resultado formal da triagem clinica');
            $table->string('categoria_inaptidao')->nullable()->comment('Categoria da inaptidao - nunca expoe diagnostico ao doador');
            $table->text('observacoes_internas')->nullable()
                ->comment('Visivel apenas para funcionarios e diretores - nunca para o doador');
            $table->text('notificacao_doador')->nullable()
                ->comment('Mensagem generica exibida ao doador - sem diagnostico');
            $table->date('valido_ate')->nullable()
                ->comment('Data de termino da restricao para inaptidao temporaria');
            $table->timestamp('criado_em')->useCurrent();
            $table->timestamp('atualizado_em')->nullable()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triagem_aptidao');
    }
};
