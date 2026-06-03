<?php

namespace App\Http\Controllers;

use App\Models\Doacao;
use App\Models\User;
use App\Models\Triagem;
use App\Models\Agendamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DoacaoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user() ?: Auth::user();
        $query = Doacao::with(['doador', 'funcionario', 'hemocentro', 'agendamento', 'triagem']);

        if ($user->role_id == 1) {
            $query->where('user_id', $user->id);
        } elseif ($user->hemocentro_id) {
            $query->where('hemocentro_id', $user->hemocentro_id);
        }

        $doacoes = $query->orderBy('data_hora_doacao', 'desc')->get();

        return response()->json([
            'status' => 'sucesso',
            'data'   => $doacoes
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user() ?: Auth::user();

        if (!$request->has('hemocentro_id') && $user->hemocentro_id) {
            $request->merge(['hemocentro_id' => $user->hemocentro_id]);
        }

        if ($user->hemocentro_id && (int) $request->hemocentro_id !== (int) $user->hemocentro_id) {
            return response()->json(['message' => 'Acesso negado para este hemocentro.'], 403);
        }

        $rules = [
            'agendamento_id'       => 'required|exists:agendamento,id',
            'triagem_id'           => 'required|exists:triagens,id',
            'user_id'              => 'required|exists:users,id',
            'hemocentro_id'        => 'required|exists:hemocentros,id',
            'data_hora_doacao'     => 'required|date',
            'tipo_sangue'          => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'quantidade'           => 'required|numeric',
            'data_validade_sangue' => 'nullable|date',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // VALIDAÇÃO DE NEGÓCIO: Só pode doar se a triagem for deste agendamento e estiver APTO
        $triagem = Triagem::where('id', $request->triagem_id)
                          ->where('agendamento_id', $request->agendamento_id)
                          ->where('user_id', $request->user_id)
                          ->first();

        if (!$triagem) {
            return response()->json(['message' => 'Triagem não vinculada a este agendamento ou usuário.'], 400);
        }

        if (!$triagem->apto) {
            return response()->json(['message' => 'Não é possível registrar doação para um doador inapto na triagem.'], 400);
        }

        // EVITAR DUPLICIDADE: Verifica se já existe doação para este agendamento
        $existente = Doacao::where('agendamento_id', $request->agendamento_id)->first();
        if ($existente) {
            return response()->json(['message' => 'Já existe uma doação registrada para este agendamento.'], 409);
        }

        $doacao = Doacao::create([
            'agendamento_id'       => $request->agendamento_id,
            'triagem_id'           => $request->triagem_id,
            'user_id'              => $request->user_id,
            'funcionario_id'       => Auth::id(),
            'hemocentro_id'        => $request->hemocentro_id,
            'data_hora_doacao'     => $request->data_hora_doacao,
            'tipo_sangue'          => $request->tipo_sangue,
            'quantidade'           => $request->quantidade,
            'data_validade_sangue' => $request->data_validade_sangue,
        ]);

        // Atualiza o status do agendamento para FIN (Finalizado)
        Agendamento::where('id', $request->agendamento_id)->update([
            'status_agendamento' => 'FIN'
        ]);

        // Atualiza a restrição biológica do doador
        $doador = User::find($request->user_id);
        if ($doador) {
            $dias = ($doador->sexo === 'M') ? 90 : 120;
            $doador->update([
                'tempo_restricao' => \Carbon\Carbon::parse($request->data_hora_doacao)->addDays($dias)
            ]);
        }

        return response()->json([
            'message' => 'Doação registrada com sucesso! Atualize o estoque para concluir o lançamento da bolsa.',
            'data'    => $doacao
        ], 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        $doacao = Doacao::with(['doador', 'funcionario', 'hemocentro', 'agendamento', 'triagem'])->find($id);

        if (!$doacao) {
            return response()->json(['message' => 'Doação não encontrada.'], 404);
        }

        if ($user->role_id == 1 && $doacao->user_id != $user->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        if ($user->hemocentro_id && (int) $doacao->hemocentro_id !== (int) $user->hemocentro_id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return response()->json($doacao);
    }
}
