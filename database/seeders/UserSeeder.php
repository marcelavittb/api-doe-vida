<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Criando 8 Doadores (IDs 1 a 8 aproximadamente)
        $tipos = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        for ($i = 1; $i <= 8; $i++) {
            User::create([
                'name'      => "Doador Exemplo $i",
                'email'     => "doador$i@email.com",
                'password'  => Hash::make('password'),
                'cpf'       => "1234567890$i",
                'tipo_sang' => $tipos[array_rand($tipos)],
                'sexo'      => ($i % 2 == 0) ? 'M' : 'F',
                'telefone'  => "(11) 99999-000$i",
                'data_nasc' => '1990-01-01',
                'role_id'   => 1,
            ]);
        }

        // 2. Funcionário Hemocentro 1
        User::create([
            'name'          => 'Funcionário H1',
            'email'         => 'funcionario1@email.com',
            'password'      => Hash::make('password'),
            'cpf'           => '11111111111',
            'role_id'       => 2,
            'hemocentro_id' => 1,
        ]);

        // 3. Funcionário Hemocentro 2 (Diferente)
        User::create([
            'name'          => 'Funcionário H2',
            'email'         => 'funcionario2@email.com',
            'password'      => Hash::make('password'),
            'cpf'           => '22222222222',
            'role_id'       => 2,
            'hemocentro_id' => 2,
        ]);

        // 4. Admin e Diretor
        User::create([
            'name' => 'Diretor H1', 'email' => 'diretor@email.com', 'password' => Hash::make('password'), 'cpf' => '33333333333', 'role_id' => 3, 'hemocentro_id' => 1
        ]);
        User::create([
            'name' => 'Admin Geral', 'email' => 'admin@email.com', 'password' => Hash::make('password'), 'cpf' => '44444444444', 'role_id' => 4
        ]);

        $this->command->info('8 Doadores e Funcionários em Hemocentros diferentes criados!');
    }
}
