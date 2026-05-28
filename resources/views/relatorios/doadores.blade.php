<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de Doadores — Doe Vida</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1F2937; background: #fff; }

        /* ── Cabeçalho ── */
        .header {
            background: linear-gradient(135deg, #064E3B 0%, #047857 50%, #059669 100%);
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
            border-radius: 6px; padding: 10px 12px;
        }
        .kpi.verde    { border-top: 3px solid #059669; }
        .kpi.azul     { border-top: 3px solid #2563EB; }
        .kpi.vermelho { border-top: 3px solid #B91C1C; }
        .kpi.laranja  { border-top: 3px solid #D97706; }
        .kpi-value { font-size: 20px; font-weight: bold; color: #111827; }
        .kpi-label { font-size: 8px; color: #6B7280; margin-top: 3px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* ── Seções ── */
        .section { margin: 0 20px 16px; }
        .section-title {
            font-size: 11px; font-weight: bold; color: #064E3B;
            text-transform: uppercase; letter-spacing: 0.6px;
            border-bottom: 2px solid #A7F3D0; padding-bottom: 4px; margin-bottom: 10px;
        }

        /* ── Layout ── */
        .two-col { display: flex; gap: 12px; margin: 0 20px 16px; }
        .col { flex: 1; }
        .chart-area { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 6px; padding: 12px; }

        /* ── Barras horizontais ── */
        .bar-row { display: flex; align-items: center; gap: 6px; margin-bottom: 5px; }
        .bar-label { width: 30px; font-weight: bold; font-size: 9px; color: #374151; }
        .bar-label-wide { width: 50px; font-size: 9px; color: #374151; }
        .bar-track { flex: 1; background: #E5E7EB; border-radius: 3px; height: 13px; }
        .bar-fill-green { height: 13px; border-radius: 3px; background: #059669; }
        .bar-fill-blue  { height: 13px; border-radius: 3px; background: #2563EB; }
        .bar-count { width: 35px; text-align: right; font-size: 9px; color: #6B7280; }

        /* ── Tabela ── */
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        thead tr { background: #064E3B; color: white; }
        thead th { padding: 7px 8px; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: 0.4px; }
        thead th.right { text-align: right; }
        thead th.center { text-align: center; }
        tbody tr:nth-child(even) { background: #F9FAFB; }
        tbody td { padding: 6px 8px; border-bottom: 1px solid #F3F4F6; }
        tbody td.center { text-align: center; }
        tbody td.right { text-align: right; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 9px; font-size: 8px; font-weight: bold; }
        .badge-tipo { background: #DBEAFE; color: #1D4ED8; }
        .badge-ativo { background: #D1FAE5; color: #065F46; }
        .badge-inativo { background: #F3F4F6; color: #6B7280; }
        .badge-m { background: #EDE9FE; color: #5B21B6; }
        .badge-f { background: #FCE7F3; color: #9D174D; }

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
            <h1>Relatório de Doadores Cadastrados</h1>
            <div class="subtitle">Doe Vida — Sistema de Gestão de Doações de Sangue</div>
        </div>
        <div class="header-meta">
            <strong>{{ $unidade }}</strong>
            Total: {{ count($doadores) }} doadores<br>
            Gerado em {{ $gerado_em }}
        </div>
    </div>
</div>

{{-- KPIs --}}
@php
    $ativos   = $doadores->where('status', true)->count();
    $inativos = $doadores->where('status', false)->count();
    $semTipo  = $doadores->whereNull('tipo_sang')->count();
    $tipoTop  = collect($dist_tipo)->sortDesc()->keys()->first();
@endphp
<div class="kpi-row">
    <div class="kpi verde">
        <div class="kpi-value">{{ number_format(count($doadores), 0, ',', '.') }}</div>
        <div class="kpi-label">Total de Doadores</div>
    </div>
    <div class="kpi azul">
        <div class="kpi-value">{{ $ativos }}</div>
        <div class="kpi-label">Doadores Ativos</div>
    </div>
    <div class="kpi laranja">
        <div class="kpi-value">{{ $inativos }}</div>
        <div class="kpi-label">Doadores Inativos</div>
    </div>
    <div class="kpi vermelho">
        <div class="kpi-value">{{ $tipoTop ?? '—' }}</div>
        <div class="kpi-label">Tipo Sanguíneo Mais Comum</div>
    </div>
</div>

{{-- Gráficos: Tipo sanguíneo + Faixa etária --}}
<div class="two-col">

    {{-- Distribuição por tipo sanguíneo --}}
    <div class="col">
        <div class="section-title">Por Tipo Sanguíneo</div>
        <div class="chart-area">
            @foreach($dist_tipo as $tipo => $count)
            @php $pct = $max_dist > 0 ? round(($count / $max_dist) * 100) : 0; @endphp
            <div class="bar-row">
                <span class="bar-label">{{ $tipo }}</span>
                <div class="bar-track">
                    <div class="bar-fill-green" style="width: {{ $pct }}%;"></div>
                </div>
                <span class="bar-count">{{ $count }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Faixa etária --}}
    <div class="col">
        <div class="section-title">Por Faixa Etária</div>
        <div class="chart-area">
            @foreach($faixas as $faixa => $count)
            @php $pct = $max_faixa > 0 ? round(($count / $max_faixa) * 100) : 0; @endphp
            <div class="bar-row">
                <span class="bar-label-wide">{{ $faixa }}</span>
                <div class="bar-track">
                    <div class="bar-fill-blue" style="width: {{ $pct }}%;"></div>
                </div>
                <span class="bar-count">{{ $count }}</span>
            </div>
            @endforeach

            {{-- Distribuição por sexo --}}
            <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #E5E7EB;">
                <div style="font-size:9px; font-weight:bold; color:#374151; margin-bottom:4px;">Por Sexo</div>
                @foreach($dist_sexo as $sexo => $count)
                @php
                    $totalSexo = array_sum($dist_sexo) ?: 1;
                    $pctSexo   = round(($count / $totalSexo) * 100);
                    $label     = match(strtoupper($sexo ?? '')) { 'M' => 'Masculino', 'F' => 'Feminino', default => 'Outros' };
                @endphp
                <div class="bar-row">
                    <span class="bar-label-wide">{{ $label }}</span>
                    <div class="bar-track">
                        <div class="{{ strtoupper($sexo) === 'M' ? 'bar-fill-blue' : 'bar-fill-green' }}" style="width: {{ $pctSexo }}%;"></div>
                    </div>
                    <span class="bar-count">{{ $count }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Tabela de Doadores --}}
<div class="section">
    <div class="section-title">Lista Completa de Doadores</div>
    @if($doadores->count() > 0)
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nome</th>
                <th>CPF</th>
                <th>E-mail</th>
                <th class="center">Tipo</th>
                <th class="center">Sexo</th>
                <th>Telefone</th>
                <th>Cidade/UF</th>
                <th class="center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($doadores as $i => $u)
            <tr>
                <td class="center" style="color:#9CA3AF;">{{ $i + 1 }}</td>
                <td>{{ $u->name }}</td>
                <td style="font-size:8px; color:#6B7280;">{{ $u->cpf ? substr($u->cpf, 0, 3) . '.***.***-**' : '—' }}</td>
                <td style="font-size:8px;">{{ $u->email }}</td>
                <td class="center">
                    @if($u->tipo_sang)
                    <span class="badge badge-tipo">{{ $u->tipo_sang }}</span>
                    @else
                    <span style="color:#9CA3AF;">—</span>
                    @endif
                </td>
                <td class="center">
                    @if($u->sexo)
                    <span class="badge {{ strtoupper($u->sexo) === 'M' ? 'badge-m' : 'badge-f' }}">
                        {{ strtoupper($u->sexo) === 'M' ? 'M' : 'F' }}
                    </span>
                    @else —
                    @endif
                </td>
                <td>{{ $u->telefone ?? '—' }}</td>
                <td>{{ $u->cidade ? $u->cidade . '/' . $u->uf : '—' }}</td>
                <td class="center">
                    <span class="badge {{ $u->status ? 'badge-ativo' : 'badge-inativo' }}">
                        {{ $u->status ? 'Ativo' : 'Inativo' }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top:6px; text-align:right; font-size:9px; color:#6B7280;">
        <strong>Total:</strong> {{ $doadores->count() }} doadores
        &nbsp;|&nbsp;
        <strong>Ativos:</strong> {{ $ativos }}
        &nbsp;|&nbsp;
        <strong>Inativos:</strong> {{ $inativos }}
    </div>
    @else
    <div class="empty">Nenhum doador encontrado para o filtro selecionado.</div>
    @endif
</div>

{{-- Rodapé --}}
<div class="footer">
    <span>Doe Vida &copy; {{ date('Y') }} — Documento Gerado Automaticamente</span>
    <span>Relatório Confidencial — Dados protegidos por LGPD</span>
    <span>{{ $gerado_em }}</span>
</div>

</body>
</html>
