<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    private const PERMISSIONS = [
        'ver_agendamentos',
        'criar_agendamentos',
        'confirmar_agendamentos',
        'cancelar_agendamentos',
        'ver_doacoes',
        'registrar_doacoes',
        'ver_triagens',
        'registrar_triagem',
        'ver_estoque',
        'gerenciar_estoque',
        'ver_alertas_medicos',
        'gerenciar_alertas_medicos',
        'ver_usuarios',
        'criar_usuarios',
        'excluir_usuarios',
        'ver_hemocentros',
        'gerenciar_hemocentros',
        'ver_campanhas',
        'gerenciar_campanhas',
        'disparar_campanhas',
        'ver_estatisticas_hemocentro',
        'ver_estatisticas_globais',
        'exportar_relatorios',
    ];

    public function run(): void
    {
        Role::firstOrCreate(['name' => 'doador', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'funcionario', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'diretor', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'enfermeiro', 'guard_name' => 'api']);

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }
    }
} 
