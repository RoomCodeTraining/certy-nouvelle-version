/**
 * Vérifie si un contrat arrive à échéance bientôt (par défaut : 7 jours).
 * @param {string} endDate - Date d'échéance (ISO YYYY-MM-DD)
 * @param {string} status - Statut du contrat (on ne met en avant que active/validated)
 * @param {number} days - Nombre de jours avant échéance
 * @returns {boolean}
 */
export function expiresSoon(endDate, status, days = 7) {
    if (!endDate || !status) return false;
    const s = String(status).toLowerCase();
    if (!['active', 'validated'].includes(s)) return false;
    const daysUntil = daysUntilExpiry(endDate);
    return daysUntil != null && daysUntil >= 0 && daysUntil <= days;
}

/**
 * Nombre de jours entre aujourd'hui et la date d'échéance.
 * @param {string} endDate - Date ISO YYYY-MM-DD
 * @returns {number|null} - Nombre de jours (négatif si déjà passé), null si date invalide
 */
export function daysUntilExpiry(endDate) {
    if (!endDate || typeof endDate !== 'string') return null;
    const match = String(endDate).slice(0, 10).match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return null;
    const end = new Date(match[1], parseInt(match[2], 10) - 1, parseInt(match[3], 10));
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    end.setHours(0, 0, 0, 0);
    return Math.floor((end - today) / (24 * 60 * 60 * 1000));
}
