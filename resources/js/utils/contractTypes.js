/**
 * Libellés des types de contrat / type d'engin pour l'affichage.
 * VP, TPC (Transport pour propre compte), TPM = codes métier ; TWO_WHEELER → "Deux roues".
 */
const CONTRACT_TYPE_LABELS = {
    VP: "Véhicule particulier",
    TPC: "Transport pour propre compte",
    TPM: "Transport privé de marchandises",
    TWO_WHEELER: "Deux roues",
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

/**
 * Contrat enfant lié à un parent : avenant (colonne ou metadata) ou renouvellement.
 * @param {object|null|undefined} row — contrat avec parent_id, endorsement_type optionnel, metadata
 */
export function contractIsEndorsement(row) {
    if (!row?.parent_id) return false;
    if (row.endorsement_type) return true;
    const m = row.metadata;
    if (!m || typeof m !== 'object') return false;
    if (m.endorsement_type) return true;
    if (m.creation_mode === 'endorsement') return true;
    return false;
}

/** Libellé badge « Affaire » : nouvelle affaire, renouvellement ou avenant. */
export function contractDealTypeLabel(row) {
    if (!row?.parent_id) return 'Nouvelle affaire';
    return contractIsEndorsement(row) ? 'Avenant' : 'Renouvellement';
}

/** Classes Tailwind pour le badge Affaire (liste contrats, fiches). */
export function contractDealTypeBadgeClass(row) {
    if (!row?.parent_id) return 'bg-emerald-100 text-emerald-800';
    return contractIsEndorsement(row)
        ? 'bg-amber-100 text-amber-800'
        : 'bg-violet-100 text-violet-800';
}
