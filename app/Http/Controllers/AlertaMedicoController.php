<?php

namespace App\Http\Controllers;

use App\Models\AlertaMedico;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AlertaMedicoController extends Controller
{
    // GET /api/alertas-medicos
    // Doador: ve apenas seus proprios alertas (somente notificacao_doador)
    // Funcionario/Diretor: ve alertas do hemocentro com todos os campos
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $query = AlertaMedico::with(['doador', 'hemocentro', 'criadoPor'])
            ->orderBy('criado_em', 'desc');

        if ($user->role_id == 1) {
            $query->where('user_id', $user->id);
            $alertas = $query->paginate($perPage);
            $items = collect($alertas->items())->map(fn($a) => [
                'id' => $a->id,
                'tipo_alerta' => $a->tipo_alerta,
                'status' => $a->status,
                'notificacao_doador' => $a->notificacao_doador,
                'criado_em' => $a->criado_em,
            ]);

            return response()->json([
                'status' => 'sucesso',
                'data' => $items,
                'meta' => [
                    'current_page' => $alertas->currentPage(),
                    'last_page' => $alertas->lastPage(),
                    'per_page' => $alertas->perPage(),
                    'total' => $alertas->total(),
                ],
            ]);
        }

        if ($user->hemocentro_id) {
            $query->where('hemocentro_id', $user->hemocentro_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return response()->json([
            'status' => 'sucesso',
            'data' => ($alertas = $query->paginate($perPage))->items(),
            'meta' => [
                'current_page' => $alertas->currentPage(),
                'last_page' => $alertas->lastPage(),
                'per_page' => $alertas->perPage(),
                'total' => $alertas->total(),
            ],
        ]);
    }

    // GET /api/alertas-medicos/{id}
    public function show($id)
    {
        $user = Auth::user();
        $alerta = AlertaMedico::with(['doador', 'hemocentro', 'criadoPor'])->findOrFail($id);

        if ($user->role_id == 1) {
            if ($alerta->user_id !== $user->id) {
                return response()->json(['message' => 'Acesso negado.'], 403);
            }

            return response()->json([
                'id' => $alerta->id,
                'tipo_alerta' => $alerta->tipo_alerta,
                'status' => $alerta->status,
                'notificacao_doador' => $alerta->notificacao_doador,
                'criado_em' => $alerta->criado_em,
            ]);
        }

        if ($user->hemocentro_id && $alerta->hemocentro_id !== $user->hemocentro_id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return response()->json($alerta);
    }

    // POST /api/auth/alertas-medicos
    // Apenas funcionarios podem criar alertas
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$request->has('hemocentro_id') && $user->hemocentro_id) {
            $request->merge(['hemocentro_id' => $user->hemocentro_id]);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'hemocentro_id' => 'required|exists:hemocentros,id',
            'tipo_alerta' => 'required|in:resultado_sorologico,convocacao_retorno,outro',
            'notificacao_doador' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $doador = User::find($request->user_id);
        if (!$doador || $doador->role_id != 1) {
            return response()->json([
                'message' => 'O usuario informado nao e um doador valido.',
            ], 422);
        }

        $alerta = AlertaMedico::create([
            'user_id' => $request->user_id,
            'hemocentro_id' => $request->hemocentro_id,
            'criado_por' => $user->id,
            'tipo_alerta' => $request->tipo_alerta,
            'status' => 'pendente',
            'notificacao_doador' => $request->notificacao_doador,
        ]);

        Log::info('ALERTA MEDICO CRIADO', [
            'alerta_id' => $alerta->id,
            'funcionario_id' => $user->id,
            'doador_id' => $request->user_id,
            'hemocentro_id' => $request->hemocentro_id,
            'tipo' => $request->tipo_alerta,
            'timestamp' => now(),
        ]);

        return response()->json([
            'message' => 'Alerta medico registrado com sucesso.',
            'data' => $alerta->load(['doador', 'criadoPor']),
        ], 201);
    }

    // PUT /api/auth/alertas-medicos/{id}
    // Funcionario atualiza o status do alerta (compareceu, encerrado)
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $alerta = AlertaMedico::findOrFail($id);

        if ($user->hemocentro_id && $alerta->hemocentro_id !== $user->hemocentro_id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pendente,compareceu,encerrado',
            'notificacao_doador' => 'sometimes|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $alerta->update($request->only(['status', 'notificacao_doador']));

        Log::info('ALERTA MEDICO ATUALIZADO', [
            'alerta_id' => $alerta->id,
            'funcionario_id' => $user->id,
            'novo_status' => $request->status,
            'timestamp' => now(),
        ]);

        return response()->json([
            'message' => 'Alerta atualizado com sucesso.',
            'data' => $alerta->fresh(['doador', 'criadoPor']),
        ]);
    }

    // DELETE /api/auth/alertas-medicos/{id}
    // Encerra e aplica soft delete - nao apaga fisicamente
    public function destroy($id)
    {
        $user = Auth::user();
        $alerta = AlertaMedico::findOrFail($id);

        if ($user->hemocentro_id && $alerta->hemocentro_id !== $user->hemocentro_id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $alerta->status = 'encerrado';
        $alerta->save();
        $alerta->delete();

        return response()->json(['message' => 'Alerta encerrado e removido do sistema.']);
    }
}
