<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TriagemPergunta extends Model
{
    use SoftDeletes;

    protected $table = 'triagem_perguntas';

    protected $fillable = [
        'pergunta',
        'bloco',
        'obrigatoria',
        'status',
    ];

    protected $casts = [
        'obrigatoria' => 'boolean',
        'status' => 'boolean',
        'bloco' => 'integer',
    ];

    public function opcoes()
    {
        return $this->hasMany(TriagemOpcao::class, 'pergunta_id');
    }

    public function respostas()
    {
        return $this->hasMany(TriagemResposta::class, 'pergunta_id');
    }

    public function preTriagemRespostas()
    {
        return $this->hasMany(PreTriagemResposta::class, 'pergunta_id');
    }

    public function scopeAtivas($query)
    {
        return $query->whereRaw($query->getModel()->qualifyColumn('status') . ' = true');
    }

    public function scopeDoBloco($query, int $bloco)
    {
        return $query->where('bloco', $bloco);
    }

    public function scopePreTriagem($query)
    {
        return $query->where('bloco', 0);
    }

    public function scopeTriagemClinica($query)
    {
        return $query->whereIn('bloco', [1, 3, 4]);
    }
}
