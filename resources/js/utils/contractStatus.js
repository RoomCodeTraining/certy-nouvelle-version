/**
 * Statuts des contrats — constantes partagées.
 */
export const CONTRACT_STATUS = {
    DRAFT: "draft",
    VALIDATED: "validated",
    ACTIVE: "active",
    CANCELLED: "cancelled",
    EXPIRED: "expired",
};

export const CONTRACT_STATUS_LABELS = {
    draft: "Brouillon",
    validated: "Validé",
    active: "Actif",
    cancelled: "Annulé",
    expired: "Expiré",
};

export function contractStatusLabel(status) {
    return (status && CONTRACT_STATUS_LABELS[status]) ?? status ?? "—";
}

/** Classes Tailwind pour badge statut contrat (vert=ok, rouge=négatif, gris=neutre). */
export function contractStatusBadgeClass(status) {
    const s = String(status ?? "").toLowerCase();
    if (["active", "actif", "validated", "validé"].some((x) => s.includes(x)))
        return "bg-emerald-100 text-emerald-800";
    if (["cancelled", "annulé", "expired", "expiré"].some((x) => s.includes(x)))
        return "bg-red-100 text-red-800";
    return "bg-slate-100 text-slate-800";
}
