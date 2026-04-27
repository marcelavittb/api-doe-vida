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
        Schema::create('triagem_perguntas', function (Blueprint $table) {
            $table->id();
            $table->string('pergunta');
            $table->boolean('obrigatoria')->default(true);
            $table->boolean('status')->default(true)->comment('Define se a pergunta está ativa no formulário');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('triagem_perguntas');
    }
};