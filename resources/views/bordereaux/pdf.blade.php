@php
$bordereau = $bordereau ?? null;
$contracts = $contracts ?? collect();
if (!$bordereau) return;
$company = $bordereau->company;
$commissionPct = $bordereau->commission_pct !== null ? (float) $bordereau->commission_pct : null;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau {{ $bordereau->reference }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #000; line-height: 1.25; }
        @page { size: A4 landscape; margin: 10mm; }
        .header { border-bottom: 2px solid #1e293b; padding-bottom: 6px; margin-bottom: 8px; }
        .header h1 { font-size: 12px; color: #1e293b; }
        .meta { margin-bottom: 8px; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 2px 6px 2px 0; vertical-align: top; }
        .meta .label { color: #64748b; width: 120px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 7px; }
        table.data th, table.data td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: left; }
        table.data th { background: #1e3a5f; color: #fff; font-weight: 600; }
        table.data td { word-wrap: break-word; }
        .totals { margin-top: 10px; text-align: right; font-size: 9px; }
        .totals .row { padding: 2px 0; }
        .totals .label { display: inline-block; width: 220px; text-align: right; margin-right: 10px; }
        .totals .value { font-weight: 600; }
    </style>
</head>
<body>
    <div class="header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <h1 style="margin: 0;">BORDEREAU DE PRODUCTION ET DE REVERSEMENT DE PRIMES</h1>
        <span style="font-weight: 700; font-size: 12px;">BORD. N° {{ $bordereau->reference }}</span>
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
                <th>N°</th>
                <th>N° attestation</th>
                <th>Police/Assuré</th>
                <th>Nom assuré</th>
                <th>Adresse</th>
                <th>Tél</th>
                <th>Email</th>
                <th>Date effet</th>
                <th>Date échéance</th>
                <th>N° carte grise</th>
                <th>Marque</th>
                <th>Modèle</th>
                <th>Type</th>
                <th>Énergie</th>
                <th>Immat</th>
                <th>Pl</th>
                <th>Prime nette</th>
                <th>Access.</th>
                <th>Taxe</th>
                <th>Prime TTC</th>
                <th>Taux %</th>
                <th>Commission</th>
                <th>Primes à reverser</th>
                <th>Montant encaissé</th>
            </tr>
        </thead>
        <tbody>
            @php
                $sumPrimeNette = 0;
                $sumAccessory = 0;
                $sumTaxe = 0;
                $sumPrimeTtc = 0;
                $sumCommission = 0;
                $sumPrimeAReverser = 0;
            @endphp
            @foreach($contracts as $i => $c)
            @php
                $primeNette = (int) ($c->prime_nette_for_commission ?? 0);
                $primeTtc = (int) ($c->total_amount ?? 0);
                $commission = $commissionPct !== null && $primeNette > 0 ? (int) round($primeNette * ($commissionPct / 100)) : 0;
                $primeAReverser = max(0, $primeTtc - $commission);
                $accessory = (int) ($c->accessory_amount ?? 0);
                $taxe = (int) ($c->taxes_amount ?? 0);
                $sumPrimeNette += $primeNette;
                $sumAccessory += $accessory;
                $sumTaxe += $taxe;
                $sumPrimeTtc += $primeTtc;
                $sumCommission += $commission;
                $sumPrimeAReverser += $primeAReverser;
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $c->attestation_number ?? '—' }}</td>
                <td>{{ $c->policy_number ?? $c->reference ?? '—' }}</td>
                <td>{{ $c->client?->full_name ?? '—' }}</td>
                <td>{{ Str::limit($c->client?->address ?? '—', 25) }}</td>
                <td>{{ $c->client?->phone ?? '—' }}</td>
                <td>{{ Str::limit($c->client?->email ?? '—', 20) }}</td>
                <td>{{ $c->start_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $c->end_date?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $c->vehicle?->registration_card_number ?? '—' }}</td>
                <td>{{ $c->vehicle?->brand?->name ?? '—' }}</td>
                <td>{{ $c->vehicle?->model?->name ?? '—' }}</td>
                <td>@php
                $t = $c->contract_type ?? $c->vehicle?->pricing_type ?? '';
                echo match($t) { 'VP' => 'VP', 'TPC' => 'TPC', 'TPM' => 'TPM', 'TWO_WHEELER' => '2 roues', default => $t ?: '—' };
            @endphp</td>
                <td>{{ $c->vehicle?->energySource?->name ?? '—' }}</td>
                <td>{{ $c->vehicle?->registration_number ?? '—' }}</td>
                <td>{{ $c->vehicle?->seat_count ?? '—' }}</td>
                <td>{{ number_format($primeNette, 0, ',', ' ') }}</td>
                <td>{{ number_format($accessory, 0, ',', ' ') }}</td>
                <td>{{ number_format($taxe, 0, ',', ' ') }}</td>
                <td>{{ number_format($primeTtc, 0, ',', ' ') }}</td>
                <td>{{ $commissionPct !== null ? number_format($commissionPct, 1, ',', ' ') : '—' }}</td>
                <td>{{ number_format($commission, 0, ',', ' ') }}</td>
                <td>{{ number_format($primeAReverser, 0, ',', ' ') }}</td>
                <td>{{ number_format($primeAReverser, 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f1f5f9; font-weight: 700;">
                <td colspan="16" style="text-align: right; padding: 6px 8px;">TOTAL</td>
                <td style="text-align: right;">{{ number_format($sumPrimeNette, 0, ',', ' ') }}</td>
                <td style="text-align: right;">{{ number_format($sumAccessory, 0, ',', ' ') }}</td>
                <td style="text-align: right;">{{ number_format($sumTaxe, 0, ',', ' ') }}</td>
                <td style="text-align: right;">{{ number_format($sumPrimeTtc, 0, ',', ' ') }}</td>
                <td></td>
                <td style="text-align: right;">{{ number_format($sumCommission, 0, ',', ' ') }}</td>
                <td style="text-align: right;">{{ number_format($sumPrimeAReverser, 0, ',', ' ') }}</td>
                <td style="text-align: right;">{{ number_format($sumPrimeAReverser, 0, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>
    <div class="totals">
        <div class="row"><span class="label">Montant total – Prime TTC (F CFA) :</span><span class="value">{{ number_format($bordereau->total_amount ?? 0, 0, ',', ' ') }}</span></div>
        <div class="row"><span class="label">Commission courtier :</span><span class="value">
            {{ number_format($bordereau->total_commission ?? 0, 0, ',', ' ') }} F CFA
            @if($bordereau->commission_pct !== null)
                ({{ number_format((float) $bordereau->commission_pct, 1, ',', ' ') }} % sur prime nette)
            @endif
        </span></div>
        @php
            $primeTtc = (float) ($bordereau->total_amount ?? 0);
            $commission = (float) ($bordereau->total_commission ?? 0);
            $primeAReverser = $primeTtc - $commission;
        @endphp
        <div class="row"><span class="label">Prime à reverser / Prime effectivement encaissée (F CFA) :</span><span class="value">{{ number_format((int) $primeAReverser, 0, ',', ' ') }}</span></div>
    </div>
</body>
</html>
