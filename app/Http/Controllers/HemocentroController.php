<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hemocentro;
use Illuminate\Support\Facades\DB;

class HemocentroController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome'               => 'required|string|max:255',
            'telefone'           => ['required', 'string', 'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/'],
            'email'              => 'required|email|max:255|unique:hemocentros,email',
            'bairro'             => 'required|string|max:255',
            'uf'                 => 'required|string|max:2',
            'endereco'           => 'required|string|max:255',
            'cidade'             => 'required|string|max:255',
            'numero'             => 'required|integer',
            'complemento'        => 'nullable|string|max:255', // Nullable pois nem todo endereço tem
            'razao_social'       => 'required|string|max:255',
            'cnpj'               => 'required|string|max:20|unique:hemocentros,cnpj', // Adicionado unique para não repetir CNPJ
            'status_agendamento' => 'required|in:ativo,inativo',
            'status'             => 'required|integer|in:0,1', // Garante que seja apenas 0 ou 1 (tinyint)
            'criado_por'         => 'nullable|string|max:255' // Pode ser preenchido pela API ou opcional
        ]);
        $validated['criado_por'] = $request->input('criado_por', 'usuario_teste_12');

        // Converte o status (boolean no PostgreSQL) para o literal correto,
        // evitando o erro "operator does not exist: boolean = integer" no pooler.
        if (array_key_exists('status', $validated)) {
            $validated['status'] = $this->booleanSqlExpression((bool) $validated['status']);
        }

        $hemocentro = Hemocentro::create($validated);

        return response()->json(['message' => 'Hemocentro criado com sucesso!', 'data' => $hemocentro], 201);
    }

    public function show($id)
    {
        $hemocentro = Hemocentro::findOrFail($id);

        return response()->json($hemocentro, 200);
    }

    //METODO  PUT  - ATUALIZAR
    public function update(Request $request, $id)
    {
        $hemocentro = Hemocentro::findOrFail($id);

        // Aceita apenas os campos que realmente existem na tabela, evitando
        // que campos extras vindos do front (ex: id, cep) causem erro.
        $dados = $request->only([
            'nome',
            'telefone',
            'email',
            'bairro',
            'uf',
            'endereco',
            'cidade',
            'numero',
            'complemento',
            'razao_social',
            'cnpj',
            'status_agendamento',
            'status',
        ]);

        // Converte o status (boolean no PostgreSQL) para o literal correto.
        // O front envia 1/0; o PostgreSQL exige true/false em coluna boolean.
        if (array_key_exists('status', $dados)) {
            $valorStatus = filter_var($dados['status'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            $dados['status'] = $this->booleanSqlExpression($valorStatus);
        }

        $hemocentro->update($dados);

        return response()->json(['message' => 'Hemocentro atualizado com sucesso!', 'data' => $hemocentro], 200);
    }

    //METODO DELETE - DELETAR
    public function destroy($id)
    {
        $hemocentro = Hemocentro::findOrFail($id);

        $hemocentro->status_agendamento = 'inativo';
        $hemocentro->status = $this->booleanSqlExpression(false);

        $hemocentro->save();
        $hemocentro->delete();
        // Como ativamos o SoftDeletes no Model, isso NÃO dá um DROP na linha.
        // Ele apenas preenche a coluna 'deletado_em' com a data/hora atual.

        return response()->json(['message' => 'Hemocentro deletado com sucesso!'], 200);
    }

    // Aproveitando, aqui está o método para listar todos, que o Front-end vai precisar:
    public function index()
    {
        $hemocentro = Hemocentro::all();
        return response()->json($hemocentro, 200);
    }

    /**
     * Retorna o valor booleano de forma compatível com o banco em uso.
     * No PostgreSQL (atrás do pooler do Supabase, com prepared statements
     * emulados), um boolean precisa virar o literal SQL true/false — caso
     * contrário o PDO envia 1/0 e o Postgres recusa com
     * "operator does not exist: boolean = integer".
     */
    private function booleanSqlExpression(bool $value): \Illuminate\Database\Query\Expression|bool
    {
        if (DB::getDriverName() === 'pgsql') {
            return DB::raw($this->booleanSqlLiteral($value));
        }

        return $value;
    }

    private function booleanSqlLiteral(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}