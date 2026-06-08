<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('name', 'idx_users_name');
            $table->index('cidade', 'idx_users_cidade');
            $table->index('tipo_sang', 'idx_users_tipo_sang');
            $table->index('status', 'idx_users_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_name');
            $table->dropIndex('idx_users_cidade');
            $table->dropIndex('idx_users_tipo_sang');
            $table->dropIndex('idx_users_status');
        });
    }
};
