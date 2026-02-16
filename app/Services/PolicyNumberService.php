<?php

namespace App\Services;

use App\Models\OrganizationCompanyConfig;

class PolicyNumberService
{
    /**
     * Longueur du numéro aléatoire (chiffres).
     */
    private const RANDOM_LENGTH = 8;

    /**
     * Identifiant par défaut si aucune config n'est trouvée.
     */
    private const DEFAULT_IDENTIFIER = 'POL';

    /**
     * Génère un numéro de police au format : {identifiant}/{numéro aléatoire}.
     * L'identifiant vient de la configuration (OrganizationCompanyConfig.policy_number_identifier).
     *
     * @param  string|null  $configCode  Code de la config (ex. code compagnie) pour cibler l'identifiant. Si null, utilise la première config avec un identifiant renseigné.
     * @return string Ex. "MYCO/12345678" ou "POL/87654321"
     */
    public function generate(?string $configCode = null): string
    {
        $identifier = $this->resolveIdentifier($configCode);
        $random = $this->generateRandomNumber();

        return $identifier.'/'.$random;
    }

    /**
     * Résout l'identifiant à partir de la configuration.
     */
    private function resolveIdentifier(?string $configCode): string
    {
        $query = OrganizationCompanyConfig::query()
            ->whereNotNull('policy_number_identifier')
            ->where('policy_number_identifier', '!=', '');

        if ($configCode !== null && $configCode !== '') {
            $query->where('code', $configCode);
        }

        $config = $query->orderBy('id')->first();
        $raw = $config ? trim((string) $config->policy_number_identifier) : '';

        return $raw !== '' ? $raw : self::DEFAULT_IDENTIFIER;
    }

    /**
     * Génère un numéro aléatoire sur N chiffres.
     */
    private function generateRandomNumber(): string
    {
        $min = (int) str_pad('1', self::RANDOM_LENGTH, '0');
        $max = (int) str_pad('9', self::RANDOM_LENGTH, '9');

        return (string) random_int($min, $max);
    }
}
