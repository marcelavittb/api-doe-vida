<?php

namespace App\Http\Controllers;

use App\Jobs\SendCampaignEmailJob;
use App\Models\Campanha;
use App\Models\Doacao;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class CampanhaController extends Controller
{
    // GET /api/campanhas
    public function index(Request $request)
    {
        $campanhas = Campanha::with('hemocentro')
            ->orderBy('criado_em', 'desc')
            ->get();

        return response()->json([
            'status' => 'sucesso',
            'data' => $campanhas,
        ]);
    }

    // GET /api/campanhas/{id}
    public function show($id)
    {
        $campanha = Campanha::with('hemocentro', 'criador')->findOrFail($id);

        return response()->json(['status' => 'sucesso', 'data' => $campanha]);
    }

    // POST /api/auth/campanhas
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'tipo_sangue' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'hemocentro_id' => 'nullable|exists:hemocentros,id',
            'data_publi' => 'required|date',
            'data_expiracao' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        [$dataPubli, $dataExpiracao, $dateErrors] = $this->validateAndNormalizeCampaignDates(
            $request->input('data_publi'),
            $request->input('data_expiracao')
        );

        if ($dateErrors !== []) {
            return response()->json($dateErrors, 422);
        }

        $campanha = Campanha::create([
            'titulo' => $request->titulo,
            'subtitulo' => $request->subtitulo,
            'descricao' => $request->descricao,
            'tipo_sangue' => $request->tipo_sangue,
            'hemocentro_id' => $request->hemocentro_id,
            'data_publi' => $dataPubli,
            'data_expiracao' => $dataExpiracao,
            'status' => true,
            'criado_por' => Auth::id(),
            'total_disparado' => 0,
            'total_aberto' => 0,
        ]);

        Log::info('CAMPANHA CRIADA', [
            'campanha_id' => $campanha->id,
            'titulo' => $campanha->titulo,
            'criado_por' => Auth::id(),
            'timestamp' => now(),
        ]);

        return response()->json([
            'message' => 'Campanha criada com sucesso!',
            'data' => $campanha->load('hemocentro'),
        ], 201);
    }

    // PUT /api/auth/campanhas/{id}
    public function update(Request $request, $id)
    {
        $campanha = Campanha::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'titulo' => 'sometimes|string|max:255',
            'subtitulo' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'tipo_sangue' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'data_publi' => 'sometimes|date',
            'data_expiracao' => 'nullable|date',
            'status' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $payload = $request->only([
            'titulo', 'subtitulo', 'descricao',
            'tipo_sangue', 'data_publi', 'data_expiracao', 'status',
        ]);

        if ($request->has('data_publi') || $request->has('data_expiracao')) {
            [$dataPubli, $dataExpiracao, $dateErrors] = $this->validateAndNormalizeCampaignDates(
                $request->input('data_publi', $campanha->data_publi?->format('Y-m-d H:i:s')),
                $request->input('data_expiracao')
            );

            if ($dateErrors !== []) {
                return response()->json($dateErrors, 422);
            }

            if ($request->has('data_publi')) {
                $payload['data_publi'] = $dataPubli;
            }

            $payload['data_expiracao'] = $dataExpiracao;
        }

        $campanha->update($payload);

        return response()->json([
            'message' => 'Campanha atualizada!',
            'data' => $campanha->fresh('hemocentro'),
        ]);
    }

    // DELETE /api/auth/campanhas/{id}
    public function destroy($id)
    {
        $campanha = Campanha::findOrFail($id);
        $campanha->status = false;
        $campanha->save();
        $campanha->delete();

        return response()->json(['message' => 'Campanha removida!']);
    }

    // POST /api/auth/campanhas/{id}/disparar
    public function disparar($id)
    {
        try {
            $campanha = Campanha::findOrFail($id);
            $this->garantirEstruturaDisparoCampanha();

            $query = User::where('role_id', 1)
                ->where('status', DB::raw('true'))
                ->whereNotNull('email');

            if ($campanha->tipo_sangue) {
                $query->where('tipo_sang', $campanha->tipo_sangue);
            }

            if ($campanha->hemocentro_id) {
                $query->whereHas('triagens', function ($q) use ($campanha) {
                    $q->where('hemocentro_id', $campanha->hemocentro_id);
                });
            }

            $doadores = $query->get(['id', 'name', 'email', 'tipo_sang', 'tempo_restricao', 'criado_em']);

            $doadoresSegmentados = $this->segmentarViaMl($doadores, $campanha);

            $totalEnfileirado = 0;
            foreach ($doadoresSegmentados as $doador) {
                SendCampaignEmailJob::dispatch($campanha->id, $doador->id);
                $totalEnfileirado++;
            }

            $campanha->update(['total_disparado' => $totalEnfileirado]);

            Log::info('CAMPANHA DISPARADA', [
                'campanha_id' => $campanha->id,
                'titulo' => $campanha->titulo,
                'total_elegiveis' => $doadores->count(),
                'total_segmentados' => $doadoresSegmentados->count(),
                'total_disparado' => $totalEnfileirado,
                'disparado_por' => Auth::id(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'campanha_id' => $campanha->id,
                'message' => "Campanha enfileirada para {$totalEnfileirado} doadores.",
                'total_elegiveis' => $doadores->count(),
                'total_segmentados' => $doadoresSegmentados->count(),
                'total_disparado' => $totalEnfileirado,
                'segmentacao' => $totalEnfileirado < $doadores->count() ? 'ml' : 'completa',
                'modo_envio' => 'queue',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erro ao disparar campanha.',
                'exception' => class_basename($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    private function segmentarViaMl($doadores, Campanha $campanha)
    {
        $mlUrl = config('services.ml.url');
        $mlKey = config('services.ml.key');

        if (! $mlUrl) {
            Log::info('ML nao configurado - disparando para todos os elegiveis', [
                'campanha_id' => $campanha->id,
            ]);

            return $doadores;
        }

        try {
            $doacaoStats = Doacao::query()
                ->whereIn('user_id', $doadores->pluck('id'))
                ->selectRaw('user_id, COUNT(*) as frequencia_doacoes, COALESCE(SUM(quantidade), 0) as volume_total_cc, MAX(data_hora_doacao) as ultima_doacao, MIN(data_hora_doacao) as primeira_doacao')
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            $payload = [
                'campanha_id' => $campanha->id,
                'tipo_sangue' => $campanha->tipo_sangue,
                'doadores'    => $doadores->map(function ($d) use ($doacaoStats) {
                    $stats = $doacaoStats->get($d->id);
                    $ultimaDoacao = $stats?->ultima_doacao ? \Carbon\Carbon::parse($stats->ultima_doacao) : null;
                    $primeiraReferencia = $stats?->primeira_doacao ?: $d->criado_em;
                    $primeiraDoacao = $primeiraReferencia ? \Carbon\Carbon::parse($primeiraReferencia) : now();
                    $recenciaMeses = $ultimaDoacao ? $ultimaDoacao->diffInMonths(now()) : $primeiraDoacao->diffInMonths(now());
                    $tempoMeses = max(1, $primeiraDoacao->diffInMonths(now()));
                    $frequencia = (int) ($stats?->frequencia_doacoes ?? 0);
                    $riscoInatividade = $recenciaMeses <= 6 ? 'Ativo' : ($recenciaMeses <= 12 ? 'Atencao' : ($recenciaMeses <= 24 ? 'Em_Risco' : 'Inativo'));

                    return [
                        'id'                          => $d->id,
                        'tipo_sang'                   => $d->tipo_sang,
                        'tempo_restricao'             => optional($d->tempo_restricao)->toDateString(),
                        'cadastrado_em'               => optional($d->criado_em)->toDateTimeString(),
                        'recencia_meses'              => $recenciaMeses,
                        'frequencia_doacoes'          => $frequencia,
                        'volume_total_cc'             => (float) ($stats?->volume_total_cc ?? 0),
                        'tempo_desde_primeira_doacao' => $tempoMeses,
                        'risco_inatividade'           => $riscoInatividade,
                    ];
                })->values()->all(),
            ];

            $request = Http::timeout(10)
                ->withHeaders(['Authorization' => "Bearer {$mlKey}"])
                ->when(app()->environment('local'), fn ($http) => $http->withoutVerifying());

            $response = $request->post("{$mlUrl}/segmentar", $payload);

            if ($response->successful()) {
                $ids = $response->json('user_ids', []);
                if (! empty($ids)) {
                    $segmentados = $doadores->whereIn('id', $ids)->values();
                    Log::info('ML SEGMENTOU DOADORES', [
                        'campanha_id' => $campanha->id,
                        'total_antes' => $doadores->count(),
                        'total_depois' => $segmentados->count(),
                    ]);

                    return $segmentados;
                }
            }

            Log::warning('ML retornou resposta invalida - usando fallback', [
                'campanha_id' => $campanha->id,
                'status' => $response->status(),
            ]);

        } catch (\Throwable $e) {
            Log::warning('ML indisponivel - usando fallback local', [
                'campanha_id' => $campanha->id,
                'erro' => $e->getMessage(),
            ]);
        }

        return $doadores->values();
    }

    private function garantirEstruturaDisparoCampanha(): void
    {
        foreach (['campanhas', 'users', 'jobs'] as $tabela) {
            if (!Schema::hasTable($tabela)) {
                throw new \RuntimeException("Tabela obrigatoria ausente: {$tabela}.");
            }
        }
    }

    private function campaignEmailData(Campanha $campanha, User $doador): array
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        return [
            'campanha' => $campanha,
            'doador' => $doador,
            'ctaUrl' => "{$frontendUrl}/login",
            'preheader' => $campanha->subtitulo ?: 'Sua doaÃ§Ã£o pode ajudar a manter os estoques de sangue seguros.',
            'bloodType' => $campanha->tipo_sangue ?: null,
            'publishDate' => optional($campanha->data_publi)->format('d/m/Y'),
            'expireDate' => optional($campanha->data_expiracao)->format('d/m/Y'),
        ];
    }

    private function validateAndNormalizeCampaignDates(?string $dataPubli, ?string $dataExpiracao): array
    {
        $errors = [];

        $dataPubliNormalizada = $this->normalizeCampaignDate($dataPubli, 'data_publi', $errors);
        $dataExpiracaoNormalizada = $this->normalizeCampaignDate($dataExpiracao, 'data_expiracao', $errors, true);

        $hoje = new \DateTimeImmutable('today');

        if ($dataPubliNormalizada) {
            $dataPubliDia = new \DateTimeImmutable(substr($dataPubliNormalizada, 0, 10));

            if ($dataPubliDia < $hoje) {
                $errors['data_publi'] = ['A data de publicaÃ§Ã£o nÃ£o pode ser anterior a hoje.'];
            }
        }

        if ($dataExpiracaoNormalizada) {
            $dataExpiracaoDia = new \DateTimeImmutable(substr($dataExpiracaoNormalizada, 0, 10));

            if ($dataExpiracaoDia < $hoje) {
                $errors['data_expiracao'] = ['A data de expiraÃ§Ã£o nÃ£o pode ser anterior a hoje.'];
            }
        }

        if ($dataPubliNormalizada && $dataExpiracaoNormalizada) {
            $dataPubliDia = new \DateTimeImmutable(substr($dataPubliNormalizada, 0, 10));
            $dataExpiracaoDia = new \DateTimeImmutable(substr($dataExpiracaoNormalizada, 0, 10));

            if ($dataExpiracaoDia <= $dataPubliDia && ! isset($errors['data_expiracao'])) {
                $errors['data_expiracao'] = ['A data de expiraÃ§Ã£o deve ser em um dia posterior Ã  data de publicaÃ§Ã£o.'];
            }
        }

        return [$dataPubliNormalizada, $dataExpiracaoNormalizada, $errors];
    }

    private function normalizeCampaignDate(?string $value, string $field, array &$errors, bool $nullable = false): ?string
    {
        if ($value === null || trim($value) === '') {
            if ($nullable) {
                return null;
            }

            $errors[$field] = ['O campo data Ã© obrigatÃ³rio.'];

            return null;
        }

        if (! preg_match('/^(\d{4,})-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?)?$/', $value, $matches)) {
            $errors[$field] = ['Data invÃ¡lida. Use um formato de data vÃ¡lido.'];

            return null;
        }

        if (strlen($matches[1]) !== 4) {
            $errors[$field] = ['O ano informado deve estar entre 2000 e 2100.'];

            return null;
        }

        $year = (int) $matches[1];

        if ($year < 2000 || $year > 2100) {
            $errors[$field] = ['O ano informado deve estar entre 2000 e 2100.'];

            return null;
        }

        $formats = ['Y-m-d H:i:s', 'Y-m-d\TH:i', 'Y-m-d'];

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            $dateErrors = \DateTimeImmutable::getLastErrors();

            if (
                $date instanceof \DateTimeImmutable
                && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
            ) {
                if ($format === 'Y-m-d') {
                    $date = $date->setTime(0, 0, 0);
                }

                return $date->format('Y-m-d H:i:s');
            }
        }

        $errors[$field] = ['Data invÃ¡lida. Use um formato de data vÃ¡lido.'];

        return null;
    }
}
