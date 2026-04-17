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
        'data_hora_doacao',
        'status_agendamento'
    ];
    
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';
    const DELETED_AT = 'deletado_em';


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hemocentro()
    {
        return $this->belongsTo(Hemocentro::class, 'hemocentro_id ');
    }
    public function coletador()
    {
        return $this->belongsTo(User::class, 'coletador_id');
    }
}
