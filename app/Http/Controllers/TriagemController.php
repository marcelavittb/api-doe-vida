<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Triagem;
use App\Models\TriagemAptidao;
use App\Models\TriagemPergunta;
use App\Models\TriagemResposta;
use App\Models\TriagemSinaisVitais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class TriagemController extends Controller
{
    // GET /api/triagens
    // Doador: ve suas proprias triagens
    // Funcionario/Diretor: ve triagens do hemocentro
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = max(1, min((int) $request->input('per_page', 15), 100));
        $query = Triagem::where('status_triagem', '!=', 'E')
            ->with([
                'doador',
                'funcionario',
                'hemocentro',
                'agendamento',
                'sinaisVitais',
                'aptidao',
                'respostas.pergunta',
                'respostas.opcao',
            ]);

        if ($user->role_id == 1) {
            $query->where('user_id', $user->id);
        } elseif ($user->hemocentro_id) {
            $query->where('hemocentro_id', $user->hemocentro_id);
        }

        if ($request->filled('status')) {
            $query->where('status_triagem', $request->status);
        }
        if ($request->filled('apto')) {
            $query->whereRaw('apto = ' . $this->booleanSqlLiteral($request->boolean('apto')));
        }
        if ($request->filled('data')) {
            $query->whereDate('data_triagem', $request->data);
        }

        $triagens = $query->orderBy('data_triagem', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $triagens->items(),
            'meta' => [
                'current_page' => $triagens->currentPage(),
                'last_page' => $triagens->lastPage(),
                'per_page' => $triagens->perPage(),
                'total' => $triagens->total(),
            ],
        ]);
    }

    // GET /api/triagens/perguntas
    // Retorna perguntas para o frontend montar os formularios
    // Deve ser declarada ANTES de /triagens/{id} no routes/api.php
    public function perguntas(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'bloco' => 'nullable|integer|in:0,1,3,4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'erro',
                'message' => 'O parametro bloco deve ser um dos valores: 0, 1, 3 ou 4.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->garantirEstruturaPerguntasTriagem();
            $this->garantirPerguntasPadrao();

            $bloco = $request->query('bloco');

            $query = TriagemPergunta::query()
                ->ativas()
                ->with('opcoes')
                ->orderBy('bloco')
                ->orderBy('id');

            if (!is_null($bloco)) {
                $query->where('bloco', (int) $bloco);
            }

            return response()->json([
                'status' => 'sucesso',
                'data' => $query->get(),
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $e) {
            Log::error('Erro ao buscar perguntas de triagem', [
                'endpoint' => '/api/triagens/perguntas',
                'bloco' => $request->query('bloco'),
                'exception' => class_basename($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'sql_state' => $e instanceof \PDOException ? $e->getCode() : null,
            ]);

            return response()->json([
                'status' => 'erro',
                'exception' => class_basename($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    // POST /api/auth/triagens
    // Registra triagem clinica completa:
    // sinais vitais + respostas + calculo de aptidao automatico
    public function store(Request $request)
    {
        try {
            $this->garantirEstruturaCadastroTriagem();

            $user = auth()->user();

            if (!$request->has('hemocentro_id') && $user->hemocentro_id) {
                $request->merge(['hemocentro_id' => $user->hemocentro_id]);
            }

            $validator = Validator::make($request->all(), [
                'agendamento_id' => 'required|exists:agendamento,id',
                'user_id' => 'required|exists:users,id',
                'hemocentro_id' => 'required|exists:hemocentros,id',
                'data_triagem' => 'required|date',
                'observacoes' => 'nullable|string',

                'sinais_vitais' => 'nullable|array',
                'sinais_vitais.peso' => 'nullable|numeric|min:40|max:300',
                'sinais_vitais.pressao_sistolica' => 'nullable|integer|min:60|max:250',
                'sinais_vitais.pressao_diastolica' => 'nullable|integer|min:40|max:150',
                'sinais_vitais.temperatura' => 'nullable|numeric|min:34|max:42',
                'sinais_vitais.frequencia_cardiaca' => 'nullable|integer|min:40|max:200',
                'sinais_vitais.hemoglobina' => 'nullable|numeric|min:5|max:25',
                'sinais_vitais.hematocrito' => 'nullable|numeric|min:10|max:70',

                'respostas' => 'nullable|array',
                'respostas.*.pergunta_id' => 'required_with:respostas|exists:triagem_perguntas,id',
                'respostas.*.opcao_id' => 'required_with:respostas|exists:triagem_opcoes,id',

                'aptidao' => 'required|array',
                'aptidao.resultado' => 'required|in:apto,inapto_temporario,inapto_definitivo',
                'aptidao.categoria_inaptidao' => 'required_unless:aptidao.resultado,apto|nullable|in:sinais_vitais_fora_do_padrao,intervalo_minimo_nao_cumprido,medicamento_incompativel,cirurgia_recente,viagem_area_de_risco,comportamento_de_risco,condicao_clinica_na_triagem,resultado_sorologico_alterado,outro',
                'aptidao.observacoes_internas' => 'nullable|string',
                'aptidao.notificacao_doador' => 'nullable|string',
                'aptidao.valido_ate' => 'required_if:aptidao.resultado,inapto_temporario|nullable|date|after:today',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $existente = Triagem::where('agendamento_id', $request->agendamento_id)
                ->where('status_triagem', '!=', 'E')
                ->first();

            if ($existente) {
                return response()->json([
                    'message' => 'Triagem recuperada com sucesso (ja existente).',
                    'apto' => (bool) $existente->apto,
                    'data' => $existente->load(['sinaisVitais', 'aptidao', 'respostas.opcao']),
                ], 200);
            }

            $resultadoAptidao = $request->input('aptidao.resultado');
            $apto = $resultadoAptidao === 'apto';

            $notificacaoDoador = $request->input('aptidao.notificacao_doador');
            if (!$notificacaoDoador && !$apto) {
                $notificacaoDoador = $this->gerarNotificacaoPadrao($resultadoAptidao);
            }

            $categoriaLabel = null;
            if (!$apto && $request->filled('aptidao.categoria_inaptidao')) {
                $categoriaLabel = $this->categoriaLabel($request->input('aptidao.categoria_inaptidao'));
            }

            DB::beginTransaction();

            $triagem = Triagem::create([
                'agendamento_id' => $request->agendamento_id,
                'user_id' => $request->user_id,
                'funcionario_id' => auth()->id(),
                'hemocentro_id' => $request->hemocentro_id,
                'data_triagem' => $request->data_triagem,
                'status_triagem' => 'C',
                'apto' => $this->booleanSqlExpression($apto),
                'motivo_inaptidao' => $apto ? null : $categoriaLabel,
                'observacoes' => $request->observacoes,
            ]);

            if ($request->filled('sinais_vitais')) {
                TriagemSinaisVitais::create(array_merge(
                    ['triagem_id' => $triagem->id],
                    $request->sinais_vitais
                ));
            }

            if ($request->filled('respostas')) {
                foreach ($request->respostas as $resposta) {
                    TriagemResposta::create([
                        'triagem_id' => $triagem->id,
                        'pergunta_id' => $resposta['pergunta_id'],
                        'opcao_id' => $resposta['opcao_id'],
                    ]);
                }
            }

            TriagemAptidao::create([
                'triagem_id' => $triagem->id,
                'resultado' => $resultadoAptidao,
                'categoria_inaptidao' => $request->input('aptidao.categoria_inaptidao'),
                'observacoes_internas' => $request->input('aptidao.observacoes_internas'),
                'notificacao_doador' => $notificacaoDoador,
                'valido_ate' => $request->input('aptidao.valido_ate'),
            ]);

            Log::info('TRIAGEM REGISTRADA', [
                'triagem_id' => $triagem->id,
                'funcionario_id' => auth()->id(),
                'doador_id' => $request->user_id,
                'hemocentro_id' => $request->hemocentro_id,
                'apto' => $apto,
                'resultado' => $resultadoAptidao,
                'timestamp' => now(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('ERRO AO REGISTRAR TRIAGEM', [
                'endpoint' => '/api/auth/triagens',
                'agendamento_id' => $request->input('agendamento_id'),
                'user_id' => $request->input('user_id'),
                'hemocentro_id' => $request->input('hemocentro_id'),
                'exception' => class_basename($e),
                'erro' => $e->getMessage(),
                'sql_state' => $e instanceof \PDOException ? $e->getCode() : null,
                'timestamp' => now(),
            ]);

            return response()->json([
                'message' => 'Erro ao registrar triagem.',
                'exception' => class_basename($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }

        return response()->json([
            'message' => 'Triagem realizada com sucesso!',
            'apto' => $apto,
            'data' => $triagem->load(['sinaisVitais', 'aptidao', 'respostas.opcao']),
        ], 201);
    }

    // GET /api/triagens/{id}
    public function show($id)
    {
        $user = auth()->user();
        $triagem = Triagem::with([
            'doador',
            'funcionario',
            'hemocentro',
            'agendamento',
            'sinaisVitais',
            'aptidao',
            'respostas.pergunta',
            'respostas.opcao',
        ])->find($id);

        if (!$triagem || $triagem->status_triagem === 'E') {
            return response()->json(['message' => 'Triagem nao encontrada.'], 404);
        }

        if ($user->role_id == 1) {
            if ($triagem->user_id != $user->id) {
                return response()->json(['message' => 'Acesso negado.'], 403);
            }

            if ($triagem->aptidao) {
                $triagem->aptidao->makeHidden(['observacoes_internas']);
            }
        }

        return response()->json($triagem);
    }

    // PUT /api/auth/triagens/{id}
    // Atualizar aptidao ou observacoes de uma triagem existente
    public function update(Request $request, $id)
    {
        $triagem = Triagem::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'apto' => 'sometimes|boolean',
            'status_triagem' => 'sometimes|in:P,C,E',
            'motivo_inaptidao' => 'required_if:apto,false|nullable|string',
            'observacoes' => 'nullable|string',
            'aptidao.resultado' => 'sometimes|in:apto,inapto_temporario,inapto_definitivo',
            'aptidao.categoria_inaptidao' => 'nullable|in:sinais_vitais_fora_do_padrao,intervalo_minimo_nao_cumprido,medicamento_incompativel,cirurgia_recente,viagem_area_de_risco,comportamento_de_risco,condicao_clinica_na_triagem,resultado_sorologico_alterado,outro',
            'aptidao.observacoes_internas' => 'nullable|string',
            'aptidao.notificacao_doador' => 'nullable|string',
            'aptidao.valido_ate' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $dadosTriagem = $request->only([
                'apto',
                'motivo_inaptidao',
                'observacoes',
                'status_triagem',
            ]);

            if (array_key_exists('apto', $dadosTriagem)) {
                $dadosTriagem['apto'] = $this->booleanSqlExpression(filter_var($dadosTriagem['apto'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false);
            }

            $triagem->update($dadosTriagem);

            if ($request->filled('aptidao')) {
                TriagemAptidao::updateOrCreate(
                    ['triagem_id' => $triagem->id],
                    $request->input('aptidao')
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['message' => 'Erro ao atualizar triagem.'], 500);
        }

        return response()->json([
            'message' => 'Triagem atualizada com sucesso!',
            'data' => $triagem->load(['aptidao', 'sinaisVitais']),
        ]);
    }

    // DELETE /api/auth/triagens/{id}
    // Encerramento logico - nao apaga fisicamente
    public function destroy($id)
    {
        $triagem = Triagem::findOrFail($id);
        $triagem->status_triagem = 'E';
        $triagem->save();

        return response()->json(['message' => 'Triagem encerrada no sistema.']);
    }

    private function gerarNotificacaoPadrao(string $resultado): string
    {
        return match ($resultado) {
            'inapto_temporario' => 'Voce foi considerado temporariamente inapto para doacao nesta data. Entre em contato com o hemocentro para mais informacoes sobre quando podera retornar.',
            'inapto_definitivo' => 'Voce foi considerado inapto para doacao de sangue. Por favor, entre em contato com o hemocentro para orientacoes.',
            default => 'Sua triagem foi registrada. Em caso de duvidas, entre em contato com o hemocentro.',
        };
    }

    private function categoriaLabel(string $categoria): string
    {
        return match ($categoria) {
            'sinais_vitais_fora_do_padrao' => 'Sinais vitais fora do padrao',
            'intervalo_minimo_nao_cumprido' => 'Intervalo minimo entre doacoes nao cumprido',
            'medicamento_incompativel' => 'Uso de medicamento incompativel',
            'cirurgia_recente' => 'Procedimento cirurgico recente',
            'viagem_area_de_risco' => 'Viagem para area de risco',
            'comportamento_de_risco' => 'Comportamento de risco recente',
            'condicao_clinica_na_triagem' => 'Condicao clinica identificada na triagem',
            'resultado_sorologico_alterado' => 'Resultado sorologico alterado',
            default => 'Outro motivo',
        };
    }

    private function garantirEstruturaPerguntasTriagem(): void
    {
        if (!Schema::hasTable('triagem_perguntas')) {
            throw new \RuntimeException('Tabela triagem_perguntas nao encontrada.');
        }

        if (!Schema::hasTable('triagem_opcoes')) {
            throw new \RuntimeException('Tabela triagem_opcoes nao encontrada.');
        }

        foreach (['id', 'pergunta', 'bloco', 'obrigatoria', 'status'] as $coluna) {
            if (!Schema::hasColumn('triagem_perguntas', $coluna)) {
                throw new \RuntimeException("Coluna obrigatoria ausente em triagem_perguntas: {$coluna}.");
            }
        }
    }

    private function garantirPerguntasPadrao(): void
    {
        if (TriagemPergunta::count() > 0) {
            return;
        }

        Log::warning('Tabela triagem_perguntas vazia. Recriando perguntas padrao.', [
            'endpoint' => '/api/triagens/perguntas',
        ]);

        app(\Database\Seeders\TriagemPerguntaSeeder::class)->run();
    }

    private function garantirEstruturaCadastroTriagem(): void
    {
        foreach (['triagens', 'triagem_respostas', 'triagem_sinais_vitais', 'triagem_aptidao', 'triagem_perguntas', 'triagem_opcoes', 'agendamento'] as $tabela) {
            if (!Schema::hasTable($tabela)) {
                throw new \RuntimeException("Tabela obrigatoria ausente: {$tabela}.");
            }
        }

        foreach (['agendamento_id', 'user_id', 'funcionario_id', 'hemocentro_id', 'data_triagem', 'status_triagem', 'apto'] as $coluna) {
            if (!Schema::hasColumn('triagens', $coluna)) {
                throw new \RuntimeException("Coluna obrigatoria ausente em triagens: {$coluna}.");
            }
        }
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
