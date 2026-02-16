/**
 * Libellés des types de contrat / type d'engin pour l'affichage.
 * VP, TPC (Transport pour propre compte), TPM = codes métier ; TWO_WHEELER → "Deux roues".
 */
const CONTRACT_TYPE_LABELS = {
    VP: 'VP',
    TPC: 'Transport pour propre compte',
    TPM: 'TPM',
    TWO_WHEELER: 'Deux roues',
};

export function contractTypeLabel(value) {
    if (value == null || value === '') return '—';
    return CONTRACT_TYPE_LABELS[value] ?? value;
}

/**
 * Couleur d'attestation selon le type de contrat : VP = Jaune, TWO_WHEELER = Verte, le reste = Jaune.
 */
export function attestationColor(value) {
    if (value == null || value === '') return 'yellow';
    return String(value).startsWith('TWO') || value === 'TWO_WHEELER' ? 'green' : 'yellow';
}

/** Libellé couleur attestation pour affichage. */
export function attestationColorLabel(value) {
    return attestationColor(value) === 'green' ? 'Verte' : 'Jaune';
}

/** Classes Tailwind pour le badge couleur attestation (fond + texte). */
export function attestationColorClasses(value) {
    return attestationColor(value) === 'green'
        ? 'bg-emerald-100 text-emerald-800 border-emerald-200'
        : 'bg-amber-100 text-amber-800 border-amber-200';
}
