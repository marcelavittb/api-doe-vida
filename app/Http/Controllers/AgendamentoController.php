<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AgendamentoController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
        'hemocentro_id' => [
            'required',
            'exists:hemocentros,id',
            function ($attribute, $value, $fail) {
                // Checa se o hemocentro está ativo no banco
                $ativo = \App\Models\Hemocentro::where('id', $value)
                    ->where('status_agendamento', 'ativo')
                    ->exists();

                if (!$ativo) {
                    $fail('Este hemocentro não está aceitando agendamentos no momento.');
                }
            },
        ],
        'data_hora_doacao' => 'required|date|after:now',
        ]);
    

         //Validação de Restrição Biológica (90/120 dias)
        if ($user->tempo_restricao && Carbon::parse($user->tempo_restricao)->isFuture()) {
            return response()->json([
                'status'   => 'erro',
                'mensagem' => 'Você ainda não está apto a doar sangue, aguarde o período de restrição terminar.',
                'apto_em'  => Carbon::parse($user->tempo_restricao)->format('d/m/Y')
            ], 403);
        }

        // 2. Lógica de Reagendamento: Inativa agendamentos futuros antigos
        Agendamento::where('user_id', $user->id)
            ->where('status_agendamento', 'AGE')
            ->update(['status_agendamento' => 'EXC']);

        // 3. Cálculo de Idade para Alerta de Responsável
        $idade = Carbon::parse($user->data_nasc)->age;
        $precisaAutorizacao = ($idade >= 16 && $idade < 18);

        // 4. Criação do Novo Agendamento
        $agendamento = Agendamento::create([
            'user_id'            => $user->id,
            'hemocentro_id'      => $request->hemocentro_id,
            'data_hora_doacao'   => $request->data_hora_doacao,
            'status_agendamento' => 'AGE'
        ]);

        return response()->json([
            'message' => 'Agendamento criado com sucesso',
            'alerta'  => $precisaAutorizacao ? 'É obrigatório autorização ou presença dos responsáveis no dia da doação' : null,
            'data'    => $agendamento
        ], 201);
    }
    

    public function index()
{
    $user = Auth::user();

    // 1. Buscamos os agendamentos garantindo que o user_id seja do logado
    // 2. Usamos o with('hemocentro') - CERTIFIQUE-SE que no Model está no SINGULAR
    $agendamentos = \App\Models\Agendamento::with('hemocentro')
        ->where('user_id', $user->id)
        ->whereIn('status_agendamento', ['AGE', 'CON']) // Traz apenas Ativos ou Concluídos
        ->orderBy('data_hora_doacao', 'desc')
        ->get();

    // Se a coleção estiver vazia, avisamos
    if ($agendamentos->isEmpty()) {
        return response()->json([
            'message' => 'Você ainda não possui agendamentos marcados.',
            'data' => []
        ], 200);
    }

    // Se chegou aqui, ele TEM que listar no campo 'data'
    return response()->json([
        'message' => 'Agendamentos recuperados com sucesso.',
        'count' => $agendamentos->count(),
        'data' => $agendamentos
    ], 200);
}

    public function destroy($id)
    {
        $user = Auth::user();

        $agendamento = Agendamento::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$agendamento) {
            return response()->json(['message' => 'Agendamento não encontrado ou não pertence a você.'], 404);
        }

        $agendamento->update(['status_agendamento' => 'CAN']);

        return response()->json(['message' => 'Agendamento cancelado com sucesso.'], 200);
    }
}