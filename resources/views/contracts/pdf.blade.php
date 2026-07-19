@php
$greenColor = '#219150';
$contract = $contract ?? null;
if (!$contract) {
    return;
}
$vehicle = $contract->vehicle;
$secondVehicle = $contract->is_double_cabine ? $contract->secondVehicle : null;
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
    @include('contracts.pdf._shared_styles')
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
                    @php
                        $compagnieBase64 = $company_logo_base64 ?? null;
                        if (!$compagnieBase64 && $company && $company->code) {
                            $compagniePath = public_path('companies/'.$company->code.'.jpg');
                            $compagnieBase64 = file_exists($compagniePath) ? 'data:' . mime_content_type($compagniePath) . ';base64,' . base64_encode(file_get_contents($compagniePath)) : null;
                        }
                    @endphp
                    @if($compagnieBase64)
                        <img src="{{ $compagnieBase64 }}" alt="Logo compagnie">
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
                    <th style="width: 12%;">Véhicule{{ $secondVehicle ? ' 1 (charge utile)' : '' }}</th>
                    <td colspan="3">
                        <strong>Immat. :</strong> {{ $vehicle->registration_number ?? 'N/A' }}
                        | <strong>Type :</strong> {{ contractTypeLabel($contract->contract_type) }}
                        | <strong>Puissance :</strong> {{ powerDisplay($vehicle) }}
                        | <strong>Marque :</strong> {{ $vehicle && $vehicle->brand ? $vehicle->brand->name : 'N/A' }}
                        | <strong>Modèle :</strong> {{ $vehicle && $vehicle->model ? $vehicle->model->name : 'N/A' }}
                    </td>
                </tr>
                @if($secondVehicle)
                <tr>
                    <th>Véhicule 2 (cabine)</th>
                    <td colspan="3">
                        <strong>Immat. :</strong> {{ $secondVehicle->registration_number ?? 'N/A' }}
                        | <strong>Marque :</strong> {{ $secondVehicle->brand ? $secondVehicle->brand->name : 'N/A' }}
                        | <strong>Modèle :</strong> {{ $secondVehicle->model ? $secondVehicle->model->name : 'N/A' }}
                    </td>
                </tr>
                @endif
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
                @php
                    $displayAmounts = $contract->getDisplayAmounts();
                    $guaranteesReduced = $displayAmounts['guarantee_amounts_reduced'];
                    $primeNettePdf = $displayAmounts['prime_nette'];
                    $reductionAutre = (int) ($contract->reduction_amount ?? 0);
                    $montantApresReductionPdf = $displayAmounts['montant_apres_reduction'];
                @endphp
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
                            @foreach($guaranteesReduced as $g)
                                <tr>
                                    <td>{{ $g['code'] }}</td>
                                    <td>{{ $g['label'] }}</td>
                                    <td class="text-right font-bold">{{ number_format($g['amount'], 0, ',', ' ') }}</td>
                                </tr>
                            @endforeach
                            <tr class="font-bold">
                                <td colspan="2" class="text-right">Prime nette</td>
                                <td class="text-right">{{ number_format($primeNettePdf, 0, ',', ' ') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="block">
                    <div class="section-title">Résumé Financier</div>
                    @php
                        $taxesAmount = $displayAmounts['taxes_amount'];
                        $primeTtc = $displayAmounts['total_amount'];
                    @endphp
                    <table class="section-table">
                        <tr class="row-highlight">
                            <th>Prime nette</th>
                            <td class="text-right">{{ number_format($primeNettePdf, 0, ',', ' ') }}</td>
                        </tr>
                        @if($reductionAutre > 0)
                        <tr>
                            <th>Réduction autre</th>
                            <td class="text-right" style="color:#dc2626;">− {{ number_format($reductionAutre, 0, ',', ' ') }}</td>
                        </tr>
                        <tr>
                            <th>Montant après réduction</th>
                            <td class="text-right font-bold">{{ number_format($montantApresReductionPdf, 0, ',', ' ') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Accessoire</th>
                            <td class="text-right">{{ number_format($contract->accessory_amount ?? 0, 0, ',', ' ') }}</td>
                        </tr>
                        <tr>
                            <th>Taxes</th>
                            <td class="text-right">{{ number_format($taxesAmount ?? 0, 0, ',', ' ') }}</td>
                        </tr>
                        <tr>
                            <th>Taxe FGA</th>
                            <td class="text-right">{{ number_format($contract->fga_amount ?? 0, 0, ',', ' ') }}</td>
                        </tr>
                        <tr>
                            <th>CEDEAO</th>
                            <td class="text-right">{{ number_format($contract->cedeao_amount ?? 0, 0, ',', ' ') }}</td>
                        </tr>
                        <tr>
                            <th>Prime ASACI</th>
                            <td class="text-right">{{ number_format($contract->cp_amount ?? 0, 0, ',', ' ') }}</td>
                        </tr>
                        @if(($contract->agency_accessory ?? 0) > 0)
                            <tr>
                                <th>Accessoire agence</th>
                                <td class="text-right">{{ number_format($contract->agency_accessory ?? 0, 0, ',', ' ') }}</td>
                            </tr>
                        @endif
                        @if(($contract->company_accessory ?? 0) > 0)
                            <tr>
                                <th>Accessoire compagnie</th>
                                <td class="text-right">{{ number_format($contract->company_accessory ?? 0, 0, ',', ' ') }}</td>
                            </tr>
                        @endif
                        <tr class="row-total">
                            <th>Prime TTC</th>
                            <td class="text-right">{{ number_format($primeTtc ?? 0, 0, ',', ' ') }} FCFA</td>
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
