import { ref, onMounted } from 'vue';

/**
 * Charge les KPIs du tableau de bord depuis GET /api/statistics.
 * Expose : revenus_totaux, contrats_actifs, clients, vehicules, loading, error, refresh.
 */
export function useStatistics() {
    const revenusTotaux = ref(0);
    const contratsActifs = ref(0);
    const clients = ref(0);
    const vehicules = ref(0);
    const loading = ref(true);
    const error = ref(null);

    async function fetchStatistics() {
        loading.value = true;
        error.value = null;
        try {
            const res = await fetch('/api/statistics', { credentials: 'include' });
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                throw new Error(data.message || `Erreur ${res.status}`);
            }
            const data = await res.json();
            revenusTotaux.value = data.revenus_totaux ?? 0;
            contratsActifs.value = data.contrats_actifs ?? 0;
            clients.value = data.clients ?? 0;
            vehicules.value = data.vehicules ?? 0;
        } catch (e) {
            error.value = e.message || 'Impossible de charger les statistiques';
        } finally {
            loading.value = false;
        }
    }

    onMounted(fetchStatistics);

    return {
        revenusTotaux,
        contratsActifs,
        clients,
        vehicules,
        loading,
        error,
        refresh: fetchStatistics,
    };
}
