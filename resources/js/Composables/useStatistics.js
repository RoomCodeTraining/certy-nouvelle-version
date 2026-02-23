import { ref, onMounted } from 'vue';

/**
 * Charge les KPIs et données graphiques du tableau de bord depuis GET /api/statistics.
 * Expose : revenusTotaux, contratsActifs, clients, vehicules, chartLabels, chartContratsParMois,
 * chartClientsParMois, chartRevenusParMois, loading, error, refresh.
 */
export function useStatistics() {
    const revenusTotaux = ref(0);
    const contratsActifs = ref(0);
    const clients = ref(0);
    const vehicules = ref(0);
    const chartLabels = ref([]);
    const chartContratsParMois = ref([]);
    const chartClientsParMois = ref([]);
    const chartRevenusParMois = ref([]);
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
            chartLabels.value = data.chart_labels ?? [];
            chartContratsParMois.value = data.chart_contrats_par_mois ?? [];
            chartClientsParMois.value = data.chart_clients_par_mois ?? [];
            chartRevenusParMois.value = data.chart_revenus_par_mois ?? [];
        } catch (e) {
            const msg = e.message || '';
            if (msg.includes('401') || msg.includes('Unauthenticated')) {
                error.value = 'Session expirée. Veuillez vous reconnecter.';
            } else if (msg.includes('403') || msg.includes('Forbidden')) {
                error.value = "Vous n'avez pas les droits pour afficher ces statistiques.";
            } else if (msg.includes('500') || msg.includes('Server Error')) {
                error.value = 'Erreur serveur. Veuillez réessayer plus tard.';
            } else if (msg.includes(' réseau') || msg.includes('Failed to fetch') || msg.includes('NetworkError')) {
                error.value = 'Erreur de connexion. Vérifiez votre accès internet.';
            } else {
                error.value = msg || 'Impossible de charger les statistiques.';
            }
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
        chartLabels,
        chartContratsParMois,
        chartClientsParMois,
        chartRevenusParMois,
        loading,
        error,
        refresh: fetchStatistics,
    };
}
