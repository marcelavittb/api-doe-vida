<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;


class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Role::create(['id' => 1, 'nome' => 'Doador']);
        \App\Models\Role::create(['id' => 2, 'nome' => 'Funcionario']);
        \App\Models\Role::create(['id' => 3, 'nome' => 'Diretor']);
    }
        
    
}
