<?php

namespace App\Services;

class ExportAttestationsHelper
{
    /** @return array<string, string> clé => libellé */
    public static function columnDefinitions(): array
    {
        return [
            'statut' => 'Statut',
            'numero_police' => 'Numéro de police',
            'numero_immatriculation' => 'Numéro immatriculation',
            'date_edition' => 'Date édition',
            'date_effet' => 'Date effet',
            'date_expiration' => 'Date expiration',
            'type_attestation' => 'Type attestation',
            'couleur_attestation' => 'Couleur attestation',
            'nom_souscripteur' => 'Nom souscripteur',
            'nom_assure' => 'Nom assuré',
            'numero_assure' => 'Numéro assuré',
            'genre' => 'Genre',
            'categorie' => 'Catégorie',
            'usage' => 'Usage',
            'energie' => 'Energie',
            'marque' => 'Marque',
            'modele' => 'Modèle',
            'numero_chassis' => 'Numéro châssis',
            'email_producteur' => 'Email producteur',
            'rc' => 'RC',
            'intermediaire' => 'Intermédiaire',
            'compagnie' => 'Compagnie',
            'lien_telechargement' => 'Lien de téléchargement',
        ];
    }

    /**
     * Retourne les colonnes qui ont au moins une valeur non vide dans les données.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<string> clés des colonnes à inclure
     */
    public static function activeColumns(array $rows): array
    {
        $defs = array_keys(self::columnDefinitions());
        if (empty($rows)) {
            return $defs;
        }
        $hasData = array_fill_keys($defs, false);
        foreach ($rows as $row) {
            $values = self::rowToExportValues(is_array($row) ? $row : []);
            foreach ($values as $key => $val) {
                if ($val !== null && $val !== '') {
                    $hasData[$key] = true;
                }
            }
        }

        return array_values(array_filter($defs, fn ($k) => $hasData[$k]));
    }

    /**
     * Extrait les valeurs d'une ligne API pour l'export Excel Reporting.
     *
     * @return array<string, string|mixed>
     */
    public static function rowToExportValues(array $row): array
    {
        $val = static fn ($a, string $k = '') => is_array($a) ? ($a[$k] ?? $a['name'] ?? $a['code'] ?? $a['label'] ?? '') : '';
        $prod = $row['production'] ?? [];
        $prodUser = $prod['user'] ?? [];

        return [
            'statut' => $val($row['state'] ?? null, 'label') ?: ($row['status'] ?? $row['etat'] ?? ''),
            'numero_police' => $row['police_number'] ?? $row['policy_number'] ?? $row['reference'] ?? $row['id'] ?? '',
            'numero_immatriculation' => $row['licence_plate'] ?? $row['registration_number'] ?? $row['plaque'] ?? $row['immat'] ?? '',
            'date_edition' => $row['printed_at'] ?? $row['issued_at'] ?? $row['created_at'] ?? '',
            'date_effet' => $row['starts_at'] ?? $row['start_date'] ?? $row['period_start'] ?? $row['effective_date'] ?? '',
            'date_expiration' => $row['ends_at'] ?? $row['end_date'] ?? $row['period_end'] ?? $row['expiry_date'] ?? '',
            'type_attestation' => $val($row['certificate_type'] ?? null, 'name') ?: $val($row['certificate_variant'] ?? null, 'name') ?: ($row['type'] ?? ''),
            'couleur_attestation' => $val($row['certificate_variant'] ?? null, 'name') ?: $val($row['certificate_variant'] ?? null, 'code') ?: '',
            'nom_souscripteur' => $val($prodUser, 'name') ?: ($row['subscriber_name'] ?? $row['subscriber'] ?? ''),
            'nom_assure' => $row['insured_name'] ?? $row['assure'] ?? $row['insured'] ?? $row['policy_holder'] ?? '',
            'numero_assure' => $row['insured_phone'] ?? $row['insured_number'] ?? $row['insured_id'] ?? '',
            'genre' => $val($row['gender'] ?? $row['genre'] ?? null, 'label') ?: ($row['gender'] ?? $row['genre'] ?? ''),
            'categorie' => $val($row['vehicle_category'] ?? $row['category'] ?? null, 'name') ?: $val($row['vehicle_category'] ?? $row['category'] ?? null, 'code') ?: '',
            'usage' => $val($row['vehicle_usage'] ?? $row['usage'] ?? null, 'name') ?: $val($row['vehicle_usage'] ?? $row['usage'] ?? null, 'code') ?: '',
            'energie' => $val($row['energy_source'] ?? $row['energy'] ?? null, 'name') ?: $val($row['energy_source'] ?? $row['energy'] ?? null, 'code') ?: '',
            'marque' => $val($row['vehicle_brand'] ?? $row['brand'] ?? null, 'name') ?: $val($row['vehicle_brand'] ?? $row['brand'] ?? null, 'code') ?: '',
            'modele' => $val($row['vehicle_model'] ?? $row['model'] ?? null, 'name') ?: $val($row['vehicle_model'] ?? $row['model'] ?? null, 'code') ?: '',
            'numero_chassis' => $row['chassis_number'] ?? $row['numero_chassis'] ?? '',
            'email_producteur' => $val($prodUser, 'email') ?: ($row['producer_email'] ?? ''),
            'rc' => $val($prod, 'reference') ?: ($row['rc'] ?? $row['registration_card'] ?? ''),
            'intermediaire' => $val($row['office'] ?? null, 'name') ?: $val($row['office'] ?? null, 'code') ?: $val($row['intermediary'] ?? null, 'name') ?: '',
            'compagnie' => $val($row['organization'] ?? null, 'name') ?: $val($row['organization'] ?? null, 'code') ?: '',
            'lien_telechargement' => $row['download_link'] ?? $row['download_url'] ?? '',
        ];
    }
}
