<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agendamento extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'agendamento';

    protected $fillable = [
        'user_id',
        'hemocentro_id',
        'coletador_id',
        'data_hora_doacao',
        'status_agendamento'
    ];
    
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';
    const DELETED_AT = 'deletado_em';

    // Relacionamento com Triagem (Um agendamento tem uma triagem)
    public function triagem()
    {
        return $this->hasOne(Triagem::class, 'agendamento_id');
    }

    // Relacionamento com Doacao (Um agendamento tem uma doação)
    public function doacao()
    {
        return $this->hasOne(Doacao::class, 'agendamento_id');
    }

    // Renomeado para 'doador' para bater com o Controller
    public function doador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hemocentro()
    {
        return $this->belongsTo(Hemocentro::class, 'hemocentro_id');
    }

    public function coletador()
    {
        return $this->belongsTo(User::class, 'coletador_id');
    }
}
