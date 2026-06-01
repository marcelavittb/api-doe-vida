<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('estoque') && Schema::hasTable('estoques')) {
            Schema::rename('estoques', 'estoque');
        }

        if (!Schema::hasTable('estoque')) {
            Schema::create('estoque', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hemocentro_id')->constrained('hemocentros')->onDelete('cascade');
                $table->string('tipo_sangue');
                $table->float('quantidade')->default(0);
                $table->float('quantidade_minima')->default(5000);
                $table->timestamp('atualizado_em')->useCurrent()->useCurrentOnUpdate();

                $table->unique(['hemocentro_id', 'tipo_sangue']);
            });

            return;
        }

        Schema::table('estoque', function (Blueprint $table) {
            if (Schema::hasColumn('estoque', 'quantidade_ml') && !Schema::hasColumn('estoque', 'quantidade')) {
                $table->renameColumn('quantidade_ml', 'quantidade');
            }

            if (!Schema::hasColumn('estoque', 'quantidade_minima')) {
                $table->float('quantidade_minima')->default(5000)->after('quantidade');
            }

            if (!Schema::hasColumn('estoque', 'atualizado_em')) {
                $table->timestamp('atualizado_em')->useCurrent()->useCurrentOnUpdate()->after('quantidade_minima');
            }
        });

        Schema::table('estoque', function (Blueprint $table) {
            if (Schema::hasColumn('estoque', 'quantidade_bolsas')) {
                $table->dropColumn('quantidade_bolsas');
            }

            if (Schema::hasColumn('estoque', 'nivel')) {
                $table->dropColumn('nivel');
            }

            if (Schema::hasColumn('estoque', 'created_at')) {
                $table->dropColumn('created_at');
            }

            if (Schema::hasColumn('estoque', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('estoque') && !Schema::hasTable('estoques')) {
            Schema::rename('estoque', 'estoques');
        }
    }
};
