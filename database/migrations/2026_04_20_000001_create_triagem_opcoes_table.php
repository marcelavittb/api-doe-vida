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
        Schema::create('triagem_opcoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pergunta_id')->constrained('triagem_perguntas')->cascadeOnDelete();
            $table->string('texto_opcao');
            $table->boolean('gera_inaptidao')->default(false);
            $table->integer('dias_inaptidao')->nullable()->comment('Ex: 90 para 3 meses, 9999 para definitiva. Null = não inabilita.');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('triagem_opcoes');
    }
};