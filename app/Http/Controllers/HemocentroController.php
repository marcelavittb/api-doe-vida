<?php

namespace App\Http\Controllers;

use App\Models\Hemocentro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HemocentroController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome'               => 'required|string|max:255',
            'telefone'           => ['required', 'string', 'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/'],
            'email'              => 'required|email|max:255|unique:hemocentros,email',
            'bairro'             => 'nullable|string|max:255',
            'uf'                 => 'required|string|max:2',
            'endereco'           => 'required|string|max:255',
            'cidade'             => 'required|string|max:255',
            'numero'             => 'required|integer',
            'complemento'        => 'nullable|string|max:255',
            'razao_social'       => 'nullable|string|max:255',
            'cnpj'               => 'nullable|string|max:20|unique:hemocentros,cnpj',
            'status_agendamento' => 'nullable|in:ativo,inativo',
            'status'             => 'nullable|integer|in:0,1',
            'criado_por'         => 'nullable|string|max:255',
        ]);

        $validated['bairro'] = $validated['bairro'] ?? 'Nao informado';
        $validated['razao_social'] = $validated['razao_social'] ?? $validated['nome'];
        $validated['status_agendamento'] = $validated['status_agendamento'] ?? 'ativo';
        $validated['status'] = $validated['status'] ?? 1;
        $validated['criado_por'] = $request->input('criado_por', $request->user()?->name ?? 'sistema');

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

    public function update(Request $request, $id)
    {
        $hemocentro = Hemocentro::findOrFail($id);

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

        if (array_key_exists('status', $dados)) {
            $valorStatus = filter_var($dados['status'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            $dados['status'] = $this->booleanSqlExpression($valorStatus);
        }

        $hemocentro->update($dados);

        return response()->json(['message' => 'Hemocentro atualizado com sucesso!', 'data' => $hemocentro], 200);
    }

    public function destroy($id)
    {
        $hemocentro = Hemocentro::findOrFail($id);

        $hemocentro->status_agendamento = 'inativo';
        $hemocentro->status = $this->booleanSqlExpression(false);

        $hemocentro->save();
        $hemocentro->delete();

        return response()->json(['message' => 'Hemocentro deletado com sucesso!'], 200);
    }

    public function index()
    {
        $hemocentro = Hemocentro::all();

        return response()->json($hemocentro, 200);
    }

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
