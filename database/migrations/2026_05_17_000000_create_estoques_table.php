<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estoque', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hemocentro_id')->constrained('hemocentros')->onDelete('cascade');
            $table->string('tipo_sangue');
            $table->float('quantidade')->default(0);
            $table->float('quantidade_minima')->default(5000);
            $table->timestamp('atualizado_em')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['hemocentro_id', 'tipo_sangue']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estoque');
    }
};
