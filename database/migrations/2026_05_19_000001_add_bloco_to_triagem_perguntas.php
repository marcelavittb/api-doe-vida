<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('triagem_perguntas') || Schema::hasColumn('triagem_perguntas', 'bloco')) {
            return;
        }

        Schema::table('triagem_perguntas', function (Blueprint $table) {
            $table->unsignedSmallInteger('bloco')->default(1)
                ->comment('0=pre_triagem, 1=estado_geral, 3=historico_recente, 4=historico_comportamental');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('triagem_perguntas') || !Schema::hasColumn('triagem_perguntas', 'bloco')) {
            return;
        }

        Schema::table('triagem_perguntas', function (Blueprint $table) {
            $table->dropColumn('bloco');
        });
    }
};
