@php
$bordereau = $bordereau ?? null;
$contracts = $contracts ?? collect();
if (!$bordereau) return;
$company = $bordereau->company;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau {{ $bordereau->reference }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #000; line-height: 1.35; }
        @page { size: A4; margin: 15mm; }
        .header { border-bottom: 2px solid #1e293b; padding-bottom: 8px; margin-bottom: 12px; }
        .header h1 { font-size: 14px; color: #1e293b; }
        .meta { margin-bottom: 12px; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 4px 8px 4px 0; vertical-align: top; }
        .meta .label { color: #64748b; width: 140px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        table.data th { background: #f1f5f9; font-weight: 600; }
        .totals { margin-top: 12px; text-align: right; }
        .totals .row { padding: 4px 0; }
        .totals .label { display: inline-block; width: 160px; text-align: right; margin-right: 12px; }
        .totals .value { font-weight: 600; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Bordereau {{ $bordereau->reference }}</h1>
    </div>
    <div class="meta">
        <table>
            <tr><td class="label">Compagnie</td><td>{{ $company?->name ?? '—' }}{{ $company?->code ? ' (' . $company->code . ')' : '' }}</td></tr>
            <tr><td class="label">Période</td><td>{{ $bordereau->period_start?->format('d/m/Y') }} → {{ $bordereau->period_end?->format('d/m/Y') }}</td></tr>
            <tr><td class="label">Statut</td><td>{{ $bordereau->status === 'draft' ? 'Brouillon' : ($bordereau->status === 'validated' ? 'Validé' : $bordereau->status) }}</td></tr>
        </table>
    </div>
    <table class="data">
        <thead>
            <tr>
                <th>Date début</th>
                <th>Date fin</th>
                <th>Client</th>
                <th>Véhicule</th>
                <th>Montant (F CFA)</th>
                <th>N° attestation</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contracts as $c)
            <tr>
                <td>{{ $c->start_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $c->end_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $c->client?->full_name ?? '—' }}</td>
                <td>{{ $c->vehicle ? trim(($c->vehicle->brand?->name ?? '').' '.($c->vehicle->model?->name ?? '').' '.($c->vehicle->registration_number ?? '')) : '—' }}</td>
                <td>{{ number_format($c->total_amount ?? 0, 0, ',', ' ') }}</td>
                <td>{{ $c->attestation_number ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="totals">
        <div class="row"><span class="label">Montant total (F CFA) :</span><span class="value">{{ number_format($bordereau->total_amount ?? 0, 0, ',', ' ') }}</span></div>
        <div class="row"><span class="label">Commission courtier :</span><span class="value">{{ number_format($bordereau->total_commission ?? 0, 0, ',', ' ') }} F CFA@if($bordereau->commission_pct !== null) ({{ number_format((float) $bordereau->commission_pct, 1, ',', ' ') }} %)@endif</span></div>
    </div>
</body>
</html>
