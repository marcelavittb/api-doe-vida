<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AgendamentoController extends Controller
{
    /**
     * Lista agendamentos.
     * Doador: vê seus agendamentos ativos e concluídos.
     * Funcionário: vê agendamentos do seu hemocentro.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $query = Agendamento::with([
            'hemocentro:id,nome,cidade,uf',
            'doador:id,name,telefone,tipo_sang,tempo_restricao,sexo',
            'triagem:id,agendamento_id,apto,data_triagem',
            'doacao:id,agendamento_id,tipo_sangue,quantidade,data_hora_doacao',
        ]);

        // Filtro por papel
        if ($user->role_id == 1) { // Doador
            $query->where('user_id', $user->id)
                  ->whereIn('status_agendamento', ['AGE', 'CON', 'FIN']);
        } elseif ($user->hemocentro_id) { // Funcionário vinculado
            // Funcionários devem ver cancelados para poderem reabrir se necessário
            $query->where('hemocentro_id', $user->hemocentro_id);
        } elseif ($request->filled('hemocentro_id')) { // Admin filtrando
            $query->where('hemocentro_id', $request->hemocentro_id);
        }

        // Filtros dinâmicos via query string
        if ($request->filled('status')) {
            $query->where('status_agendamento', $request->status);
        }

        if ($request->filled('data')) {
            $query->whereDate('data_hora_doacao', $request->data);
        }

        $agendamentos = $query->orderBy('data_hora_doacao', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'sucesso',
            'data'   => $agendamentos->items(),
            'meta' => [
                'current_page' => $agendamentos->currentPage(),
                'last_page' => $agendamentos->lastPage(),
                'per_page' => $agendamentos->perPage(),
                'total' => $agendamentos->total(),
            ],
        ]);
    }

    /**
     * Histórico completo do doador.
     */
    public function historico()
    {
        $user = Auth::user();
        $perPage = max(1, min((int) request()->input('per_page', 15), 100));
        $agendamentos = Agendamento::with([
            'hemocentro:id,nome,cidade,uf',
            'triagem:id,agendamento_id,apto,data_triagem',
            'doacao:id,agendamento_id,tipo_sangue,quantidade,data_hora_doacao',
        ])
            ->where('user_id', $user->id)
            ->withTrashed() // Inclui deletados se houver
            ->orderBy('data_hora_doacao', 'desc')
            ->paginate($perPage);

        return response()->json([
            'status' => 'sucesso',
            'data'   => $agendamentos->items(),
            'meta' => [
                'current_page' => $agendamentos->currentPage(),
                'last_page' => $agendamentos->lastPage(),
                'per_page' => $agendamentos->perPage(),
                'total' => $agendamentos->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'hemocentro_id' => [
                'required',
                'exists:hemocentros,id',
                function ($attribute, $value, $fail) {
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

        // Validação de Elegibilidade (Autoexame)
        $elegibilidadeValida = $user->autoexame_validade && $user->autoexame_validade->isFuture();
        if (!$user->apto_pelo_autoexame || !$elegibilidadeValida) {
            return response()->json([
                'status' => 'erro',
                'message' => 'Você precisa realizar o teste de elegibilidade e estar apto antes de agendar.',
                'code' => 'REQUIRES_ELIGIBILITY'
            ], 403);
        }

        if ($user->tempo_restricao && Carbon::parse($user->tempo_restricao)->isFuture()) {
            return response()->json([
                'status'   => 'erro',
                'mensagem' => 'Você ainda não está apto a doar sangue, aguarde o período de restrição terminar.',
                'apto_em'  => Carbon::parse($user->tempo_restricao)->format('d/m/Y')
            ], 403);
        }

        Agendamento::where('user_id', $user->id)
            ->where('status_agendamento', 'AGE')
            ->update(['status_agendamento' => 'EXC']);

        $idade = Carbon::parse($user->data_nasc)->age;
        $precisaAutorizacao = ($idade >= 16 && $idade < 18);

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

    public function show($id)
    {
        $user = Auth::user();
        $agendamento = Agendamento::with(['hemocentro', 'doador', 'triagem', 'doacao'])->find($id);

        if (!$agendamento) {
            return response()->json(['message' => 'Agendamento não encontrado.'], 404);
        }

        if ($user->role_id == 1 && $agendamento->user_id != $user->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return response()->json($agendamento);
    }

    /**
     * Confirmar agendamento (Doador ou Funcionário).
     */
    public function confirmar($id)
    {
        $user = Auth::user();
        $agendamento = Agendamento::findOrFail($id);

        // Se for doador, só pode confirmar o dele
        if ($user->role_id == 1 && $agendamento->user_id != $user->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $agendamento->update([
            'status_agendamento' => 'CON',
            'coletador_id'       => $user->role_id != 1 ? $user->id : $agendamento->coletador_id
        ]);

        return response()->json([
            'message' => 'Agendamento confirmado com sucesso.',
            'data'    => $agendamento
        ]);
    }

    /**
     * Cancelar agendamento (Doador ou Funcionário).
     */
    public function cancelar($id)
    {
        $user = Auth::user();
        $agendamento = Agendamento::findOrFail($id);

        if ($user->role_id == 1 && $agendamento->user_id != $user->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        $agendamento->update(['status_agendamento' => 'CAN']);

        return response()->json([
            'message' => 'Agendamento cancelado com sucesso.',
            'data'    => $agendamento
        ]);
    }

    /**
     * Reabrir agendamento (Doador ou Funcionário).
     */
    public function reabrir($id)
    {
        $user = Auth::user();
        $agendamento = Agendamento::findOrFail($id);

        if ($user->role_id == 1 && $agendamento->user_id != $user->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        // Regra de Negócio: Não reabrir se a data já passou
        if (Carbon::parse($agendamento->data_hora_doacao)->isPast()) {
            return response()->json([
                'status' => 'erro',
                'message' => 'Não é possível reabrir um agendamento com data passada.'
            ], 400);
        }

        $agendamento->update(['status_agendamento' => 'AGE']);

        return response()->json([
            'message' => 'Agendamento reaberto com sucesso.',
            'data'    => $agendamento
        ]);
    }

    public function destroy($id)
    {
        return $this->cancelar($id);
    }
}
