<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas_medicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('O doador que recebera o alerta');
            $table->foreignId('hemocentro_id')
                ->constrained('hemocentros')
                ->onDelete('cascade');
            $table->foreignId('criado_por')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('Funcionario que criou o alerta');
            $table->string('tipo_alerta');
            $table->string('status')
                ->default('pendente');
            $table->text('notificacao_doador')
                ->comment('Mensagem generica exibida ao doador - sem diagnostico');
            $table->timestamp('criado_em')->useCurrent();
            $table->timestamp('atualizado_em')->nullable()->useCurrentOnUpdate();
            $table->softDeletes('deletado_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas_medicos');
    }
};
