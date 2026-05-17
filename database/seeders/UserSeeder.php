<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roleDoador = $this->role('doador');
        $roleFuncionario = $this->role('funcionario');
        $roleDiretor = $this->role('diretor');
        $roleAdmin = $this->role('admin');
        $tipos = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

        for ($i = 1; $i <= 8; $i++) {
            $user = User::updateOrCreate(
                ['email' => "doador$i@email.com"],
                [
                    'name' => "Doador Exemplo $i",
                    'password' => Hash::make('password'),
                    'cpf' => "1234567890$i",
                    'tipo_sang' => $tipos[array_rand($tipos)],
                    'sexo' => ($i % 2 == 0) ? 'M' : 'F',
                    'telefone' => "(11) 99999-000$i",
                    'data_nasc' => '1990-01-01',
                    'role_id' => $roleDoador->id,
                ]
            );

            $user->syncRoles([$roleDoador]);
        }

        $funcionario1 = User::updateOrCreate(
            ['email' => 'funcionario1@email.com'],
            [
                'name' => 'Funcionario H1',
                'password' => Hash::make('password'),
                'cpf' => '11111111111',
                'role_id' => $roleFuncionario->id,
                'hemocentro_id' => 1,
            ]
        );
        $funcionario1->syncRoles([$roleFuncionario]);

        $funcionario2 = User::updateOrCreate(
            ['email' => 'funcionario2@email.com'],
            [
                'name' => 'Funcionario H2',
                'password' => Hash::make('password'),
                'cpf' => '22222222222',
                'role_id' => $roleFuncionario->id,
                'hemocentro_id' => 2,
            ]
        );
        $funcionario2->syncRoles([$roleFuncionario]);

        $diretor = User::updateOrCreate(
            ['email' => 'diretor@email.com'],
            [
                'name' => 'Diretor H1',
                'password' => Hash::make('password'),
                'cpf' => '33333333333',
                'role_id' => $roleDiretor->id,
                'hemocentro_id' => 1,
            ]
        );
        $diretor->syncRoles([$roleDiretor]);

        $admin = User::updateOrCreate(
            ['email' => 'admin@email.com'],
            [
                'name' => 'Admin Geral',
                'password' => Hash::make('password'),
                'cpf' => '44444444444',
                'role_id' => $roleAdmin->id,
            ]
        );
        $admin->syncRoles([$roleAdmin]);

        $this->command->info('Usuarios de exemplo criados ou atualizados!');
    }

    private function role(string $name): Role
    {
        return Role::firstOrCreate([
            'name' => $name,
            'guard_name' => 'api',
        ]);
    }
}
