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
        // Adicionando agendamento_id na triagem
        Schema::table('triagens', function (Blueprint $table) {
            $table->foreignId('agendamento_id')->nullable()->after('id')->constrained('agendamento')->onDelete('cascade');
        });

        // Adicionando vínculos na doação
        Schema::table('doacao', function (Blueprint $table) {
            $table->foreignId('agendamento_id')->nullable()->after('id')->constrained('agendamento')->onDelete('cascade');
            $table->foreignId('triagem_id')->nullable()->after('agendamento_id')->constrained('triagens')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('triagens', function (Blueprint $table) {
            $table->dropForeign(['agendamento_id']);
            $table->dropColumn('agendamento_id');
        });

        Schema::table('doacao', function (Blueprint $table) {
            $table->dropForeign(['agendamento_id']);
            $table->dropForeign(['triagem_id']);
            $table->dropColumn(['agendamento_id', 'triagem_id']);
        });
    }
};
