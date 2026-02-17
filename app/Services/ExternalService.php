<?php

namespace App\Services;

use App\Models\Contract;
use App\Services\PolicyNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalService
{
    /** Timeout en secondes pour tous les appels HTTP (objectif < 5s par requête). */
    private const HTTP_TIMEOUT = 5;

    public $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('app.asaci_core_url', ''), '/');
    }

    /**
     * Génère une attestation digitale via l'API productions ASACI.
     * Construit le payload à partir du contrat, véhicule et client.
     * code_demandeur = username du user connecté (récupéré depuis les infos user ASACI à la connexion).
     *
     * @param  string|null  $codeDemandeur  Username ASACI du user (ex. depuis $request->user()->external_username)
     * @return array{success: bool, numero_attestation?: string, lien_pdf?: string, errors?: array}
     */
    public function createProduction(Contract $contract, ?string $codeDemandeur = null): array
    {
        $contract->load([
            'client',
            'vehicle.brand',
            'vehicle.model',
            'vehicle.energySource',
            'vehicle.vehicleUsage',
            'vehicle.vehicleType',
            'vehicle.vehicleGender',
            'company',
        ]);

        $vehicle = $contract->vehicle;
        $client = $contract->client;
        $company = $contract->company;

        $codeCompagnie = $company && $company->code ? $company->code : 'ASACI_LUNAR';
        $numeroPolice = $contract->policy_number;
        if ($numeroPolice === null || $numeroPolice === '') {
            $numeroPolice = app(PolicyNumberService::class)->generate($company->code ?? null);
            $contract->update(['policy_number' => $numeroPolice]);
        }

        $listeDemande = [
            'rc' => (string) ($contract->rc_amount ?? '0'),
            'date_effet' => $contract->start_date ? $contract->start_date->format('Y-m-d') : '',
            'date_echeance' => $contract->end_date ? $contract->end_date->format('Y-m-d') : '',
            'nom_assure' => $client ? ($client->full_name ?? '') : '',
            'nom_souscripteur' => $client ? ($client->full_name ?? '') : '',
            'type_souscripteur' => $client->type ?? "TAPP",
            'numero_police' => $numeroPolice,
            'type_vehicule' => $vehicle && $vehicle->vehicleType && $vehicle->vehicleType->code ? $vehicle->vehicleType->code : 'TV06',
            'genre_vehicule' => $vehicle && $vehicle->vehicleGender && $vehicle->vehicleGender->code ? $vehicle->vehicleGender->code : 'GV01',
            'model_vehicule' => $vehicle && $vehicle->model ? substr(str_replace(' ', '', $vehicle->model->name ?? ''), 0, 30) : '',
            'marque_vehicule' => $vehicle && $vehicle->brand ? substr(str_replace(' ', '', $vehicle->brand->name ?? ''), 0, 30) : '',
            'numero_chassis' => $vehicle ? ($vehicle->chassis_number ?? '') : '',
            'source_energie' => $vehicle && $vehicle->energySource && $vehicle->energySource->code ? $vehicle->energySource->code : 'SEES',
            'usage_vehicule' => $vehicle && $vehicle->vehicleUsage && $vehicle->vehicleUsage->code ? $vehicle->vehicleUsage->code : 'UV05',
            'numero_immatriculation' => $vehicle ? ($vehicle->registration_number ?? '') : '',
            'nombre_place' => (string) ($vehicle->seat_count ?? 5),
            'categorie_vehicule' => '01',
            'adresse_mail_assure' => $client ? ($client->email ?? '') : '',
            'numero_telephone_assure' => $client ? ($client->phone ?? '') : '',
            'boite_postale_assure' => $client ? ($client->postal_address ?? $client->address ?? '') : '',
            'adresse_mail_souscripteur' => $client ? ($client->email ?? '') : '',
            'numero_telephone_souscripteur' => $client ? ($client->phone ?? '') : '',
            'boite_postale_souscripteur' => $client ? ($client->postal_address ?? $client->address ?? '') : '',
            'code_nature_attestation' => config('app.asaci_code_nature_attestation', 'JAUN'),
        ];


        $payload = [
            'code_demandeur' => $codeDemandeur !== null && $codeDemandeur !== '' ? $codeDemandeur : config('app.asaci_code_demandeur', ''),
            'code_intermediaire' => config('app.asaci_code_intermediaire', ''),
            'code_compagnie' => $codeCompagnie,
            'liste_demande' => [$listeDemande],
        ];

        $productionsUrl = rtrim(config('app.asaci_productions_url', ''), '/').'/productions';

        $response = Http::timeout(self::HTTP_TIMEOUT)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post($productionsUrl, $payload);

        if ($response->failed()) {
            Log::warning('ExternalService createProduction failed', [
                'payload' => $payload,
                'status' => $response->status(),
                'body' => $response->body(),
                'contract_id' => $contract->id,
            ]);
            return [
                'success' => false,
                'errors' => [[
                    'title' => $response->json()['message'] ?? 'Erreur lors de la génération de l\'attestation.',
                    'detail' => $response->body(),
                ]],
            ];
        }

        $rawBody = $response->body();
        $data = $response->json();
        $statut = $data['statut'] ?? null;
        $infos = $data['infos'] ?? [];

        if ($statut !== '0' && $statut !== 0) {
            Log::warning('ExternalService createProduction statut non OK', [
                'payload' => $payload,
                'statut' => $statut,
                'http_status' => $response->status(),
                'raw_response' => $rawBody,
                'data' => $data,
                'contract_id' => $contract->id,
            ]);
            return [
                'success' => false,
                'errors' => [[
                    'title' => $data['message'] ?? 'La plateforme a refusé la demande d\'attestation.',
                    'detail' => $data,
                ]],
            ];
        }

        $first = is_array($infos) && isset($infos[0]) ? $infos[0] : [];
        $numeroAttestation = $first['numero_attestation'] ?? null;
        $lienPdf = $first['lien_pdf'] ?? null;

        if (! $numeroAttestation || ! $lienPdf) {
            return [
                'success' => false,
                'errors' => [['title' => 'Réponse invalide : numéro ou lien d\'attestation manquant.', 'detail' => $data]],
            ];
        }

        return [
            'success' => true,
            'numero_attestation' => $numeroAttestation,
            'lien_pdf' => $lienPdf,
        ];
    }

    /**
     * Auth the user
     *
     * @param  string  $email
     * @param  string  $password
     * @return array
     */
    public function auth($email, $password)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->post($this->baseUrl.'/auth/tokens', [
            'email' => $email,
            'password' => $password,
        ]);

        if (! $response->successful()) {
            Log::debug('ExternalService auth/tokens', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return $response->json();
    }

    public function getUser($token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/auth/user');

        if (! $response->successful()) {
            Log::warning('ExternalService auth/user échec', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $this->baseUrl.'/auth/user',
            ]);
        } else {
            Log::debug('ExternalService auth/user succès', ['body' => $response->json()]);
        }

        return $response->json();
    }

    public function relationnship($token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/auth/relationnship');

        return $response->json();
    }

    public function storeUser(array $data, string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post($this->baseUrl.'/users', $data);

        if ($response->failed()) {
            $body = $response->json();
            Log::warning('ExternalService::storeUser API error', [
                'status' => $response->status(),
                'request_payload' => array_diff_key($data, array_flip(['password'])),
                'response_body' => $body,
                'response_raw' => $response->body(),
            ]);
        }

        return $response->json();
    }

    public function getOffices(string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/offices?per_page=10');

        return $response->json();
    }

    public function desactivateOffice(string $id, string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post($this->baseUrl.'/offices/'.$id.'/disable');

        return $response->json();
    }

    public function activateOffice(string $id, string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post($this->baseUrl.'/offices/'.$id.'/enable');

        return $response->json();
    }

    public function storeOffice(array $data, string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post($this->baseUrl.'/offices', $data);

        return $response->json();
    }

    public function getOfficeTypes(string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/office-types');

        return $response->json();
    }

    public function getUsers(string $token, Request $request)
    {
        $queryParams = [];

        if ($request->has('per_page')) {
            $queryParams['per_page'] = $request->per_page;
        }
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/users?'.http_build_query($queryParams));

        return $response->json();
    }

    public function desactivateUser(string $id, string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/users/'.$id.'/disable');

        return $response->json();
    }

    public function activateUser(string $id, string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/users/'.$id.'/enable');

        return $response->json();
    }

    public function getCertificates(string $token, Request $request)
    {
        $queryParams = [];
        $queryParams['per_page'] = $request->integer('per_page', 10);

        if ($request->has('printed_at') && $request->printed_at !== null && $request->printed_at !== '') {
            $queryParams['printed_at'] = $request->printed_at;
        }

        if ($request->filled('cursor')) {
            $queryParams['cursor'] = $request->cursor;
        }

        if ($request->filled('search')) {
            $queryParams['search'] = $request->search;
        }

        $url = $this->baseUrl.'/certificates';
        if (! empty($queryParams)) {
            $url .= '?'.http_build_query($queryParams);
        }

        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($url);

        if ($response->failed()) {
            return [
                'errors' => [
                    [
                        'status' => $response->status(),
                        'title' => $response->json()['message'] ?? 'Oops. Something went wrong. Please try again or contact support',
                        'detail' => $response->json()['errors'] ?? null,
                    ],
                ],
            ];
        }

        return $response->json();
    }

    public function showCertificate(string $reference, string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/certificates/'.$reference);

        if ($response->failed()) {
            return [
                'errors' => [
                    [
                        'status' => $response->status(),
                        'title' => $response->json()['message'] ?? 'Oops. Something went wrong. Please try again or contact support',
                        'detail' => $response->json()['errors'] ?? null,
                    ],
                ],
            ];
        }

        return $response->json();
    }

    /**
     * Récupère le détail d'un certificat (GET /api/v1/certificates/{reference}).
     * La réponse contient notamment printed_certificate (URL du PDF ou contenu base64) pour afficher l'attestation.
     *
     * @param  string  $reference  Ex. ATD-B0E76B5C26
     * @return array{data?: array{printed_certificate?: string, reference?: string, ...}, errors?: array}
     */
    public function getCertificate(string $reference, string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/certificates/'.$reference);


        if ($response->failed()) {
            return [
                'errors' => [
                    [
                        'status' => $response->status(),
                        'title' => $response->json()['message'] ?? 'Oops. Something went wrong. Please try again or contact support',
                        'detail' => $response->json()['errors'] ?? null,
                    ],
                ],
            ];
        }

        return $response->json();
    }

    /**
     * Récupère le PDF d'affichage d'une attestation via GET /certificates/{reference} puis printed_certificate.
     * printed_certificate peut être : une URL du PDF (on la récupère avec le token), ou du base64.
     *
     * @param  string  $reference  Ex. ATD-B0E76B5C26
     * @param  string  $token
     * @return array{body: string, content_type: string}|array{errors: array}
     */
    public function getCertificatePrintedPdf(string $reference, string $token): array
    {
        $cert = $this->getCertificate($reference, $token);
        if (isset($cert['errors'])) {
            // Détail certificat en erreur : tenter quand même l'endpoint download
            $download = $this->downloadCertificate($reference, $token);
            if (! is_array($download) || ! isset($download['errors'])) {
                return [
                    'body' => $download->body(),
                    'content_type' => $download->headers()->get('Content-Type') ?? 'application/pdf',
                ];
            }
            return $cert;
        }

        $data = $cert['data'] ?? $cert;
        // Chercher printed_certificate au premier niveau ou dans data.attributes (JSON:API)
        $printed = $data['printed_certificate'] ?? $data['attributes']['printed_certificate'] ?? null;

        // printed_certificate peut être un objet { url, content, data, ... }
        if (is_array($printed)) {
            $printed = $printed['url'] ?? $printed['content'] ?? $printed['data'] ?? $printed['base64'] ?? null;
        }
        if (empty($printed) || ! is_string($printed)) {
            $download = $this->downloadCertificate($reference, $token);
            if (! is_array($download) || ! isset($download['errors'])) {
                return [
                    'body' => $download->body(),
                    'content_type' => $download->headers()->get('Content-Type') ?? 'application/pdf',
                ];
            }
            return [
                'errors' => [['title' => 'Attestation sans printed_certificate et téléchargement indisponible.', 'detail' => null]],
            ];
        }

        $printed = trim($printed);

        // URL du PDF (ex. renvoyée par l'API)
        if (str_starts_with($printed, 'http://') || str_starts_with($printed, 'https://')) {
            $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->get($printed);

            if ($response->failed()) {
                return [
                    'errors' => [
                        [
                            'title' => $response->json()['message'] ?? 'Impossible de récupérer le PDF imprimé.',
                            'detail' => null,
                        ],
                    ],
                ];
            }

            return [
                'body' => $response->body(),
                'content_type' => $response->header('Content-Type') ?: 'application/pdf',
            ];
        }

        // Contenu base64 : data URL (data:application/pdf;base64,... ou data:image/jpeg;base64,... etc.)
        $contentType = 'application/pdf';
        $base64 = $printed;
        if (preg_match('#^data:([^;]+);base64,(.+)$#s', $printed, $m)) {
            $contentType = trim($m[1]);
            $base64 = $m[2];
        }
        $base64 = preg_replace('#\s+#', '', $base64);
        $decoded = base64_decode($base64, true);
        if ($decoded === false || $decoded === '') {
            // Fallback : utiliser l'endpoint download pour récupérer le PDF
            $download = $this->downloadCertificate($reference, $token);
            if (is_array($download) && isset($download['errors'])) {
                return [
                    'errors' => [['title' => 'printed_certificate invalide et téléchargement indisponible.', 'detail' => null]],
                ];
            }

            return [
                'body' => $download->body(),
                'content_type' => $download->headers()->get('Content-Type') ?? 'application/pdf',
            ];
        }

        return [
            'body' => $decoded,
            'content_type' => $contentType,
        ];
    }

    /**
     * Télécharge le PDF d'un certificat depuis l'API externe (GET /certificates/{reference}/download).
     *
     * @param  string  $reference
     * @param  string  $token
     * @return array|\Illuminate\Http\Response
     */
    public function downloadCertificate(string $reference, string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/certificates/'.$reference.'/download');

        if ($response->failed()) {
            return [
                'errors' => [
                    [
                        'status' => $response->status(),
                        'title' => $response->json()['message'] ?? 'Oops. Something went wrong. Please try again or contact support',
                        'detail' => $response->json()['errors'] ?? null,
                    ],
                ],
            ];
        }

        return $response;
    }

    public function getTransactions(string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/transactions?per_page=100');

        return $response->json();
    }

    public function cancelTransaction(string $reference, string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post($this->baseUrl.'/transactions/'.$reference.'/cancel');

        return $response->json();
    }

    public function getStockUsage(string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/transactions/statistics/usage');

        return $response->json();
    }

    public function getOfficesDashboard(string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/dashboard/offices');

        return $response->json();
    }

    public function getRelationships(string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/relationships');


        return $response->json();
    }

    public function getCertificatesTypes(string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/certificate-types');

        return $response->json();
    }

    public function getCertificatesStatistics(string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/certificates/statistics/usage');

        if ($response->failed()) {
            return [
                'errors' => [
                    [
                        'status' => $response->status(),
                        'title' => $response->json()['message'] ?? 'Oops. Something went wrong. Please try again or contact support',
                        'detail' => $response->json()['errors'] ?? null,
                    ],
                ],
            ];
        }

        return $response->json();
    }

    public function storeTransaction(array $data, string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->post($this->baseUrl.'/transactions', $data);

        return $response->json();
    }

    public function getStatistics(string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/users/statistic');

        return $response->json();
    }

    public function getProductions(string $token, Request $request)
    {
        $queryParams = [];

        if ($request->has('per_page')) {
            $queryParams['per_page'] = $request->per_page;
        }

        if ($request->has('from') && $request->from !== null && $request->from !== '') {
            $queryParams['from'] = $request->from;
        }

        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/productions?'.http_build_query($queryParams));

        return $response->json();
    }

    public function showOffice(string $id, string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/offices/'.$id);

        return $response->json();
    }

    public function showUser(string $id, string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/users/'.$id);

        return $response->json();
    }

    public function updateUser(string $id, string $token, array $data)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->post($this->baseUrl.'/users/'.$id, $data);

        return $response->json();
    }

    public function updateOffice(string $id, string $token, array $data)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->put($this->baseUrl.'/offices/'.$id, $data);

        return $response->json();
    }

    public function getRoles(string $token)
    {
        $response = Http::timeout(self::HTTP_TIMEOUT)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->get($this->baseUrl.'/roles');

        return $response->json();
    }
}
