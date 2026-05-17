<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estoque extends Model
{
    use HasFactory;

    protected $table = 'estoque';

    const CREATED_AT = null;
    const UPDATED_AT = 'atualizado_em';

    protected $fillable = [
        'hemocentro_id',
        'tipo_sangue',
        'quantidade',
        'quantidade_minima',
    ];

    protected $casts = [
        'quantidade' => 'float',
        'quantidade_minima' => 'float',
    ];

    public function hemocentro()
    {
        return $this->belongsTo(Hemocentro::class, 'hemocentro_id');
    }
}
