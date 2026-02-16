/**
 * Formate une date pour l'affichage (format français : JJ/MM/AAAA).
 * Accepte : chaîne ISO (2026-02-15), Date, ou valeur partielle.
 */
export function formatDate(value) {
    if (value == null || value === '') return '—';
    const str = typeof value === 'string' ? value : (value instanceof Date ? value.toISOString() : String(value));
    const ymd = str.slice(0, 10);
    const match = ymd.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return ymd || '—';
    const [, y, m, d] = match;
    return `${d}/${m}/${y}`;
}

/**
 * Format court avec mois abrégé (ex. 15 févr. 2026).
 */
const mois = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

export function formatDateLong(value) {
    if (value == null || value === '') return '—';
    const str = typeof value === 'string' ? value : (value instanceof Date ? value.toISOString() : String(value));
    const match = str.slice(0, 10).match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return str.slice(0, 10) || '—';
    const [, y, m, d] = match;
    const mi = parseInt(m, 10) - 1;
    return `${parseInt(d, 10)} ${mois[mi]} ${y}`;
}
