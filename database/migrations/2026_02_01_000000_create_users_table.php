<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // ✅ padrão correto (BIGINT)

            $table->string('name'); 
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();

            $table->enum('tipo_sang', ['A+','A-','B+','B-','AB+','AB-','O+','O-'])->nullable();
            $table->enum('sexo', ['M','F','Outro'])->nullable();
            $table->date('data_nasc')->nullable();
            $table->string('cpf', 14)->unique()->nullable();
            $table->string('telefone', 20)->nullable();

            $table->string('cep', 9)->nullable();
            $table->string('rua')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('complemento')->nullable();
            $table->integer('numero')->nullable();
            $table->char('uf', 2)->nullable();

            $table->boolean('status')->default(true);

            // ✅ agora compatível com BIGINT
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignId('hemocentro_id')->nullable()->constrained('hemocentros')->nullOnDelete();

            // ⚠️ SEM FK por enquanto (auto relacionamento)
            $table->foreignId('criado_por')->nullable();

            $table->timestamp('criado_em')->useCurrent();
            $table->timestamp('atualizado_em')->useCurrent()->useCurrentOnUpdate();

            $table->softDeletes('deletado_em'); // ✅ já cria com nome correto
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};