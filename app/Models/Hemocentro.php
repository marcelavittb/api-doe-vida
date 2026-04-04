<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hemocentro extends Model
{ 
    protected $table = 'hemocentro';

    use SoftDeletes;
    
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = 'atualizado_em';
    const DELETED_AT = 'deletado_em';

      protected $fillable = [
        'nome', 'telefone','email','bairro', 'uf', 'endereco', 
        'cidade', 'numero', 'complemento', 'razao_social', 'cnpj', 
        'status_agendamento', 'status', 'criado_por'
     ];  //são os valores que o usuario pode preeencher 

}
