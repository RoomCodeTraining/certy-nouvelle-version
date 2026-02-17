@php
$greenColor = '#219150';
$contract = $contract ?? null;
if (!$contract) {
    return;
}
$vehicle = $contract->vehicle;
$client = $contract->client;
$company = $contract->company;

function powerDisplay($vehicle) {
    if (!$vehicle) return 'N/A';
    $type = $vehicle->pricing_type ?? '';
    if ($type === 'VP') return ($vehicle->fiscal_power ?? '-') . ' CV';
    if (str_starts_with($type, 'TWO_')) return ($vehicle->engine_capacity ?? '-') . ' cm³';
    if (in_array($type, ['TPC', 'TPM'])) return ($vehicle->payload_capacity ?? '-') . ' tonne';
    return $vehicle->fiscal_power ?? '-';
}
function powerUnit($vehicle) {
    if (!$vehicle) return 'CV';
    $type = $vehicle->pricing_type ?? '';
    if ($type === 'VP') return 'CV';
    if (str_starts_with($type, 'TWO_')) return 'cm³';
    if (in_array($type, ['TPC', 'TPM'])) return 'tonne';
    return 'CV';
}
$issueDate = $contract->start_date ?? now();

function contractTypeLabel($type) {
    $labels = ['VP' => 'VP', 'TPC' => 'Transport pour propre compte', 'TPM' => 'TPM', 'TWO_WHEELER' => 'Deux roues'];
    return $type ? ($labels[$type] ?? $type) : 'N/A';
}
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contrat d'assurance</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #000;
            background: #fff;
            line-height: 1.25;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        @page { size: A4; margin: 12mm; }
        @media print {
            body { margin: 0; padding: 0; }
            .page-container { page-break-inside: avoid; }
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 4rem;
            font-weight: bold;
            color: {{ $greenColor }};
            opacity: 0.06;
            z-index: 0;
            pointer-events: none;
            white-space: nowrap;
            letter-spacing: 0.1em;
        }
        .container { padding: 6px 12px 8px; max-width: 100%; position: relative; z-index: 1; }
        .header {
            border-bottom: 1px solid {{ $greenColor }};
            margin-bottom: 4px;
            padding-bottom: 4px;
        }
        .header-top { display: table; width: 100%; }
        .header-top > div { display: table-cell; vertical-align: middle; }
        .header-logo { width: 15%; text-align: center; }
        .header-logo img { height: 52px; max-width: 90px; object-fit: contain; }
        .header-title { width: 70%; text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; color: {{ $greenColor }}; }
        .header-compagnie { width: 15%; text-align: right; }
        .header-compagnie img { height: 40px; max-width: 80px; object-fit: contain; }
        .header-meta { display: table; width: 100%; margin-top: 2px; font-size: 9px; }
        .header-meta > div { display: table-cell; vertical-align: top; }
        .header-meta .left { text-align: left; width: 50%; }
        .header-meta .right { text-align: right; width: 50%; }
        .header-meta span.fw { font-weight: 600; }
        .main-title { text-align: center; margin-bottom: 4px; }
        .main-title h2 { font-size: 11px; font-weight: bold; text-transform: uppercase; color: {{ $greenColor }}; }
        .main-title .sub { font-size: 9px; color: #666; margin-top: 1px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th, td { border: 1px solid {{ $greenColor }}; padding: 3px 4px; text-align: left; }
        th { font-weight: 600; background: #f9fafb; color: {{ $greenColor }}; }
        .table-info { margin-bottom: 4px; }
        .section-title {
            background: {{ $greenColor }};
            color: #fff;
            padding: 3px 6px;
            font-size: 9px;
            font-weight: 600;
            border-radius: 2px 2px 0 0;
        }
        .section-table { border-top: none; }
        .two-cols { display: table; width: 100%; margin-bottom: 4px; }
        .col-left { display: table-cell; width: 60%; padding-right: 6px; vertical-align: top; }
        .col-right { display: table-cell; width: 40%; vertical-align: top; }
        .block { margin-bottom: 3px; }
        .signatures { display: table; width: 100%; margin-top: 6px; font-size: 9px; }
        .signatures > div { display: table-cell; width: 50%; }
        .signatures .left { text-align: left; }
        .signatures .right { text-align: right; }
        .signatures .fw { font-weight: bold; }
        .signatures .mt { margin-top: 2px; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid {{ $greenColor }};
            text-align: center;
            font-size: 8px;
            padding: 4px 8px;
            background: #fff;
        }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="container page-container">
        <div class="watermark">{{ $company->name ?? 'COMPAGNIE D\'ASSURANCE' }}</div>

        <div class="header">
            <div class="header-top">
                <div class="header-logo">
                    @php
                        $logoPath = public_path(config('app.logo', 'logo.png'));
                        $logoBase64 = null;
                        if (file_exists($logoPath)) {
                            $logoBase64 = 'data:' . mime_content_type($logoPath) . ';base64,' . base64_encode(file_get_contents($logoPath));
                        }
                    @endphp
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="Logo">
                    @else
                        <div style="color: {{ $greenColor }}; font-size: 9px; font-weight: bold;">SCA</div>
                    @endif
                </div>
                <div class="header-title">Société de Courtage en Assurances</div>
                <div class="header-compagnie">
                    @if($company && $company->code)
                        @php
                            $compagniePath = public_path('companies/'.$company->code.'.jpg');
                            $compagnieBase64 = file_exists($compagniePath) ? 'data:' . mime_content_type($compagniePath) . ';base64,' . base64_encode(file_get_contents($compagniePath)) : null;
                        @endphp
                        @if($compagnieBase64 ?? false)
                            <img src="{{ $compagnieBase64 }}" alt="Logo compagnie">
                        @endif
                    @endif
                </div>
            </div>
            <div class="header-meta">
                <div class="left">
                    <div><span class="fw">Assuré :</span> {{ $client->full_name ?? 'N/A' }}</div>
                </div>
                <div class="right">
                    <div><span class="fw">N° Document :</span> DOC-{{ date('y') }}-{{ str_pad($contract->id, 6, '0', STR_PAD_LEFT) }}</div>
                </div>
            </div>
        </div>

        <div class="main-title">
            <h2>Contrat d'Assurance Automobile</h2>
            <div class="sub">N° Contrat: CT{{ date('y') }}{{ str_pad($contract->id, 6, '0', STR_PAD_LEFT) }} | Document: DOC-{{ date('y') }}-{{ str_pad($contract->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>

        <table class="table-info">
            <tr>
                <th>Date Effet</th>
                <td>{{ $contract->start_date ? $contract->start_date->format('d/m/Y') : 'N/A' }}</td>
                <th>Date Echéance</th>
                <td>{{ $contract->end_date ? $contract->end_date->format('d/m/Y') : 'N/A' }}</td>
                <th>Statut</th>
                <td>{{ $contract->status ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Compagnie</th>
                <td>{{ $company->name ?? 'N/A' }}</td>
                <th>Type Contrat</th>
                <td>{{ contractTypeLabel($contract->contract_type) }}</td>
            </tr>
        </table>

        <div class="block" style="margin-top: 4px;">
            <div class="section-title">Récapitulatif</div>
            <table class="section-table">
                <tr>
                    <th style="width: 12%;">Véhicule</th>
                    <td colspan="3">
                        <strong>Immat. :</strong> {{ $vehicle->registration_number ?? 'N/A' }}
                        | <strong>Type :</strong> {{ contractTypeLabel($contract->contract_type) }}
                        | <strong>Puissance :</strong> {{ powerDisplay($vehicle) }}
                        | <strong>Marque :</strong> {{ $vehicle && $vehicle->brand ? $vehicle->brand->name : 'N/A' }}
                        | <strong>Modèle :</strong> {{ $vehicle && $vehicle->model ? $vehicle->model->name : 'N/A' }}
                    </td>
                </tr>
                <tr>
                    <th>Client</th>
                    <td colspan="3">
                        <strong>Nom :</strong> {{ $client->full_name ?? 'N/A' }}
                        | <strong>Email :</strong> {{ $client->email ?? 'N/A' }}
                        | <strong>Contact :</strong> {{ $client->phone ?? 'N/A' }}
                        | <strong>Adresse :</strong> {{ $client->address ?? 'N/A' }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="two-cols">
            <div class="col-left">
                <div class="block">
                    <div class="section-title">Véhicule assuré</div>
                    <table class="section-table">
                        <tr>
                            <th>Marque</th>
                            <td>{{ $vehicle && $vehicle->brand ? $vehicle->brand->name : 'N/A' }}</td>
                            <th>Immat.</th>
                            <td>{{ $vehicle->registration_number ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Energie</th>
                            <td>{{ $vehicle && $vehicle->energySource ? $vehicle->energySource->name : 'N/A' }}</td>
                            <th>Puissance</th>
                            <td>{{ $vehicle ? (($vehicle->fiscal_power ?? $vehicle->engine_capacity ?? $vehicle->payload_capacity ?? '-') . ' ' . powerUnit($vehicle)) : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>N° Chassis</th>
                            <td>{{ $vehicle->chassis_number ?? 'N/A' }}</td>
                            <th>1ère Circ.</th>
                            <td>{{ $vehicle && $vehicle->first_registration_date ? $vehicle->first_registration_date->format('d/m/Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Usage</th>
                            <td>{{ $vehicle && $vehicle->vehicleUsage ? $vehicle->vehicleUsage->name : 'N/A' }}</td>
                            <th>Zone</th>
                            <td>{{ $vehicle && $vehicle->circulationZone ? $vehicle->circulationZone->name : 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="block">
                    <div class="section-title">Souscripteur / Assuré</div>
                    <table class="section-table">
                        <tr>
                            <th>Nom</th>
                            <td>{{ $client->full_name ?? 'N/A' }}</td>
                            <th>Tél</th>
                            <td>{{ $client->phone ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Profession</th>
                            <td>{{ $client->profession ? $client->profession->name : 'N/A' }}</td>
                            <th>Adresse</th>
                            <td>{{ $client->address ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td colspan="3">{{ $client->email ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="col-right">
                <div class="block">
                    <div class="section-title">Garanties</div>
                    <table class="section-table">
                        <thead>
                            <tr>
                                <th style="background:{{ $greenColor }};color:#fff;">Code</th>
                                <th style="background:{{ $greenColor }};color:#fff;">Désignation</th>
                                <th class="text-right" style="background:{{ $greenColor }};color:#fff;">Primes (FCFA)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>RC</td>
                                <td>Responsabilité Civile</td>
                                <td class="text-right font-bold">{{ number_format($contract->rc_amount ?? 0, 0, ',', ' ') }}</td>
                            </tr>
                            <tr>
                                <td>DR</td>
                                <td>Défence et Recours</td>
                                <td class="text-right font-bold">{{ number_format($contract->defence_appeal_amount ?? 0, 0, ',', ' ') }}</td>
                            </tr>
                            <tr>
                                <td>TP</td>
                                <td>Transport de personnes</td>
                                <td class="text-right font-bold">{{ number_format($contract->person_transport_amount ?? 0, 0, ',', ' ') }}</td>
                            </tr>
                            <tr class="font-bold">
                                <td colspan="2" class="text-right">Sous-total</td>
                                <td class="text-right">{{ number_format(($contract->rc_amount ?? 0) + ($contract->defence_appeal_amount ?? 0) + ($contract->person_transport_amount ?? 0), 0, ',', ' ') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="block">
                    <div class="section-title">Résumé Financier</div>
                    <table class="section-table">
                        <tr>
                            <th>Prime Nette</th>
                            <td class="text-right">{{ number_format($contract->base_amount ?? 0, 0, ',', ' ') }}</td>
                        </tr>
                        <tr>
                            <th>Accessoire</th>
                            <td class="text-right">{{ number_format($contract->accessory_amount ?? 0, 0, ',', ' ') }}</td>
                        </tr>
                        <tr>
                            <th>Taxes</th>
                            <td class="text-right">{{ number_format($contract->taxes_amount ?? 0, 0, ',', ' ') }}</td>
                        </tr>
                        <tr>
                            <th>Réduction BNS</th>
                            <td class="text-right">{{ number_format($contract->reduction_bns_amount ?? 0, 0, ',', ' ') }}</td>
                        </tr>
                        <tr>
                            <th>Réduction Com.</th>
                            <td class="text-right">{{ number_format($contract->reduction_on_commission_amount ?? 0, 0, ',', ' ') }}</td>
                        </tr>
                        <tr>
                            <th>Taxe FGA</th>
                            <td class="text-right">{{ number_format($contract->fga_amount ?? 0, 0, ',', ' ') }}</td>
                        </tr>
                        <tr>
                            <th>Cedeao</th>
                            <td class="text-right">{{ number_format($contract->cedeao_amount ?? 0, 0, ',', ' ') }}</td>
                        </tr>
                        <tr class="font-bold" style="background:#f3f4f6;">
                            <th>Net à Payer</th>
                            <td class="text-right">{{ number_format($contract->total_amount ?? 0, 0, ',', ' ') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="signatures">
            <div class="left">
                <div class="fw">Le Souscripteur</div>
                <div class="mt">Fait à {{ $vehicle && $vehicle->circulationZone ? $vehicle->circulationZone->name : 'Abidjan' }}, le {{ $issueDate instanceof \Carbon\Carbon ? $issueDate->format('d/m/Y') : \Carbon\Carbon::parse($issueDate)->format('d/m/Y') }}</div>
            </div>
            <div class="right">
                <div class="fw">Pour la Compagnie</div>
            </div>
        </div>
    </div>

    <div class="footer" style="border-color: {{ $greenColor }};">
        {{ config('app.contract_footer.line1') }}<br>
        {{ config('app.contract_footer.line2') }}
    </div>
</body>
</html>
