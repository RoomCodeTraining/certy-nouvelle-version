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
