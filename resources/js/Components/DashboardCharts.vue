<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import {
    Chart,
    CategoryScale,
    LinearScale,
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    Filler,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(
    CategoryScale,
    LinearScale,
    BarController,
    BarElement,
    LineController,
    LineElement,
    PointElement,
    Filler,
    Tooltip,
    Legend
);

const props = defineProps({
    labels: { type: Array, default: () => [] },
    contratsParMois: { type: Array, default: () => [] },
    clientsParMois: { type: Array, default: () => [] },
    revenusParMois: { type: Array, default: () => [] },
    /** 'mini' = un seul graphique (contrats), 'full' = les 3 graphiques */
    mode: { type: String, default: 'full' },
});

const chartContratsRef = ref(null);
const chartClientsRef = ref(null);
const chartRevenusRef = ref(null);
let chartContrats = null;
let chartClients = null;
let chartRevenus = null;

const defaultOptions = {
    responsive: true,
    maintainAspectRatio: false,
    layout: { padding: { top: 8, right: 8, bottom: 4, left: 4 } },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            padding: 10,
            cornerRadius: 8,
            titleFont: { size: 12 },
            bodyFont: { size: 13 },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { maxRotation: 45, font: { size: 11 }, maxTicksLimit: 8 },
        },
        y: {
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.06)' },
            ticks: { font: { size: 11 }, padding: 8 },
        },
    },
};

function formatRevenusAxis(value) {
    if (value >= 1e6) return (value / 1e6).toFixed(1) + ' M';
    if (value >= 1e3) return (value / 1e3).toFixed(0) + ' k';
    return String(value);
}

function formatRevenusTooltip(value) {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value) + ' F CFA';
}

function buildCharts() {
    const destroy = (c) => {
        if (c) {
            c.destroy();
        }
    };
    destroy(chartContrats);
    destroy(chartClients);
    destroy(chartRevenus);
    chartContrats = null;
    chartClients = null;
    chartRevenus = null;

    if (!props.labels?.length) return;

    if (chartContratsRef.value) {
        chartContrats = new Chart(chartContratsRef.value, {
            type: 'bar',
            data: {
                labels: props.labels,
                datasets: [{
                    label: 'Contrats',
                    data: props.contratsParMois,
                    backgroundColor: 'rgba(14, 165, 233, 0.65)',
                    borderColor: 'rgb(14, 165, 233)',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false,
                }],
            },
            options: {
                ...defaultOptions,
                datasets: { bar: { barPercentage: 0.7, categoryPercentage: 0.85 } },
                plugins: {
                    ...defaultOptions.plugins,
                    tooltip: {
                        ...defaultOptions.plugins.tooltip,
                        callbacks: { label: (ctx) => ctx.parsed.y === 1 ? '1 contrat' : `${ctx.parsed.y} contrats` },
                    },
                },
            },
        });
    }

    if (props.mode === 'full' && chartClientsRef.value) {
        chartClients = new Chart(chartClientsRef.value, {
            type: 'bar',
            data: {
                labels: props.labels,
                datasets: [{
                    label: 'Nouveaux clients',
                    data: props.clientsParMois,
                    backgroundColor: 'rgba(16, 185, 129, 0.75)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false,
                }],
            },
            options: {
                ...defaultOptions,
                datasets: {
                    bar: { barPercentage: 0.7, categoryPercentage: 0.85 },
                },
                plugins: {
                    ...defaultOptions.plugins,
                    tooltip: {
                        ...defaultOptions.plugins.tooltip,
                        callbacks: {
                            label: (ctx) => ctx.parsed.y === 1 ? '1 nouveau client' : `${ctx.parsed.y} nouveaux clients`,
                        },
                    },
                },
            },
        });
    }

    if (props.mode === 'full' && chartRevenusRef.value) {
        const ctx = chartRevenusRef.value.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 200);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.35)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.02)');

        chartRevenus = new Chart(chartRevenusRef.value, {
            type: 'line',
            data: {
                labels: props.labels,
                datasets: [{
                    label: 'Revenus',
                    data: props.revenusParMois,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: 'rgb(59, 130, 246)',
                    pointBorderWidth: 1.5,
                }],
            },
            options: {
                ...defaultOptions,
                plugins: {
                    ...defaultOptions.plugins,
                    tooltip: {
                        ...defaultOptions.plugins.tooltip,
                        callbacks: {
                            label: (ctx) => formatRevenusTooltip(ctx.parsed.y),
                        },
                    },
                },
                scales: {
                    ...defaultOptions.scales,
                    y: {
                        ...defaultOptions.scales.y,
                        ticks: {
                            ...defaultOptions.scales.y.ticks,
                            callback: (value) => formatRevenusAxis(value),
                        },
                    },
                },
            },
        });
    }
}

function destroyAll() {
    [chartContrats, chartClients, chartRevenus].forEach((c) => {
        if (c) {
            c.destroy();
        }
    });
    chartContrats = null;
    chartClients = null;
    chartRevenus = null;
}

watch(
    () => [props.labels, props.contratsParMois, props.clientsParMois, props.revenusParMois, props.mode],
    () => buildCharts(),
    { deep: true }
);

onMounted(() => buildCharts());
onBeforeUnmount(destroyAll);
</script>

<template>
    <div class="space-y-6">
        <!-- Contrats par mois -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Contrats</h3>
            <p class="text-xs text-slate-500 mt-0.5 mb-3">{{ mode === 'full' ? 'Évolution mensuelle sur 12 mois' : 'Par mois' }}</p>
            <div class="h-[220px] sm:h-[200px]">
                <canvas ref="chartContratsRef"></canvas>
            </div>
        </div>

        <template v-if="mode === 'full'">
            <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Nouveaux clients</h3>
                <p class="text-xs text-slate-500 mt-0.5 mb-3">Évolution mensuelle sur 12 mois</p>
                <div class="h-[240px]">
                    <canvas ref="chartClientsRef"></canvas>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Revenus</h3>
                <p class="text-xs text-slate-500 mt-0.5 mb-3">Cumul par mois (F CFA)</p>
                <div class="h-[240px]">
                    <canvas ref="chartRevenusRef"></canvas>
                </div>
            </div>
        </template>
    </div>
</template>
