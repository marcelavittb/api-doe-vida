<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'doador']);
        Role::firstOrCreate(['name' => 'funcionario']);
        Role::firstOrCreate(['name' => 'diretor']);
        Role::firstOrCreate(['name' => 'admin']);
    }
}