<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triagens', function (Blueprint $table) {
    $table->id();
    
    // Mudamos para 'restrict' ou apenas deixamos o padrão para não apagar a triagem
        $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
        $table->foreignId('funcionario_id')->constrained('users')->onDelete('restrict');
        $table->foreignId('hemocentro_id')->constrained('hemocentros');
        
        $table->dateTime('data_triagem');
        $table->string('status_triagem')->default('P');
        
        $table->boolean('apto')->default(false);
        $table->text('motivo_inaptidao')->nullable();
        $table->text('observacoes')->nullable();
        
        $table->timestamps();
    });
    }
};   
