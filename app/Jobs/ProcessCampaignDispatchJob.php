<?php

namespace App\Jobs;

use App\Models\Campanha;
use App\Models\Doacao;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessCampaignDispatchJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $campanhaId,
        public ?int $disparadoPor = null,
    ) {
        $this->onQueue('campaign-emails');
    }

    public function handle(): void
    {
        $campanha = Campanha::find($this->campanhaId);

        if (! $campanha) {
            Log::warning('CAMPANHA NAO ENCONTRADA PARA DISPARO', [
                'campanha_id' => $this->campanhaId,
            ]);

            return;
        }

        $query = User::query()
            ->where('role_id', 1)
            ->where('status', DB::raw('true'))
            ->whereNotNull('email');

        if ($campanha->tipo_sangue) {
            $query->where('tipo_sang', $campanha->tipo_sangue);
        }

        if ($campanha->hemocentro_id) {
            Log::info('CAMPANHA COM HEMOCENTRO DEFINIDO - sem restringir elegiveis por triagem previa', [
                'campanha_id' => $campanha->id,
                'hemocentro_id' => $campanha->hemocentro_id,
            ]);
        }

        $doadores = $query->get(['id', 'name', 'email', 'tipo_sang', 'tempo_restricao', 'criado_em']);
        $doadoresSegmentados = $this->segmentarViaMl($doadores, $campanha);

        $totalEnfileirado = 0;
        foreach ($doadoresSegmentados as $doador) {
            SendCampaignEmailJob::dispatchSync($campanha->id, $doador->id);
            $totalEnfileirado++;
        }

        $campanha->update(['total_disparado' => $totalEnfileirado]);

        Log::info('CAMPANHA PROCESSADA EM FILA', [
            'campanha_id' => $campanha->id,
            'titulo' => $campanha->titulo,
            'total_elegiveis' => $doadores->count(),
            'total_segmentados' => $doadoresSegmentados->count(),
            'total_disparado' => $totalEnfileirado,
            'disparado_por' => $this->disparadoPor,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('FALHA AO PROCESSAR DISPARO DA CAMPANHA', [
            'campanha_id' => $this->campanhaId,
            'disparado_por' => $this->disparadoPor,
            'erro' => $e->getMessage(),
            'arquivo' => $e->getFile(),
            'linha' => $e->getLine(),
        ]);
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
                'doadores' => $doadores->map(function ($doador) use ($doacaoStats) {
                    $stats = $doacaoStats->get($doador->id);
                    $ultimaDoacao = $stats?->ultima_doacao ? \Carbon\Carbon::parse($stats->ultima_doacao) : null;
                    $primeiraReferencia = $stats?->primeira_doacao ?: $doador->criado_em;
                    $primeiraDoacao = $primeiraReferencia ? \Carbon\Carbon::parse($primeiraReferencia) : now();
                    $recenciaMeses = $ultimaDoacao ? $ultimaDoacao->diffInMonths(now()) : $primeiraDoacao->diffInMonths(now());
                    $tempoMeses = max(1, $primeiraDoacao->diffInMonths(now()));
                    $frequencia = (int) ($stats?->frequencia_doacoes ?? 0);
                    $riscoInatividade = $recenciaMeses <= 6 ? 'Ativo' : ($recenciaMeses <= 12 ? 'Atencao' : ($recenciaMeses <= 24 ? 'Em_Risco' : 'Inativo'));

                    return [
                        'id' => $doador->id,
                        'tipo_sang' => $doador->tipo_sang,
                        'tempo_restricao' => optional($doador->tempo_restricao)->toDateString(),
                        'cadastrado_em' => optional($doador->criado_em)->toDateTimeString(),
                        'recencia_meses' => $recenciaMeses,
                        'frequencia_doacoes' => $frequencia,
                        'volume_total_cc' => (float) ($stats?->volume_total_cc ?? 0),
                        'tempo_desde_primeira_doacao' => $tempoMeses,
                        'risco_inatividade' => $riscoInatividade,
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
}