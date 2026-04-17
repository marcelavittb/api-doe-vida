<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // Permitir que o Seeder preencha esses campos
    protected $fillable = ['id', 'nome'];

    // Se sua migration criou a tabela no singular 'role', descomente a linha abaixo:
     // protected $table = 'role';
}