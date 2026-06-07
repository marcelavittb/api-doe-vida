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
        if (Schema::hasTable('triagens') && !Schema::hasColumn('triagens', 'agendamento_id')) {
            Schema::table('triagens', function (Blueprint $table) {
                $table->foreignId('agendamento_id')->nullable()->constrained('agendamento')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('doacao') && !Schema::hasColumn('doacao', 'agendamento_id')) {
            Schema::table('doacao', function (Blueprint $table) {
                $table->foreignId('agendamento_id')->nullable()->constrained('agendamento')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('doacao') && !Schema::hasColumn('doacao', 'triagem_id')) {
            Schema::table('doacao', function (Blueprint $table) {
                $table->foreignId('triagem_id')->nullable()->constrained('triagens')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('triagens') && Schema::hasColumn('triagens', 'agendamento_id')) {
            Schema::table('triagens', function (Blueprint $table) {
                $table->dropForeign(['agendamento_id']);
                $table->dropColumn('agendamento_id');
            });
        }

        if (Schema::hasTable('doacao') && Schema::hasColumn('doacao', 'agendamento_id')) {
            Schema::table('doacao', function (Blueprint $table) {
                $table->dropForeign(['agendamento_id']);
                $table->dropColumn('agendamento_id');
            });
        }

        if (Schema::hasTable('doacao') && Schema::hasColumn('doacao', 'triagem_id')) {
            Schema::table('doacao', function (Blueprint $table) {
                $table->dropForeign(['triagem_id']);
                $table->dropColumn('triagem_id');
            });
        }
    }
};
