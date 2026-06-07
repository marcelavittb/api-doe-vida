<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Doacao;
use App\Models\Estoque;
use App\Models\Hemocentro;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstatisticaController extends Controller
{
    private const TIPOS_SANGUINEOS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public function funcionario(Request $request)
    {
        $hemocentroId = $this->hemocentroIdDoUsuario($request);

        if (!$hemocentroId) {
            return response()->json([
                'message' => 'Usuario nao possui hemocentro vinculado.',
            ], 422);
        }

        return response()->json($this->estatisticasHemocentro($hemocentroId));
    }

    public function diretor(Request $request)
    {
        $hemocentroId = $this->hemocentroIdDoUsuario($request);

        if (!$hemocentroId) {
            return response()->json([
                'message' => 'Usuario nao possui hemocentro vinculado.',
            ], 422);
        }

        $stats = $this->estatisticasHemocentro($hemocentroId);
        
        // Cálculos adicionais para o Diretor
        $hoje = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();
        $inicioMesAnterior = Carbon::now()->subMonth()->startOfMonth();
        $fimMesAnterior = Carbon::now()->subMonth()->endOfMonth();

        // Crescimento
        $doacoesMesAnterior = Doacao::where('hemocentro_id', $hemocentroId)
            ->whereBetween('data_hora_doacao', [$inicioMesAnterior, $fimMesAnterior])
            ->count();
        
        $crescimentoMes = $doacoesMesAnterior > 0 
            ? (($stats['doacoes_mes'] - $doacoesMesAnterior) / $doacoesMesAnterior) * 100 
            : ($stats['doacoes_mes'] > 0 ? 100 : 0);

        // Taxa de comparecimento
        $taxaComparecimento = $stats['agendamentos_hoje'] > 0 
            ? ($stats['confirmados_hoje'] / $stats['agendamentos_hoje']) * 100 
            : 0;

        // Média diária (baseada em dias passados no mês atual)
        $diasPassados = Carbon::now()->day;
        $mediaDiaria = $stats['doacoes_mes'] / ($diasPassados ?: 1);

        return response()->json([
            'doacoes_mes' => $stats['doacoes_mes'],
            'crescimento_mes' => round($crescimentoMes, 1),
            'agendamentos_hoje' => $stats['agendamentos_hoje'],
            'confirmados_hoje' => $stats['confirmados_hoje'],
            'taxa_comparecimento' => round($taxaComparecimento, 1),
            'media_diaria' => round($mediaDiaria, 1),
            'satisfacao' => null, // De molho conforme solicitado
            'estoque_critico' => $stats['estoque_critico'],
            'doacoes_por_mes' => $this->doacoesPorMes($hemocentroId),
            'doacoes_por_tipo' => $this->doacoesPorTipo($hemocentroId),
        ]);
    }

    public function admin()
    {
        $totalAgendamentos = Agendamento::whereIn('status_agendamento', ['AGE', 'CON'])->count();
        $totalCompareceram = Agendamento::where('status_agendamento', 'CON')->count()
            + Doacao::distinct('agendamento_id')->count('agendamento_id');

        $taxaComparecimento = $totalAgendamentos > 0
            ? round(($totalCompareceram / $totalAgendamentos) * 100)
            : 0;

        return response()->json([
            'total_hemocentros'   => Hemocentro::count(),
            'total_usuarios'      => User::count(),
            'taxa_comparecimento' => $taxaComparecimento,
            'doacoes_por_hemocentro' => $this->doacoesPorHemocentro(),
            'estoque_global'      => $this->estoqueGlobal(),
            'doacoes_por_mes'     => $this->doacoesPorMes(),
            'doacoes_por_tipo'    => $this->doacoesPorTipo(),
        ]);
    }

    private function estatisticasHemocentro(int $hemocentroId): array
    {
        $hoje = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();
        $fimMes = Carbon::now()->endOfMonth();

        return [
            'agendamentos_hoje' => Agendamento::where('hemocentro_id', $hemocentroId)
                ->whereDate('data_hora_doacao', $hoje)
                ->count(),
            'confirmados_hoje' => Agendamento::where('hemocentro_id', $hemocentroId)
                ->whereDate('data_hora_doacao', $hoje)
                ->where('status_agendamento', 'CON')
                ->count(),
            'doacoes_mes' => Doacao::where('hemocentro_id', $hemocentroId)
                ->whereBetween('data_hora_doacao', [$inicioMes, $fimMes])
                ->count(),
            'estoque_critico' => Estoque::where('hemocentro_id', $hemocentroId)
                ->whereColumn('quantidade', '<', 'quantidade_minima')
                ->orderBy('tipo_sangue')
                ->pluck('tipo_sangue')
                ->values(),
            'agendamentos_semana' => $this->agendamentosSemana($hemocentroId),
        ];
    }

    private function agendamentosSemana(?int $hemocentroId = null): array
    {
        $inicioSemana = Carbon::now()->startOfWeek();
        $fimSemana = Carbon::now()->endOfWeek();

        $query = Agendamento::query()
            ->selectRaw('DATE(data_hora_doacao) as data, COUNT(*) as total')
            ->whereBetween('data_hora_doacao', [$inicioSemana, $fimSemana])
            ->groupByRaw('DATE(data_hora_doacao)');

        if ($hemocentroId) {
            $query->where('hemocentro_id', $hemocentroId);
        }

        $totaisPorData = $query->pluck('total', 'data');
        $dias = ['seg' => 0, 'ter' => 0, 'qua' => 0, 'qui' => 0, 'sex' => 0, 'sab' => 0, 'dom' => 0];
        $labels = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];

        foreach (range(0, 6) as $indice) {
            $data = $inicioSemana->copy()->addDays($indice)->toDateString();
            $dias[$labels[$indice]] = (int) ($totaisPorData[$data] ?? 0);
        }

        return $dias;
    }

    private function doacoesPorMes(?int $hemocentroId = null): array
    {
        $query = Doacao::query()
            ->selectRaw($this->monthExpression() . ' as mes, COUNT(*) as total')
            ->whereNotNull('data_hora_doacao')
            ->where('data_hora_doacao', '>=', Carbon::now()->subMonths(11)->startOfMonth())
            ->groupBy('mes')
            ->orderBy('mes');

        if ($hemocentroId) {
            $query->where('hemocentro_id', $hemocentroId);
        }

        return $query->get()
            ->map(fn ($item) => [
                'mes' => $item->mes,
                'total' => (int) $item->total,
            ])
            ->values()
            ->all();
    }

    private function doacoesPorTipo(?int $hemocentroId = null): array
    {
        $query = Doacao::query()
            ->select('tipo_sangue', DB::raw('COUNT(*) as total'))
            ->whereNotNull('tipo_sangue')
            ->groupBy('tipo_sangue');

        if ($hemocentroId) {
            $query->where('hemocentro_id', $hemocentroId);
        }

        $totais = array_fill_keys(self::TIPOS_SANGUINEOS, 0);

        foreach ($query->pluck('total', 'tipo_sangue') as $tipo => $total) {
            $totais[$tipo] = (int) $total;
        }

        return $totais;
    }

    private function totalDoadoresAtivos(int $hemocentroId): int
    {
        return User::where('role_id', $this->roleId('doador'))
            ->where('status', DB::raw('true'))
            ->whereHas('triagens', fn ($query) => $query->where('hemocentro_id', $hemocentroId))
            ->count();
    }

    private function doacoesPorHemocentro(): array
    {
        return Hemocentro::query()
            ->leftJoin('doacao', 'hemocentros.id', '=', 'doacao.hemocentro_id')
            ->select('hemocentros.id', 'hemocentros.nome', DB::raw('COUNT(doacao.id) as total'))
            ->groupBy('hemocentros.id', 'hemocentros.nome')
            ->orderBy('hemocentros.nome')
            ->get()
            ->map(fn ($item) => [
                'hemocentro_id' => (int) $item->id,
                'hemocentro' => $item->nome,
                'total' => (int) $item->total,
            ])
            ->values()
            ->all();
    }

    private function estoqueGlobal(): array
    {
        $totais = array_fill_keys(self::TIPOS_SANGUINEOS, 0.0);

        foreach (Estoque::select('tipo_sangue', DB::raw('SUM(quantidade) as total'))->groupBy('tipo_sangue')->pluck('total', 'tipo_sangue') as $tipo => $total) {
            $totais[$tipo] = (float) $total;
        }

        return $totais;
    }

    private function hemocentroIdDoUsuario(Request $request): ?int
    {
        return $request->user()?->hemocentro_id;
    }

    private function roleId(string $nome): ?int
    {
        return DB::table('roles')->where('name', $nome)->value('id');
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