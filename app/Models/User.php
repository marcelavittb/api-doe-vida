<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasApiTokens, Notifiable, HasRoles, SoftDeletes;

    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';
    const DELETED_AT = 'deletado_em';

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
        'complemento',
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
        'lgpd_aceite',
        'lgpd_aceite_em',
        'lgpd_ip',
        'apto_pelo_autoexame',
        'autoexame_validade',
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
            'status'          => 'boolean',
            'tempo_restricao' => 'date',
            'lgpd_aceite'     => 'boolean',
            'lgpd_aceite_em'  => 'datetime',
            'apto_pelo_autoexame' => 'boolean',
            'autoexame_validade'  => 'datetime',
        ];
    }

    public function triagens()
    {
        return $this->hasMany(Triagem::class, 'user_id');
    }

    public function hemocentro()
    {
        return $this->belongsTo(Hemocentro::class, 'hemocentro_id');
    }

    public function doacoes()
    {
        return $this->hasMany(\App\Models\Doacao::class, 'user_id');
    }

    public function preTriagemRespostas()
    {
        return $this->hasMany(PreTriagemResposta::class, 'user_id');
    }

    public function alertasMedicos()
    {
        return $this->hasMany(AlertaMedico::class, 'user_id');
    }

    public function tipoSangueHistorico()
    {
        return $this->hasMany(UserTipoSangueHistorico::class, 'user_id');
    }
}
