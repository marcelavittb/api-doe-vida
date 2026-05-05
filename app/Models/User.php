<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasApiTokens, Notifiable, HasRoles;

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'name',
        'email',
        'password',
        'cpf',
        'telefone',
        'tipo_sang',
        'sexo',
        'data_nasc',
        'cep',
        'rua',
        'numero',
        'bairro',
        'cidade',
        'uf',
        'responsavel_nome',
        'responsavel_cpf',
        'responsavel_data_nasc',
        'responsavel_telefone',
        'hemocentro_id',
        'status',
        'criado_por',
        'tempo_restricao',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $guard_name = 'api';
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'        => 'hashed',
            'data_nasc'       => 'date',
            'tempo_restricao' => 'date',
        ];
    }

    public function triagens()
    {
        return $this->hasMany(Triagem::class, 'user_id');
    }
}
