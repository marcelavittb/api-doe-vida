<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de Doações — Doe Vida</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1F2937; background: #fff; }

        /* ── Cabeçalho ── */
        .header {
            background: linear-gradient(135deg, #7F1D1D 0%, #B91C1C 50%, #DC2626 100%);
            color: white; padding: 18px 24px; margin-bottom: 16px;
        }
        .header-inner { display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 20px; font-weight: bold; letter-spacing: 0.5px; }
        .header .subtitle { font-size: 10px; opacity: 0.85; margin-top: 2px; }
        .header-meta { text-align: right; font-size: 9px; opacity: 0.9; }
        .header-meta strong { font-size: 11px; display: block; margin-bottom: 2px; }

        /* ── KPIs ── */
        .kpi-row { display: flex; gap: 8px; margin: 0 20px 16px; }
        .kpi {
            flex: 1; background: #F9FAFB; border: 1px solid #E5E7EB;
            border-radius: 6px; padding: 10px 12px; border-top: 3px solid #B91C1C;
        }
        .kpi.verde { border-top-color: #059669; }
        .kpi.azul  { border-top-color: #2563EB; }
        .kpi.laranja { border-top-color: #D97706; }
        .kpi-value { font-size: 20px; font-weight: bold; color: #111827; line-height: 1.2; }
        .kpi-label { font-size: 8px; color: #6B7280; margin-top: 3px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* ── Seções ── */
        .section { margin: 0 20px 16px; }
        .section-title {
            font-size: 11px; font-weight: bold; color: #7F1D1D;
            text-transform: uppercase; letter-spacing: 0.6px;
            border-bottom: 2px solid #FECACA; padding-bottom: 4px; margin-bottom: 10px;
        }

        /* ── Gráfico de barras SVG ── */
        .chart-area { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 6px; padding: 12px; }
        .chart-label { font-size: 8px; color: #6B7280; text-anchor: middle; }
        .bar-value { font-size: 7px; fill: #374151; text-anchor: middle; }
        .bar-fill { fill: #B91C1C; }
        .bar-fill-low { fill: #FCA5A5; }
        .chart-grid { stroke: #E5E7EB; stroke-width: 0.5; stroke-dasharray: 2,2; }

        /* ── Tabela ── */
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        thead tr { background: #1E3A5F; color: white; }
        thead th { padding: 7px 8px; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: 0.4px; }
        thead th.right { text-align: right; }
        tbody tr:nth-child(even) { background: #F9FAFB; }
        tbody tr:hover { background: #FEF2F2; }
        tbody td { padding: 6px 8px; border-bottom: 1px solid #F3F4F6; }
        tbody td.right { text-align: right; }
        tbody td.center { text-align: center; }
        .badge {
            display: inline-block; padding: 2px 6px; border-radius: 9px;
            font-size: 8px; font-weight: bold;
        }
        .badge-tipo { background: #DBEAFE; color: #1D4ED8; }
        .badge-ap   { background: #D1FAE5; color: #065F46; }
        .badge-na   { background: #FEF3C7; color: #92400E; }

        /* ── Layout 2 colunas ── */
        .two-col { display: flex; gap: 12px; margin: 0 20px 16px; }
        .col { flex: 1; }

        /* ── Resumo por tipo ── */
        .tipo-row { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
        .tipo-label { width: 28px; font-weight: bold; font-size: 9px; color: #374151; }
        .tipo-bar-bg { flex: 1; background: #F3F4F6; border-radius: 3px; height: 12px; position: relative; }
        .tipo-bar-fill { height: 12px; border-radius: 3px; background: #B91C1C; }
        .tipo-count { width: 30px; text-align: right; font-size: 9px; color: #6B7280; }

        /* ── Rodapé ── */
        .footer {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: #F9FAFB; border-top: 1px solid #E5E7EB;
            padding: 5px 20px; display: flex; justify-content: space-between;
            font-size: 8px; color: #9CA3AF;
        }

        /* ── Sem dados ── */
        .empty { text-align: center; padding: 30px; color: #9CA3AF; font-style: italic; }
    </style>
</head>
<body>

{{-- Cabeçalho --}}
<div class="header">
    <div class="header-inner">
        <div>
            <h1>Relatório de Doações de Sangue</h1>
            <div class="subtitle">Doe Vida — Sistema de Gestão de Doações</div>
        </div>
        <div class="header-meta">
            <strong>{{ $unidade }}</strong>
            Período: últimos {{ $periodo }} dias<br>
            Gerado em {{ $gerado_em }}
        </div>
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-row">
    <div class="kpi">
        <div class="kpi-value">{{ number_format($doacoes->count(), 0, ',', '.') }}</div>
        <div class="kpi-label">Total de Doações</div>
    </div>
    <div class="kpi verde">
        <div class="kpi-value">{{ number_format($volume_total / 1000, 2, ',', '.') }} L</div>
        <div class="kpi-label">Volume Total Coletado</div>
    </div>
    <div class="kpi azul">
        <div class="kpi-value">{{ number_format($media_vol, 0, ',', '.') }} mL</div>
        <div class="kpi-label">Volume Médio/Doação</div>
    </div>
    <div class="kpi laranja">
        <div class="kpi-value">{{ $doacoes->groupBy('user_id')->count() }}</div>
        <div class="kpi-label">Doadores Únicos</div>
    </div>
    <div class="kpi">
        @php $tipoTop = collect($dist_tipo)->sortDesc()->keys()->first() @endphp
        <div class="kpi-value">{{ $tipoTop ?? '—' }}</div>
        <div class="kpi-label">Tipo Mais Doado</div>
    </div>
</div>

{{-- Distribuição por Tipo + Gráfico Mensal --}}
<div class="two-col">

    {{-- Distribuição por Tipo Sanguíneo --}}
    <div class="col">
        <div class="section-title">Distribuição por Tipo Sanguíneo</div>
        <div class="chart-area">
            @foreach($dist_tipo as $tipo => $count)
            @php $pct = $max_dist_tipo > 0 ? round(($count / $max_dist_tipo) * 100) : 0; @endphp
            <div class="tipo-row">
                <span class="tipo-label">{{ $tipo }}</span>
                <div class="tipo-bar-bg">
                    <div class="tipo-bar-fill" style="width: {{ $pct }}%;"></div>
                </div>
                <span class="tipo-count">{{ $count }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Gráfico de doações por dia (SVG sparkline) --}}
    <div class="col">
        <div class="section-title">Doações por Dia (Período)</div>
        <div class="chart-area">
            @if($por_dia->count() > 0)
            @php
                $maxDia = $por_dia->max() ?: 1;
                $dias   = $por_dia->values()->toArray();
                $n      = count($dias);
                $w      = 320; $h = 80; $pad = 6;
                $barW   = ($n > 0) ? floor(($w - $pad*2) / $n) - 1 : 10;
            @endphp
            <svg width="{{ $w }}" height="{{ $h + 20 }}" xmlns="http://www.w3.org/2000/svg">
                {{-- Linhas de grade --}}
                @for ($i = 0; $i <= 4; $i++)
                @php $y = $pad + ($h - $pad*2) * $i / 4; @endphp
                <line x1="{{ $pad }}" y1="{{ $y }}" x2="{{ $w - $pad }}" y2="{{ $y }}" class="chart-grid"/>
                @endfor
                {{-- Barras --}}
                @foreach($dias as $idx => $val)
                @php
                    $barH  = $maxDia > 0 ? (int)(($val / $maxDia) * ($h - $pad*2)) : 0;
                    $x     = $pad + $idx * (($w - $pad*2) / $n);
                    $y     = $h - $pad - $barH;
                    $color = $val === $maxDia ? '#991B1B' : '#B91C1C';
                @endphp
                <rect x="{{ $x + 1 }}" y="{{ $y }}" width="{{ max(1, $barW) }}" height="{{ $barH }}"
                      fill="{{ $color }}" rx="1"/>
                @if($val > 0)
                <text x="{{ $x + $barW/2 + 1 }}" y="{{ $y - 2 }}" class="bar-value">{{ $val }}</text>
                @endif
                @endforeach
            </svg>
            <div style="font-size:8px; color:#9CA3AF; margin-top:2px;">
                {{ $por_dia->keys()->first() }} → {{ $por_dia->keys()->last() }}
            </div>
            @else
            <div class="empty">Sem dados no período</div>
            @endif
        </div>
    </div>
</div>

{{-- Tabela de Doações --}}
<div class="section">
    <div class="section-title">Registro Detalhado de Doações</div>
    @if($doacoes->count() > 0)
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Data / Hora</th>
                <th>Doador</th>
                <th>Tipo</th>
                <th class="right">Volume (mL)</th>
                <th>Unidade</th>
                <th>Responsável</th>
                <th>Validade Bolsa</th>
            </tr>
        </thead>
        <tbody>
            @foreach($doacoes as $i => $d)
            <tr>
                <td class="center" style="color:#9CA3AF;">{{ $i + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($d->data_hora_doacao)->format('d/m/Y H:i') }}</td>
                <td>{{ $d->doador?->name ?? '—' }}</td>
                <td class="center">
                    <span class="badge badge-tipo">{{ $d->tipo_sangue }}</span>
                </td>
                <td class="right">{{ number_format($d->quantidade, 0, ',', '.') }}</td>
                <td>{{ $d->hemocentro?->nome ?? '—' }}</td>
                <td>{{ $d->funcionario?->name ?? '—' }}</td>
                <td>{{ $d->data_validade_sangue ? \Carbon\Carbon::parse($d->data_validade_sangue)->format('d/m/Y') : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:6px; text-align:right; font-size:9px; color:#6B7280;">
        <strong>Volume Total:</strong> {{ number_format($volume_total, 0, ',', '.') }} mL
        &nbsp;|&nbsp;
        <strong>Total de Registros:</strong> {{ $doacoes->count() }}
    </div>
    @else
    <div class="empty">Nenhuma doação registrada no período selecionado.</div>
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
