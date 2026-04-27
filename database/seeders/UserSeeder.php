<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Criando um Doador para testes
        User::create([
            'name' => 'Dr. Anderson Funcionário',
            'email' => 'funcionario2@email.com',
            'password' => Hash::make('password'),
            'tipo_sang'=> 'B-',
            'telefone'=>'(11) 98765-4321',
            'data_nasc'=> '1999-06-15',
            'role_id'=>1,
            'hemocentro_id'=> 1,
            // Adicione aqui outros campos que você tenha na sua migration de users
        ]);

        // Criando um Funcionário para testes
        User::create([
            'name' => 'Dr. Anderson Funcionário',
            'email' => 'funcionario@email.com',
            'password' => Hash::make('password'),
            'tipo_sang'=> 'A+',
            'telefone'=>'(11) 98765-4321',
            'data_nasc'=> '1999-05-15',
            'role_id'=>1,
            'hemocentro_id'=> 1,

        ]);
        
        $this->command->info('Usuários (Doador e Funcionário) criados com sucesso!');
    }
}