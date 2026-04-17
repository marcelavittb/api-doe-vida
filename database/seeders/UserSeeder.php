<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Hash\Bcrypt;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name'=>'Doador Apto',
            'email'=>'doadorapto@gmail.com',
            'password'=>bcrypt('123456'),
            'role_id'=>1,
            'status'=>1,
            'data_nasc'=>'1990-01-01',
            'cpf'=>'12345678900',
            'tempo_restricao'=>null
        ]);

        User::create([
            'name'=>'Doador Restrito',
            'email'=>'doadorrestrito@gmail.com',
            'password'=>bcrypt('123456'),
            'role_id'=>1,
            'status'=>1,
            'data_nasc'=>'2000-11-09',
            'cpf'=>'12345678901',
            'tempo_restricao'=>now()->addDays(90)
        ]);

        User::create([
            'name'=>'Doador menor',
            'email'=>'doadormenor@gmail.com',
            'password'=>bcrypt('123456'),
            'sexo'=>'M',
            'role_id'=>1,
            'status'=>1,
            'data_nasc'=>'2015-05-15',
            'cpf'=>'12345678902',
            'tempo_restricao'=>null,
            'responsavel_nome'=>'Responsável do Doador Menor',
            'responsavel_cpf'=>'12345678903',
            'responsavel_telefone'=>'(11) 98765-4321'
        ]);
    }
}
