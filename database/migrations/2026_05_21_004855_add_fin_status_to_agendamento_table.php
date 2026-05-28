<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // No MySQL, para alterar um ENUM, precisamos redeclarar a coluna
        // Como o Laravel não tem um método nativo perfeito para ENUM em 'change', 
        // usamos DB::statement para garantir compatibilidade com MySQL.
        DB::statement("ALTER TABLE agendamento MODIFY COLUMN status_agendamento ENUM('AGE', 'CAN', 'CON', 'EXC', 'FIN') DEFAULT 'AGE'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE agendamento MODIFY COLUMN status_agendamento ENUM('AGE', 'CAN', 'CON', 'EXC') DEFAULT 'AGE'");
    }
};
