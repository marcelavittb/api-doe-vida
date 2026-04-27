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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->integer('id_role_permission')->nullable();
            $table->string('nome_role', 255)->nullable();
            $table->string('descricao', 255)->nullable();
            $table->integer('criado_por_id')->nullable();
            $table->integer('deletado_por_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
