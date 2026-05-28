<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de Estoque — Doe Vida</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1F2937; background: #fff; }

        /* ── Cabeçalho ── */
        .header {
            background: linear-gradient(135deg, #1E3A5F 0%, #1D4ED8 60%, #2563EB 100%);
            color: white; padding: 18px 24px; margin-bottom: 16px;
        }
        .header-inner { display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 20px; font-weight: bold; letter-spacing: 0.5px; }
        .header .subtitle { font-size: 10px; opacity: 0.85; margin-top: 2px; }
        .header-meta { text-align: right; font-size: 9px; opacity: 0.9; }
        .header-meta strong { font-size: 11px; display: block; margin-bottom: 2px; }

        /* ── Alerta crítico ── */
        .alerta-box {
            margin: 0 20px 14px;
            background: #FEF2F2; border: 1px solid #FECACA;
            border-left: 4px solid #B91C1C; border-radius: 4px;
            padding: 10px 14px;
        }
        .alerta-box h3 { font-size: 10px; color: #991B1B; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .alerta-item { display: inline-block; margin: 2px 4px 2px 0; padding: 3px 8px; background: #FEE2E2; border-radius: 12px; font-size: 9px; color: #B91C1C; font-weight: bold; }
        .alerta-none { font-size: 9px; color: #059669; }

        /* ── KPIs ── */
        .kpi-row { display: flex; gap: 8px; margin: 0 20px 16px; }
        .kpi {
            flex: 1; background: #F9FAFB; border: 1px solid #E5E7EB;
            border-radius: 6px; padding: 10px 12px;
        }
        .kpi.vermelho { border-top: 3px solid #B91C1C; }
        .kpi.verde    { border-top: 3px solid #059669; }
        .kpi.azul     { border-top: 3px solid #2563EB; }
        .kpi.cinza    { border-top: 3px solid #6B7280; }
        .kpi-value { font-size: 20px; font-weight: bold; color: #111827; }
        .kpi-label { font-size: 8px; color: #6B7280; margin-top: 3px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* ── Seções ── */
        .section { margin: 0 20px 16px; }
        .section-title {
            font-size: 11px; font-weight: bold; color: #1E3A5F;
            text-transform: uppercase; letter-spacing: 0.6px;
            border-bottom: 2px solid #DBEAFE; padding-bottom: 4px; margin-bottom: 10px;
        }

        /* ── Gráfico de barras por tipo ── */
        .chart-area { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 6px; padding: 12px; }
        .bar-row { display: flex; align-items: center; gap: 6px; margin-bottom: 7px; }
        .bar-label { width: 30px; font-weight: bold; font-size: 9px; color: #374151; }
        .bar-track { flex: 1; background: #E5E7EB; border-radius: 3px; height: 14px; position: relative; overflow: hidden; }
        .bar-atual { height: 14px; border-radius: 3px; transition: width 0s; }
        .bar-min-line { position: absolute; top: 0; bottom: 0; width: 2px; background: #F59E0B; }
        .bar-count { width: 50px; text-align: right; font-size: 8px; color: #6B7280; }
        .bar-ok { background: #059669; }
        .bar-warn { background: #B91C1C; }
        .legend { font-size: 8px; color: #6B7280; margin-top: 8px; }
        .legend-dot { display: inline-block; width: 8px; height: 8px; border-radius: 2px; margin-right: 3px; vertical-align: middle; }

        /* ── Tabela ── */
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        thead tr { background: #1E3A5F; color: white; }
        thead th { padding: 7px 8px; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: 0.4px; }
        thead th.right { text-align: right; }
        thead th.center { text-align: center; }
        tbody tr:nth-child(even) { background: #F9FAFB; }
        tbody td { padding: 6px 8px; border-bottom: 1px solid #F3F4F6; }
        tbody td.right { text-align: right; }
        tbody td.center { text-align: center; }

        .status-critico { color: #B91C1C; font-weight: bold; font-size: 8px; }
        .status-estavel { color: #059669; font-weight: bold; font-size: 8px; }
        .status-atencao { color: #D97706; font-weight: bold; font-size: 8px; }

        .badge-tipo { display: inline-block; padding: 2px 6px; border-radius: 9px; background: #DBEAFE; color: #1D4ED8; font-size: 8px; font-weight: bold; }

        /* ── Barra de nível inline ── */
        .nivel-bar { display: inline-block; width: 60px; height: 8px; background: #E5E7EB; border-radius: 2px; vertical-align: middle; margin-left: 4px; }
        .nivel-fill { display: inline-block; height: 8px; border-radius: 2px; vertical-align: top; }

        /* ── Rodapé ── */
        .footer {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: #F9FAFB; border-top: 1px solid #E5E7EB;
            padding: 5px 20px; display: flex; justify-content: space-between;
            font-size: 8px; color: #9CA3AF;
        }

        .empty { text-align: center; padding: 30px; color: #9CA3AF; font-style: italic; }
    </style>
</head>
<body>

{{-- Cabeçalho --}}
<div class="header">
    <div class="header-inner">
        <div>
            <h1>Relatório de Estoque de Sangue</h1>
            <div class="subtitle">Doe Vida — Sistema de Gestão de Estoques</div>
        </div>
        <div class="header-meta">
            <strong>{{ $unidade }}</strong>
            Gerado em {{ $gerado_em }}
        </div>
    </div>
</div>

{{-- Alerta de estoque crítico --}}
<div class="alerta-box">
    @if($criticos->count() > 0)
    <h3>Alerta — {{ $criticos->count() }} Tipo(s) em Nível Crítico</h3>
    @foreach($criticos as $c)
    <span class="alerta-item">
        {{ $c->tipo_sangue }}
        ({{ number_format($c->quantidade, 1, ',', '.') }} / {{ number_format($c->quantidade_minima, 1, ',', '.') }} L)
    </span>
    @endforeach
    @else
    <h3>Status do Estoque</h3>
    <span class="alerta-none">Todos os tipos sanguíneos estão em nível estável.</span>
    @endif
</div>

{{-- KPIs --}}
<div class="kpi-row">
    <div class="kpi azul">
        <div class="kpi-value">{{ number_format($volume_global / 1000, 2, ',', '.') }} L</div>
        <div class="kpi-label">Volume Total em Estoque</div>
    </div>
    <div class="kpi vermelho">
        <div class="kpi-value">{{ $criticos->count() }}</div>
        <div class="kpi-label">Tipos em Nível Crítico</div>
    </div>
    <div class="kpi verde">
        <div class="kpi-value">{{ $estaveis->count() }}</div>
        <div class="kpi-label">Tipos em Nível Estável</div>
    </div>
    <div class="kpi cinza">
        <div class="kpi-value">{{ $estoques->pluck('hemocentro_id')->unique()->count() }}</div>
        <div class="kpi-label">Unidades Monitoradas</div>
    </div>
</div>

{{-- Gráfico: Nível por Tipo Sanguíneo --}}
<div class="section">
    <div class="section-title">Nível de Estoque por Tipo Sanguíneo</div>
    <div class="chart-area">
        @foreach($por_tipo as $tipo => $dados)
        @php
            $qtd    = $dados['qtd'];
            $min    = $dados['min'];
            $pctBar = $max_estoque > 0 ? min(100, round(($qtd / $max_estoque) * 100)) : 0;
            $pctMin = $max_estoque > 0 ? min(100, round(($min / $max_estoque) * 100)) : 0;
            $ok     = $qtd >= $min;
        @endphp
        <div class="bar-row">
            <span class="bar-label">{{ $tipo }}</span>
            <div class="bar-track">
                <div class="bar-atual {{ $ok ? 'bar-ok' : 'bar-warn' }}" style="width: {{ $pctBar }}%;"></div>
                @if($min > 0)
                <div class="bar-min-line" style="left: {{ $pctMin }}%;"></div>
                @endif
            </div>
            <span class="bar-count">
                {{ number_format($qtd, 1, ',', '.') }} L
                @if(!$ok)<span style="color:#B91C1C;"> !</span>@endif
            </span>
        </div>
        @endforeach
        <div class="legend">
            <span class="legend-dot" style="background:#059669;"></span> Estável
            &nbsp;&nbsp;
            <span class="legend-dot" style="background:#B91C1C;"></span> Crítico
            &nbsp;&nbsp;
            <span class="legend-dot" style="background:#F59E0B; width:2px; height:10px;"></span> Mínimo exigido
        </div>
    </div>
</div>

{{-- Tabela Detalhada --}}
<div class="section">
    <div class="section-title">Detalhamento por Unidade e Tipo Sanguíneo</div>
    @if($estoques->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Unidade</th>
                <th class="center">Tipo</th>
                <th class="right">Qtd. Atual (L)</th>
                <th class="right">Mínimo (L)</th>
                <th class="right">Déficit (L)</th>
                <th class="center">% do Mínimo</th>
                <th class="center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($estoques as $e)
            @php
                $pct    = $e->quantidade_minima > 0 ? min(999, round(($e->quantidade / $e->quantidade_minima) * 100)) : 100;
                $deficit = max(0, $e->quantidade_minima - $e->quantidade);
                if ($pct < 50)        { $status = 'CRÍTICO';  $cls = 'status-critico'; $barColor = '#B91C1C'; }
                elseif ($pct < 80)    { $status = 'ATENÇÃO';  $cls = 'status-atencao'; $barColor = '#D97706'; }
                else                  { $status = 'ESTÁVEL';  $cls = 'status-estavel'; $barColor = '#059669'; }
                $barPct = min(100, $pct);
            @endphp
            <tr>
                <td>{{ $e->hemocentro?->nome ?? 'N/D' }}</td>
                <td class="center"><span class="badge-tipo">{{ $e->tipo_sangue }}</span></td>
                <td class="right">{{ number_format($e->quantidade, 2, ',', '.') }}</td>
                <td class="right">{{ number_format($e->quantidade_minima, 2, ',', '.') }}</td>
                <td class="right" style="{{ $deficit > 0 ? 'color:#B91C1C; font-weight:bold;' : 'color:#6B7280;' }}">
                    {{ $deficit > 0 ? number_format($deficit, 2, ',', '.') : '—' }}
                </td>
                <td class="center">
                    <span class="nivel-bar">
                        <span class="nivel-fill" style="width:{{ $barPct }}%; background:{{ $barColor }};"></span>
                    </span>
                    {{ $pct }}%
                </td>
                <td class="center"><span class="{{ $cls }}">{{ $status }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="empty">Nenhum dado de estoque disponível.</div>
    @endif
</div>

{{-- Rodapé --}}
<div class="footer">
    <span>Doe Vida &copy; {{ date('Y') }} — Documento Gerado Automaticamente</span>
    <span>Relatório Confidencial — Uso Interno</span>
    <span>{{ $gerado_em }}</span>
</div>

</body>
</html>
