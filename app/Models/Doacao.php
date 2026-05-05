<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doacao extends Model
{
    use HasFactory;

    protected $table = 'doacao';

    protected $fillable = [
        'agendamento_id',
        'triagem_id',
        'user_id',
        'hemocentro_id',
        'funcionario_id',
        'retirado_por',
        'data_hora_doacao',
        'data_validade_sangue',
        'retirada_em',
        'tipo_sangue',
        'quantidade',
        'quantidade_retirada',
        'atualizado_em',
    ];

    public function agendamento()
    {
        return $this->belongsTo(Agendamento::class, 'agendamento_id');
    }

    public function triagem()
    {
        return $this->belongsTo(Triagem::class, 'triagem_id');
    }

    public function doador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function funcionario()
    {
        return $this->belongsTo(User::class, 'funcionario_id');
    }

    public function hemocentro()
    {
        return $this->belongsTo(Hemocentro::class, 'hemocentro_id');
    }

    public function responsavelRetirada()
    {
        return $this->belongsTo(User::class, 'retirado_por');
    }
}
