<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Doacao;
use App\Models\Estoque;
use App\Models\User;
use App\Models\Hemocentro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class RelatorioController extends Controller
{
    private const TIPOS_SANGUINEOS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    /**
     * JSON: Resumo executivo completo — todos os KPIs em uma única chamada.
     */
    public function resumo(Request $request)
    {
        $hemocentroId = $this->getHemocentroId($request);
        $dias         = (int) $request->query('dias', 30);

        $agendamentoBase = Agendamento::query();
        $doacaoBase      = Doacao::query();

        if ($hemocentroId) {
            $agendamentoBase->where('hemocentro_id', $hemocentroId);
            $doacaoBase->where('hemocentro_id', $hemocentroId);
        }

        // Totais de agendamento
        $totalAgendados   = (clone $agendamentoBase)->where('criado_em', '>=', Carbon::now()->subDays($dias))->count();
        $totalConcluidos  = (clone $agendamentoBase)->where('criado_em', '>=', Carbon::now()->subDays($dias))->where('status_agendamento', 'CON')->count();
        $totalCancelados  = (clone $agendamentoBase)->where('criado_em', '>=', Carbon::now()->subDays($dias))->where('status_agendamento', 'CAN')->count();

        // Doações no período
        $doacoesPeriodo = (clone $doacaoBase)
            ->where('data_hora_doacao', '>=', Carbon::now()->subDays($dias))
            ->get();

        $totalDoacoes      = $doacoesPeriodo->count();
        $volumeTotalMl     = $doacoesPeriodo->sum('quantidade');
        $mediaVolumeMl     = $totalDoacoes > 0 ? round($volumeTotalMl / $totalDoacoes, 0) : 0;
        $volumeAnteriorMl  = (clone $doacaoBase)
            ->whereBetween('data_hora_doacao', [Carbon::now()->subDays($dias * 2), Carbon::now()->subDays($dias)])
            ->sum('quantidade');

        $variacaoVolume = $volumeAnteriorMl > 0
            ? round((($volumeTotalMl - $volumeAnteriorMl) / $volumeAnteriorMl) * 100, 1)
            : null;

        // Taxa de conversão e cancelamento
        $taxaConversao    = $totalAgendados > 0 ? round(($totalConcluidos / $totalAgendados) * 100, 1) : 0;
        $taxaCancelamento = $totalAgendados > 0 ? round(($totalCancelados / $totalAgendados) * 100, 1) : 0;

        // Tipo sanguíneo mais doado
        $tipoPorContagem = $doacoesPeriodo->groupBy('tipo_sangue')
            ->map->count()
            ->sortDesc();
        $tipoMaisDoado = $tipoPorContagem->keys()->first();

        // Doadores únicos no período
        $doadoresUnicos = (clone $doacaoBase)
            ->where('data_hora_doacao', '>=', Carbon::now()->subDays($dias))
            ->distinct('user_id')
            ->count('user_id');

        // Estoque crítico
        $estoqueCritico = $this->estoquesCriticos($hemocentroId);

        // Triagem: taxa de aptidão
        $triagemQuery = DB::table('triagens');
        if ($hemocentroId) {
            $triagemQuery->where('hemocentro_id', $hemocentroId);
        }
        $triagemQuery->where('created_at', '>=', Carbon::now()->subDays($dias));
        $totalTriagens = (clone $triagemQuery)->count();
        $totalAptos    = (clone $triagemQuery)->where('apto', DB::raw('true'))->count();
        $taxaAptidao   = $totalTriagens > 0 ? round(($totalAptos / $totalTriagens) * 100, 1) : null;

        // Doações por tipo sanguíneo no período
        $distribuicaoTipo = array_fill_keys(self::TIPOS_SANGUINEOS, 0);
        foreach ($tipoPorContagem as $tipo => $total) {
            if (isset($distribuicaoTipo[$tipo])) {
                $distribuicaoTipo[$tipo] = $total;
            }
        }

        // Performance mensal (últimos 6 meses)
        $performanceMensal = $this->performanceMensalQuery($hemocentroId, 6);

        // Doações por dia da semana no período (compatível com PostgreSQL)
        $doacoesDiaSemanaRaw = (clone $doacaoBase)
            ->where('data_hora_doacao', '>=', Carbon::now()->subDays($dias))
            ->get(['data_hora_doacao']);

        $diasLabel = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
        $porDiaSemana = array_fill_keys($diasLabel, 0);
        foreach ($doacoesDiaSemanaRaw as $d) {
            if (!$d->data_hora_doacao) continue;
            $porDiaSemana[$diasLabel[Carbon::parse($d->data_hora_doacao)->dayOfWeek]]++;
        }
        $doacoesDiaSemana = [];
        foreach ($diasLabel as $label) {
            $doacoesDiaSemana[] = ['dia' => $label, 'total' => $porDiaSemana[$label]];
        }

        return response()->json([
            'periodo_dias'          => $dias,
            'gerado_em'             => now()->toIso8601String(),
            'kpis' => [
                'total_doacoes'         => $totalDoacoes,
                'volume_total_ml'       => round($volumeTotalMl, 0),
                'volume_total_litros'   => round($volumeTotalMl / 1000, 2),
                'media_volume_ml'       => $mediaVolumeMl,
                'doadores_unicos'       => $doadoresUnicos,
                'taxa_conversao_pct'    => $taxaConversao,
                'taxa_cancelamento_pct' => $taxaCancelamento,
                'tipo_mais_doado'       => $tipoMaisDoado,
                'taxa_aptidao_pct'      => $taxaAptidao,
                'variacao_volume_pct'   => $variacaoVolume,
                'estoques_criticos'     => count($estoqueCritico),
            ],
            'agendamentos' => [
                'total'      => $totalAgendados,
                'concluidos' => $totalConcluidos,
                'cancelados' => $totalCancelados,
                'pendentes'  => $totalAgendados - $totalConcluidos - $totalCancelados,
            ],
            'distribuicao_tipo_sanguineo' => $distribuicaoTipo,
            'estoque_critico'             => $estoqueCritico,
            'performance_mensal'          => $performanceMensal,
            'doacoes_por_dia_semana'       => $doacoesDiaSemana,
        ]);
    }

    /**
     * JSON: Resumo de agendamentos por status.
     */
    public function donationsSummary(Request $request)
    {
        $hemocentroId = $this->getHemocentroId($request);
        $dias = (int) $request->query('dias', 30);

        $query = Agendamento::query()
            ->select('status_agendamento', DB::raw('COUNT(*) as total'))
            ->where('criado_em', '>=', Carbon::now()->subDays($dias))
            ->groupBy('status_agendamento');

        if ($hemocentroId) {
            $query->where('hemocentro_id', $hemocentroId);
        }

        $resumo = $query->pluck('total', 'status_agendamento')->toArray();

        $labels = [
            'AGE' => 'Agendado',
            'CAN' => 'Cancelado',
            'CON' => 'Concluído',
            'EXC' => 'Excedido/Substituído',
        ];

        $data = [];
        foreach ($labels as $status => $label) {
            $data[] = ['status' => $status, 'label' => $label, 'total' => (int)($resumo[$status] ?? 0)];
        }

        return response()->json($data);
    }

    /**
     * JSON: Saldo de estoque por tipo sanguíneo.
     */
    public function bloodStock(Request $request)
    {
        $hemocentroId = $this->getHemocentroId($request);

        $query = Estoque::query()
            ->select('tipo_sangue', DB::raw('SUM(quantidade) as total'), DB::raw('SUM(quantidade_minima) as minimo'))
            ->groupBy('tipo_sangue');

        if ($hemocentroId) {
            $query->where('hemocentro_id', $hemocentroId);
        }

        $rows = $query->get()->keyBy('tipo_sangue');

        $data = [];
        foreach (self::TIPOS_SANGUINEOS as $tipo) {
            $row    = $rows[$tipo] ?? null;
            $qtd    = (float)($row->total ?? 0);
            $minimo = (float)($row->minimo ?? 0);
            $data[] = [
                'tipo'      => $tipo,
                'quantidade' => $qtd,
                'minimo'    => $minimo,
                'critico'   => $qtd < $minimo,
                'pct_nivel' => $minimo > 0 ? min(100, round(($qtd / $minimo) * 100)) : null,
            ];
        }

        return response()->json($data);
    }

    /**
     * JSON: Desempenho mensal (últimos N meses).
     */
    public function performanceMonthly(Request $request)
    {
        $hemocentroId = $this->getHemocentroId($request);
        $meses        = (int) $request->query('meses', 12);

        return response()->json($this->performanceMensalQuery($hemocentroId, $meses));
    }

    /**
     * PDF: Relatório de Doações — profissional com KPIs e gráfico SVG.
     */
    public function pdfDoacoes(Request $request)
    {
        $hemocentroId = $this->getHemocentroId($request);
        $filtros      = $this->reportFilters($request);
        $periodo      = $filtros['periodo_dias'];

        $query = Doacao::with(['doador', 'funcionario', 'hemocentro'])
            ->orderBy('data_hora_doacao', 'desc');
        $this->applyPeriod($query, 'data_hora_doacao', $filtros);
        $this->applyBloodType($query, 'tipo_sangue', $filtros);

        if ($hemocentroId) {
            $query->where('hemocentro_id', $hemocentroId);
        }

        $doacoes     = $query->get();
        $volumeTotal = $doacoes->sum('quantidade');
        $mediaVol    = $doacoes->count() > 0 ? round($volumeTotal / $doacoes->count(), 0) : 0;

        $distTipo = array_fill_keys(self::TIPOS_SANGUINEOS, 0);
        foreach ($doacoes->groupBy('tipo_sangue') as $tipo => $items) {
            if (isset($distTipo[$tipo])) {
                $distTipo[$tipo] = $items->count();
            }
        }
        $maxDistTipo = max($distTipo) ?: 1;

        $porDia = $doacoes->groupBy(fn ($d) => Carbon::parse($d->data_hora_doacao)->format('Y-m-d'))
            ->map->count()
            ->sortKeys();

        // Taxa de conversão
        $agBase = Agendamento::query();
        $this->applyPeriod($agBase, 'criado_em', $filtros);
        if ($hemocentroId) {
            $agBase->where('hemocentro_id', $hemocentroId);
        }
        $totalAgendamentos = (clone $agBase)->whereIn('status_agendamento', ['AGE', 'CON', 'FIN'])->count();
        $totalFinalizados  = (clone $agBase)->where('status_agendamento', 'FIN')->count();
        $taxaConversao     = $totalAgendamentos > 0 ? round($totalFinalizados / $totalAgendamentos * 100, 1) : 0;

        // Doações por dia da semana
        $diasLabel = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
        $porDiaSemana = array_fill_keys($diasLabel, 0);
        foreach ($doacoes as $d) {
            if (!$d->data_hora_doacao) continue;
            $porDiaSemana[$diasLabel[Carbon::parse($d->data_hora_doacao)->dayOfWeek]]++;
        }

        // Triagens no período
        $triagemBase = DB::table('triagens');
        $this->applyPeriod($triagemBase, 'created_at', $filtros);
        if ($hemocentroId) {
            $triagemBase->where('hemocentro_id', $hemocentroId);
        }
        $triagensTotal = (clone $triagemBase)->count();
        $triagensAptas = (clone $triagemBase)->where('apto', DB::raw('true'))->count();
        $taxaAptidao   = round($triagensAptas / max(1, $triagensTotal) * 100, 1);

        $pdf = Pdf::loadView('relatorios.doacoes', [
            ...$this->reportDocumentMeta('doacoes', $filtros),
            'doacoes'            => $doacoes,
            'periodo'            => $periodo,
            'periodo_label'      => $filtros['label'],
            'tipo_filtro'        => $filtros['tipo_sangue'] ?? 'Todos os tipos',
            'gerado_em'          => now()->format('d/m/Y H:i'),
            'unidade'            => $hemocentroId ? Hemocentro::find($hemocentroId)?->nome : 'Todas as Unidades',
            'volume_total'       => $volumeTotal,
            'media_vol'          => $mediaVol,
            'dist_tipo'          => $distTipo,
            'max_dist_tipo'      => $maxDistTipo,
            'por_dia'            => $porDia,
            'taxa_conversao'     => $taxaConversao,
            'total_agendamentos' => $totalAgendamentos,
            'total_finalizados'  => $totalFinalizados,
            'triagens_total'     => $triagensTotal,
            'triagens_aptas'     => $triagensAptas,
            'taxa_aptidao'       => $taxaAptidao,
            'por_dia_semana'     => $porDiaSemana,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('relatorio-doacoes-' . now()->format('Ymd') . '.pdf');
    }

    /**
     * PDF: Relatório de Estoque.
     */
    public function pdfEstoque(Request $request)
    {
        $hemocentroId = $this->getHemocentroId($request);
        $filtros      = $this->reportFilters($request);

        $query = Estoque::with('hemocentro');
        $this->applyBloodType($query, 'tipo_sangue', $filtros);
        if ($hemocentroId) {
            $query->where('hemocentro_id', $hemocentroId);
        }

        $estoques     = $query->orderBy('hemocentro_id')->orderBy('tipo_sangue')->get();
        $criticos     = $estoques->filter(fn ($e) => $e->quantidade < $e->quantidade_minima);
        $estaveis     = $estoques->filter(fn ($e) => $e->quantidade >= $e->quantidade_minima);
        $volumeGlobal = $estoques->sum('quantidade');

        $porTipo = array_fill_keys(self::TIPOS_SANGUINEOS, ['qtd' => 0.0, 'min' => 0.0]);
        foreach ($estoques as $e) {
            if (isset($porTipo[$e->tipo_sangue])) {
                $porTipo[$e->tipo_sangue]['qtd'] += $e->quantidade;
                $porTipo[$e->tipo_sangue]['min'] += $e->quantidade_minima;
            }
        }
        $maxEstoque = max(array_column($porTipo, 'qtd')) ?: 1;

        // Entradas por tipo nos últimos 30 dias
        $histQuery = Doacao::query()
            ->when($hemocentroId, fn ($q) => $q->where('hemocentro_id', $hemocentroId))
            ->selectRaw('tipo_sangue, SUM(quantidade) as total_ml')
            ->groupBy('tipo_sangue');
        $this->applyPeriod($histQuery, 'data_hora_doacao', $filtros);
        $this->applyBloodType($histQuery, 'tipo_sangue', $filtros);
        $hist30dRaw = $histQuery->pluck('total_ml', 'tipo_sangue')->toArray();

        $historico30d = array_fill_keys(self::TIPOS_SANGUINEOS, 0.0);
        foreach ($hist30dRaw as $tipo => $total) {
            if (isset($historico30d[$tipo])) {
                $historico30d[$tipo] = round((float) $total, 1);
            }
        }

        // Projeção de duração por tipo
        $projecaoDias = [];
        foreach (self::TIPOS_SANGUINEOS as $tipo) {
            $qtdAtual   = $porTipo[$tipo]['qtd'];
            $entrada30d = $historico30d[$tipo];
            $consumoDia = $entrada30d / 30;
            $projecaoDias[$tipo] = [
                'qtd_atual'    => $qtdAtual,
                'entrada_30d'  => $entrada30d,
                'consumo_dia'  => round($consumoDia, 2),
                'duracao_dias' => $consumoDia > 0 ? (int) round($qtdAtual / $consumoDia) : null,
            ];
        }

        $pdf = Pdf::loadView('relatorios.estoque', [
            ...$this->reportDocumentMeta('estoque', $filtros),
            'estoques'      => $estoques,
            'criticos'      => $criticos,
            'estaveis'      => $estaveis,
            'periodo_label' => $filtros['label'],
            'tipo_filtro'   => $filtros['tipo_sangue'] ?? 'Todos os tipos',
            'gerado_em'     => now()->format('d/m/Y H:i'),
            'unidade'       => $hemocentroId ? Hemocentro::find($hemocentroId)?->nome : 'Global',
            'volume_global' => $volumeGlobal,
            'por_tipo'      => $porTipo,
            'max_estoque'   => $maxEstoque,
            'historico_30d' => $historico30d,
            'projecao_dias' => $projecaoDias,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('relatorio-estoque-' . now()->format('Ymd') . '.pdf');
    }

    /**
     * PDF: Relatório de Doadores.
     */
    public function pdfDoadores(Request $request)
    {
        $hemocentroId = $this->getHemocentroId($request);
        $filtros      = $this->reportFilters($request);

        $query = User::where('role_id', 1)->orderBy('name');
        $this->applyBloodType($query, 'tipo_sang', $filtros);
        $this->applyPeriod($query, 'criado_em', $filtros);
        if ($hemocentroId) {
            $query->whereHas('triagens', fn ($q) => $q->where('hemocentro_id', $hemocentroId));
        }
        $doadores = $query->get();

        $distTipo = array_fill_keys(self::TIPOS_SANGUINEOS, 0);
        foreach ($doadores->groupBy('tipo_sang') as $tipo => $items) {
            if (isset($distTipo[$tipo])) {
                $distTipo[$tipo] = $items->count();
            }
        }
        $maxDistTipo = max($distTipo) ?: 1;

        $distSexo = $doadores->groupBy('sexo')->map->count()->toArray();

        $faixas = ['18-25' => 0, '26-35' => 0, '36-45' => 0, '46-55' => 0, '56+' => 0, 'N/D' => 0];
        foreach ($doadores as $d) {
            if (!$d->data_nasc) { $faixas['N/D']++; continue; }
            $idade = Carbon::parse($d->data_nasc)->age;
            if ($idade <= 25)       $faixas['18-25']++;
            elseif ($idade <= 35)   $faixas['26-35']++;
            elseif ($idade <= 45)   $faixas['36-45']++;
            elseif ($idade <= 55)   $faixas['46-55']++;
            else                    $faixas['56+']++;
        }
        $maxFaixa = max($faixas) ?: 1;

        // Doadores com ao menos 1 doação
        $doadoresComDoacoes = Doacao::query()
            ->when($hemocentroId, fn ($q) => $q->where('hemocentro_id', $hemocentroId));
        $this->applyPeriod($doadoresComDoacoes, 'data_hora_doacao', $filtros);
        $this->applyBloodType($doadoresComDoacoes, 'tipo_sangue', $filtros);
        $doadoresComDoacoes = $doadoresComDoacoes->distinct('user_id')->count('user_id');

        $totalDoacoes = Doacao::query()
            ->when($hemocentroId, fn ($q) => $q->where('hemocentro_id', $hemocentroId));
        $this->applyPeriod($totalDoacoes, 'data_hora_doacao', $filtros);
        $this->applyBloodType($totalDoacoes, 'tipo_sangue', $filtros);
        $totalDoacoes = $totalDoacoes->count();
        $mediaDoacoesPorDoador = $doadoresComDoacoes > 0
            ? round($totalDoacoes / $doadoresComDoacoes, 1) : 0;

        // Top 10 doadores por total de doações
        $topDoadoresRaw = Doacao::query()
            ->select('user_id', DB::raw('COUNT(*) as total_doacoes'), DB::raw('MAX(data_hora_doacao) as ultima_doacao'))
            ->when($hemocentroId, fn ($q) => $q->where('hemocentro_id', $hemocentroId));
        $this->applyPeriod($topDoadoresRaw, 'data_hora_doacao', $filtros);
        $this->applyBloodType($topDoadoresRaw, 'tipo_sangue', $filtros);
        $topDoadoresRaw = $topDoadoresRaw->groupBy('user_id')
            ->orderByDesc('total_doacoes')
            ->limit(10)
            ->get();

        $topDoadores = $topDoadoresRaw->map(function ($row) {
            $user = User::find($row->user_id);
            return [
                'name'          => $user?->name ?? 'N/D',
                'tipo_sang'     => $user?->tipo_sang ?? '—',
                'total_doacoes' => (int) $row->total_doacoes,
                'ultima_doacao' => $row->ultima_doacao
                    ? Carbon::parse($row->ultima_doacao)->format('d/m/Y') : '—',
            ];
        });

        // Doadores em restrição temporária
        $doadoresRestricao = $doadores->filter(
            fn ($d) => $d->tempo_restricao && Carbon::parse($d->tempo_restricao)->isFuture()
        )->count();

        $pdf = Pdf::loadView('relatorios.doadores', [
            ...$this->reportDocumentMeta('doadores', $filtros),
            'doadores'                 => $doadores,
            'periodo_label'            => $filtros['label'],
            'tipo_filtro'              => $filtros['tipo_sangue'] ?? 'Todos os tipos',
            'gerado_em'                => now()->format('d/m/Y H:i'),
            'unidade'                  => $hemocentroId ? Hemocentro::find($hemocentroId)?->nome : 'Geral',
            'dist_tipo'                => $distTipo,
            'max_dist'                 => $maxDistTipo,
            'dist_sexo'                => $distSexo,
            'faixas'                   => $faixas,
            'max_faixa'                => $maxFaixa,
            'doadores_com_doacoes'     => $doadoresComDoacoes,
            'media_doacoes_por_doador' => $mediaDoacoesPorDoador,
            'top_doadores'             => $topDoadores,
            'doadores_restricao'       => $doadoresRestricao,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('relatorio-doadores-' . now()->format('Ymd') . '.pdf');
    }

    public function pdfAgendamentos(Request $request)
    {
        $hemocentroId = $this->getHemocentroId($request);
        $filtros      = $this->reportFilters($request);
        $periodo      = $filtros['periodo_dias'];

        $query = Agendamento::with(['doador', 'hemocentro'])
            ->orderBy('data_hora_doacao', 'desc');
        $this->applyPeriod($query, 'criado_em', $filtros);
        $this->applyDonorBloodType($query, $filtros);
        if ($hemocentroId) {
            $query->where('hemocentro_id', $hemocentroId);
        }

        $agendamentos  = $query->get();
        $total         = $agendamentos->count();
        $porStatus     = [
            'AGE' => $agendamentos->where('status_agendamento', 'AGE')->count(),
            'CON' => $agendamentos->where('status_agendamento', 'CON')->count(),
            'FIN' => $agendamentos->where('status_agendamento', 'FIN')->count(),
            'CAN' => $agendamentos->where('status_agendamento', 'CAN')->count(),
        ];
        $taxaConclusao = $total > 0 ? round($porStatus['FIN'] / $total * 100, 1) : 0;

        $porHemocentro = $agendamentos->groupBy('hemocentro_id')
            ->map(fn ($g) => ['nome' => $g->first()->hemocentro?->nome ?? 'N/D', 'total' => $g->count()])
            ->sortByDesc('total')
            ->values();
        $maxHemo = $porHemocentro->max('total') ?: 1;

        $diasLabel    = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
        $porDiaSemana = array_fill_keys($diasLabel, 0);
        foreach ($agendamentos as $ag) {
            if (!$ag->data_hora_doacao) continue;
            $porDiaSemana[$diasLabel[Carbon::parse($ag->data_hora_doacao)->dayOfWeek]]++;
        }

        $pdf = Pdf::loadView('relatorios.agendamentos', [
            ...$this->reportDocumentMeta('agendamentos', $filtros),
            'agendamentos'   => $agendamentos,
            'periodo'        => $periodo,
            'periodo_label'  => $filtros['label'],
            'tipo_filtro'    => $filtros['tipo_sangue'] ?? 'Todos os tipos',
            'gerado_em'      => now()->format('d/m/Y H:i'),
            'unidade'        => $hemocentroId ? Hemocentro::find($hemocentroId)?->nome : 'Todas as Unidades',
            'total'          => $total,
            'por_status'     => $porStatus,
            'taxa_conclusao' => $taxaConclusao,
            'por_hemocentro' => $porHemocentro,
            'max_hemo'       => $maxHemo,
            'por_dia_semana' => $porDiaSemana,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('relatorio-agendamentos-' . now()->format('Ymd') . '.pdf');
    }

    public function pdfTriagens(Request $request)
    {
        $hemocentroId = $this->getHemocentroId($request);
        $filtros      = $this->reportFilters($request);
        $periodo      = $filtros['periodo_dias'];

        $query = \App\Models\Triagem::with(['doador', 'funcionario', 'hemocentro', 'sinaisVitais'])
            ->orderBy('data_triagem', 'desc');
        $this->applyPeriod($query, 'data_triagem', $filtros);
        $this->applyDonorBloodType($query, $filtros);
        if ($hemocentroId) {
            $query->where('hemocentro_id', $hemocentroId);
        }

        $triagens    = $query->get();
        $total       = $triagens->count();
        $aptas       = $triagens->where('apto', true)->count();
        $inaptas     = $triagens->where('apto', false)->count();
        $taxaAptidao = $total > 0 ? round($aptas / $total * 100, 1) : 0;

        $pressoes = $triagens->where('apto', true)
            ->filter(fn ($t) => $t->sinaisVitais && $t->sinaisVitais->pressao_sistolica)
            ->map(fn ($t) => $t->sinaisVitais->pressao_sistolica);
        $mediaPressao = $pressoes->count() > 0 ? round($pressoes->avg(), 0) : null;

        $motivosRaw = $triagens->where('apto', false)
            ->whereNotNull('motivo_inaptidao')
            ->groupBy('motivo_inaptidao')
            ->map->count()
            ->sortDesc()
            ->take(5);
        $maxMotivo = $motivosRaw->max() ?: 1;

        $porHemocentro = $triagens->groupBy('hemocentro_id')
            ->map(fn ($g) => ['nome' => $g->first()->hemocentro?->nome ?? 'N/D', 'total' => $g->count()])
            ->sortByDesc('total')
            ->values();

        $pdf = Pdf::loadView('relatorios.triagens', [
            ...$this->reportDocumentMeta('triagens', $filtros),
            'triagens'       => $triagens,
            'periodo'        => $periodo,
            'periodo_label'  => $filtros['label'],
            'tipo_filtro'    => $filtros['tipo_sangue'] ?? 'Todos os tipos',
            'gerado_em'      => now()->format('d/m/Y H:i'),
            'unidade'        => $hemocentroId ? Hemocentro::find($hemocentroId)?->nome : 'Todas as Unidades',
            'total'          => $total,
            'aptas'          => $aptas,
            'inaptas'        => $inaptas,
            'taxa_aptidao'   => $taxaAptidao,
            'media_pressao'  => $mediaPressao,
            'motivos'        => $motivosRaw,
            'max_motivo'     => $maxMotivo,
            'por_hemocentro' => $porHemocentro,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('relatorio-triagens-' . now()->format('Ymd') . '.pdf');
    }

    public function pdfDesempenho(Request $request)
    {
        $hemocentroId = $this->getHemocentroId($request);
        $filtros      = $this->reportFilters($request);
        $meses        = max(1, (int) ceil(($filtros['periodo_dias'] ?: 180) / 30));

        $performanceMensal   = $this->performanceMensalQuery($hemocentroId, $meses, $filtros);
        $totalDoacoesPeriodo = array_sum(array_column($performanceMensal, 'total'));
        $volumeTotalMl       = array_sum(array_column($performanceMensal, 'volume_ml'));

        $mesAtual    = end($performanceMensal);
        $mesAnterior = prev($performanceMensal);
        reset($performanceMensal);
        $variacaoPct = ($mesAnterior && $mesAnterior['total'] > 0)
            ? round(($mesAtual['total'] - $mesAnterior['total']) / $mesAnterior['total'] * 100, 1)
            : null;

        $porHemocentroRaw = Doacao::query()
            ->select('hemocentro_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(quantidade) as volume_ml'))
            ->when($hemocentroId, fn ($q) => $q->where('hemocentro_id', $hemocentroId))
            ->groupBy('hemocentro_id')
            ->orderByDesc('total');
        $this->applyPeriod($porHemocentroRaw, 'data_hora_doacao', $filtros);
        $this->applyBloodType($porHemocentroRaw, 'tipo_sangue', $filtros);
        $porHemocentroRaw = $porHemocentroRaw->get();
        $totalGeral = $porHemocentroRaw->sum('total') ?: 1;

        $porHemocentro = $porHemocentroRaw->map(function ($row) use ($totalGeral) {
            $hemo = Hemocentro::find($row->hemocentro_id);
            return [
                'nome'      => $hemo?->nome ?? 'N/D',
                'total'     => (int) $row->total,
                'volume_ml' => round((float) $row->volume_ml, 0),
                'media_mes' => round($row->total / max(1, $meses), 1),
                'pct_total' => round($row->total / $totalGeral * 100, 1),
            ];
        });

        $topFuncionarios = Doacao::query()
            ->select('funcionario_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('funcionario_id')
            ->when($hemocentroId, fn ($q) => $q->where('hemocentro_id', $hemocentroId));
        $this->applyPeriod($topFuncionarios, 'data_hora_doacao', $filtros);
        $this->applyBloodType($topFuncionarios, 'tipo_sangue', $filtros);
        $topFuncionarios = $topFuncionarios->groupBy('funcionario_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $user = User::find($row->funcionario_id);
                $hemo = $user?->hemocentro_id ? Hemocentro::find($user->hemocentro_id) : null;
                return [
                    'name'    => $user?->name ?? 'N/D',
                    'unidade' => $hemo?->nome ?? '—',
                    'total'   => (int) $row->total,
                ];
            });

        $agMes         = Agendamento::query()->when($hemocentroId, fn ($q) => $q->where('hemocentro_id', $hemocentroId));
        $this->applyPeriod($agMes, 'criado_em', $filtros);
        $agTotal       = (clone $agMes)->count();
        $agConfirmados = (clone $agMes)->where('status_agendamento', 'CON')->count();
        $taxaOcupacao  = $agTotal > 0 ? round($agConfirmados / $agTotal * 100, 1) : 0;

        $pdf = Pdf::loadView('relatorios.desempenho', [
            ...$this->reportDocumentMeta('desempenho', $filtros),
            'gerado_em'          => now()->format('d/m/Y H:i'),
            'periodo_label'      => $filtros['label'],
            'tipo_filtro'        => $filtros['tipo_sangue'] ?? 'Todos os tipos',
            'unidade'            => $hemocentroId ? Hemocentro::find($hemocentroId)?->nome : 'Todas as Unidades',
            'performance_mensal' => $performanceMensal,
            'total_doacoes'      => $totalDoacoesPeriodo,
            'volume_total_ml'    => $volumeTotalMl,
            'variacao_pct'       => $variacaoPct,
            'hemocentros_ativos' => $porHemocentro->count(),
            'por_hemocentro'     => $porHemocentro,
            'top_funcionarios'   => $topFuncionarios,
            'taxa_ocupacao'      => $taxaOcupacao,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('relatorio-desempenho-' . now()->format('Ymd') . '.pdf');
    }

    // ─── Helpers privados ─────────────────────────────────────────────────────

    private function reportFilters(Request $request): array
    {
        $periodo = (string) $request->query('periodo', '30');
        $tipoSangue = $request->query('tipo_sangue');
        $tipoSangue = in_array($tipoSangue, self::TIPOS_SANGUINEOS, true) ? $tipoSangue : null;

        if ($request->query('data_inicio') && $request->query('data_fim')) {
            $inicio = Carbon::parse($request->query('data_inicio'))->startOfDay();
            $fim = Carbon::parse($request->query('data_fim'))->endOfDay();
            return [
                'inicio' => $inicio,
                'fim' => $fim,
                'periodo_dias' => max(1, $inicio->diffInDays($fim) + 1),
                'label' => $request->query('periodo_label') ?: $inicio->format('d/m/Y') . ' a ' . $fim->format('d/m/Y'),
                'tipo_sangue' => $tipoSangue,
            ];
        }

        if ($periodo === 'all') {
            return [
                'inicio' => null,
                'fim' => null,
                'periodo_dias' => 3650,
                'label' => $request->query('periodo_label') ?: 'Período completo',
                'tipo_sangue' => $tipoSangue,
            ];
        }

        if ($periodo === 'previous_month') {
            $inicio = now()->subMonthNoOverflow()->startOfMonth();
            $fim = now()->subMonthNoOverflow()->endOfMonth();
            return [
                'inicio' => $inicio,
                'fim' => $fim,
                'periodo_dias' => max(1, $inicio->diffInDays($fim) + 1),
                'label' => $request->query('periodo_label') ?: 'Mês anterior (' . $inicio->format('d/m/Y') . ' a ' . $fim->format('d/m/Y') . ')',
                'tipo_sangue' => $tipoSangue,
            ];
        }

        $dias = max(1, (int) $periodo ?: 30);
        $inicio = now()->subDays($dias)->startOfDay();
        $fim = now()->endOfDay();

        return [
            'inicio' => $inicio,
            'fim' => $fim,
            'periodo_dias' => $dias,
            'label' => $request->query('periodo_label') ?: 'Últimos ' . $dias . ' dias',
            'tipo_sangue' => $tipoSangue,
        ];
    }

    private function reportDocumentMeta(string $tipo, array $filtros): array
    {
        $nomes = [
            'doacoes' => 'Doações',
            'estoque' => 'Estoque',
            'doadores' => 'Doadores',
            'agendamentos' => 'Agendamentos',
            'triagens' => 'Triagens',
            'desempenho' => 'Desempenho',
        ];

        $subtitulos = [
            'doacoes' => 'Coletas realizadas, volume coletado, tipos sanguíneos e indicadores operacionais.',
            'estoque' => 'Níveis atuais, alertas críticos, entradas registradas e projeção de duração.',
            'doadores' => 'Cadastros, perfil dos doadores, atividade registrada e ranking de participação.',
            'agendamentos' => 'Status, conclusão, taxa de comparecimento e distribuição dos agendamentos.',
            'triagens' => 'Aptidão, motivos de inaptidão, sinais vitais e triagens realizadas.',
            'desempenho' => 'Performance operacional, volume coletado, produtividade e comparação mensal.',
        ];

        $nome = $nomes[$tipo] ?? ucfirst($tipo);
        $periodo = $filtros['label'] ?? 'Período completo';

        return [
            'titulo' => "Relatório de {$nome} - {$periodo}",
            'subtitulo' => $subtitulos[$tipo] ?? 'Indicadores consolidados do período selecionado.',
        ];
    }

    private function applyPeriod($query, string $column, array $filtros): void
    {
        if ($filtros['inicio'] && $filtros['fim']) {
            $query->whereBetween($column, [$filtros['inicio'], $filtros['fim']]);
        }
    }

    private function applyBloodType($query, string $column, array $filtros): void
    {
        if ($filtros['tipo_sangue']) {
            $query->where($column, $filtros['tipo_sangue']);
        }
    }

    private function applyDonorBloodType($query, array $filtros): void
    {
        if ($filtros['tipo_sangue']) {
            $query->whereHas('doador', fn ($q) => $q->where('tipo_sang', $filtros['tipo_sangue']));
        }
    }

    private function getHemocentroId(Request $request): ?int
    {
        $user     = Auth::user();
        $roleName = $user->getRoleNames()->first() ?? '';

        // Admin: pode ver tudo (null) ou filtrar via ?hemocentro_id=
        if ($roleName === 'admin' || $user->role_id == 4) {
            return $request->query('hemocentro_id')
                ? (int) $request->query('hemocentro_id')
                : null;
        }

        // Qualquer outro role: sempre restrito ao próprio hemocentro.
        // -1 garante query sem resultados se hemocentro_id não estiver preenchido.
        return $user->hemocentro_id ?? -1;
    }

    private function estoquesCriticos(?int $hemocentroId): array
    {
        $query = Estoque::with('hemocentro')
            ->whereColumn('quantidade', '<', 'quantidade_minima');

        if ($hemocentroId) {
            $query->where('hemocentro_id', $hemocentroId);
        }

        return $query->get()->map(fn ($e) => [
            'tipo_sangue'    => $e->tipo_sangue,
            'quantidade'     => (float)$e->quantidade,
            'quantidade_minima' => (float)$e->quantidade_minima,
            'hemocentro'     => $e->hemocentro?->nome,
            'deficit'        => round($e->quantidade_minima - $e->quantidade, 2),
        ])->values()->all();
    }

    private function performanceMensalQuery(?int $hemocentroId, int $meses = 12, ?array $filtros = null): array
    {
        $query = Doacao::query()
            ->selectRaw($this->monthExpression() . ' as mes, COUNT(*) as total, SUM(quantidade) as volume_ml')
            ->whereNotNull('data_hora_doacao')
            ->groupBy('mes')
            ->orderBy('mes');

        if ($filtros) {
            $this->applyPeriod($query, 'data_hora_doacao', $filtros);
            $this->applyBloodType($query, 'tipo_sangue', $filtros);
        } else {
            $query->where('data_hora_doacao', '>=', Carbon::now()->subMonths($meses - 1)->startOfMonth());
        }

        if ($hemocentroId) {
            $query->where('hemocentro_id', $hemocentroId);
        }

        $rows = $query->get()->keyBy('mes');

        // Preenche todos os meses mesmo sem doações
        $result = [];
        for ($i = $meses - 1; $i >= 0; $i--) {
            $mes = Carbon::now()->subMonths($i)->format('Y-m');
            $row = $rows[$mes] ?? null;
            $result[] = [
                'mes'       => $mes,
                'label'     => Carbon::createFromFormat('Y-m', $mes)->translatedFormat('M/y'),
                'total'     => $row ? (int)$row->total : 0,
                'volume_ml' => $row ? round((float)$row->volume_ml, 0) : 0,
            ];
        }

        return $result;
    }

    private function monthExpression(): string
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            return "strftime('%Y-%m', data_hora_doacao)";
        }
        
        if ($driver === 'pgsql') {
            return "TO_CHAR(data_hora_doacao, 'YYYY-MM')";
        }

        if ($driver === 'mysql') {
            return "DATE_FORMAT(data_hora_doacao, '%Y-%m')";
        }

        return "TO_CHAR(data_hora_doacao, 'YYYY-MM')";
    }
}