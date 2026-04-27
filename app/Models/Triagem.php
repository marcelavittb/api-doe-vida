<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Triagem extends Model
{
    use HasFactory;

    protected $table = 'triagens'; // Definindo o nome da tabela manualmente

    protected $fillable = [
        'user_id', 
        'funcionario_id', 
        'hemocentro_id', 
        'data_triagem', 
        'status_triagem', 
        'apto', 
        'motivo_inaptidao', 
        'observacoes'
    ];

    // Casts ajudam o Laravel a tratar os tipos de dados automaticamente
    protected $casts = [
        'data_triagem' => 'datetime',
        'apto' => 'boolean',
    ];

    // Relacionamentos
    public function doador() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function funcionario() {
        return $this->belongsTo(User::class, 'funcionario_id');
    }

    public function hemocentro() {
        return $this->belongsTo(Hemocentro::class);
    }

    public function scopeAtivas($query) {

            return $query->where('status_triagem', '!=', 'E');
    }
}