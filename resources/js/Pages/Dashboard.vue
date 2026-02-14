<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const page = usePage();
const subscription = page.props.auth?.subscription;

const props = defineProps({
    organization: Object,
    stats: {
        type: Object,
        default: () => ({
            documentCount: 0,
            totalSize: 0,
            documentsPerDay: [],
            documentsByType: [],
            assistantExchangesCount: 0,
        }),
    },
    recentDocuments: {
        type: Array,
        default: () => [],
    },
});

const formatSize = (bytes) => {
    if (!bytes) return '0 o';
    if (bytes < 1024) return bytes + ' o';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
    return (bytes / (1024 * 1024)).toFixed(1) + ' Mo';
};

const maxDayCount = () => {
    const counts = props.stats?.documentsPerDay?.map((d) => d.count) ?? [];
    return Math.max(1, ...counts);
};

const CHART_COLORS = ['#0d9488', '#2563eb', '#d97706', '#dc2626', '#7c3aed', '#0891b2'];

const pieSegments = () => {
    const types = props.stats?.documentsByType ?? [];
    const total = types.reduce((s, t) => s + t.count, 0) || 1;
    let startAngle = -90;
    return types.map((t, i) => {
        const angle = (t.count / total) * 360;
        const endAngle = startAngle + angle;
        const r = 40;
        const cx = 50;
        const cy = 50;
        const rad = (deg) => (deg * Math.PI) / 180;
        const x1 = cx + r * Math.cos(rad(startAngle));
        const y1 = cy + r * Math.sin(rad(startAngle));
        const x2 = cx + r * Math.cos(rad(endAngle));
        const y2 = cy + r * Math.sin(rad(endAngle));
        const large = angle > 180 ? 1 : 0;
        const d = `M ${cx} ${cy} L ${x1} ${y1} A ${r} ${r} 0 ${large} 1 ${x2} ${y2} Z`;
        startAngle = endAngle;
        return { ...t, d, color: CHART_COLORS[i % CHART_COLORS.length] };
    });
};

// Activity chart: bar heights (0–100) for 7 days
const activityDays = () => props.stats?.documentsPerDay ?? [];
const activityMax = () => Math.max(1, ...activityDays().map((d) => d.count));
const barHeight = (count) => (count / activityMax()) * 100;
</script>

<template>
    <DashboardLayout>
        <template #header>
            <h1 class="text-sm font-medium text-slate-900">Tableau de bord</h1>
        </template>

        <div class="flex-1 min-h-full flex flex-col w-full">
            <!-- Plan & quotas (compact) -->
            <div v-if="subscription" class="rounded-lg border border-slate-200 bg-slate-50/80 p-3 mb-6 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <span class="text-slate-600">Plan <span class="font-medium text-slate-900">{{ subscription.plan_name }}</span></span>
                    <span class="text-slate-500">Documents : {{ (subscription.documents_limit ?? 0) - (subscription.documents_remaining ?? subscription.documents_limit ?? 0) }} / {{ subscription.documents_limit }}</span>
                    <span class="text-slate-500">Assistant : {{ subscription.assistant_calls_remaining }} / {{ subscription.assistant_calls_limit }} appels</span>
                </div>
                <Link href="/settings/subscription" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Changer de plan →</Link>
            </div>

            <!-- KPI cards (Nuxt UI style: icon top center, label, value, trend) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="rounded-xl border border-slate-200 bg-white p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500 mb-0.5">Documents</p>
                    <p class="text-2xl font-bold text-slate-900">{{ stats?.documentCount ?? 0 }}</p>
                    <p class="text-sm text-emerald-600 font-medium mt-1">+{{ (stats?.documentsPerDay ?? []).reduce((s, d) => s + d.count, 0) || 0 }} ce mois</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500 mb-0.5">Espace utilisé</p>
                    <p class="text-2xl font-bold text-slate-900">{{ formatSize(stats?.totalSize ?? 0) }}</p>
                    <p class="text-sm text-slate-400 font-medium mt-1">—</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-6 text-center">
                    <div class="w-12 h-12 rounded-full bg-emerald-500/10 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500 mb-0.5">Réponses IA</p>
                    <p class="text-2xl font-bold text-slate-900">{{ stats?.assistantExchangesCount ?? 0 }}</p>
                    <p class="text-sm text-emerald-600 font-medium mt-1">ce mois</p>
                </div>
            </div>

            <!-- Summary block (Nuxt UI hero number) -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 mb-6">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Documents</p>
                <p class="text-3xl sm:text-4xl font-bold text-slate-900">{{ stats?.documentCount ?? 0 }}</p>
                <p class="text-sm text-slate-500 mt-1">Total dans votre base</p>
            </div>

            <!-- Activité 7 jours — bar chart moderne -->
            <div class="rounded-xl border border-slate-200/80 bg-white shadow-sm overflow-hidden mb-6">
                <div class="px-6 pt-5 pb-1">
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wider">Documents par jour</p>
                    <h2 class="text-lg font-semibold text-slate-900 mt-0.5">Activité · 7 derniers jours</h2>
                </div>
                <div class="px-6 pb-6 pt-4">
                    <div v-if="activityDays().length === 0" class="flex flex-col items-center justify-center h-40 rounded-lg bg-slate-50/80 border border-slate-100">
                        <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <p class="text-sm text-slate-500">Aucune donnée sur cette période</p>
                    </div>
                    <div v-else class="flex items-stretch justify-between gap-3 h-40">
                        <template v-for="(day, i) in activityDays()" :key="i">
                            <div class="flex-1 flex flex-col items-center min-w-0">
                                <div class="flex-1 w-full flex flex-col justify-end items-center min-h-0">
                                    <div
                                        class="w-full max-w-8 rounded-t-md bg-gradient-to-t from-emerald-600 to-emerald-500 transition-all duration-300 hover:from-emerald-500 hover:to-emerald-400"
                                        :style="{ height: barHeight(day.count) + '%', minHeight: day.count ? '4px' : '0' }"
                                        :title="day.label + ' : ' + day.count + ' doc.'"
                                    />
                                </div>
                                <span class="mt-2 text-[11px] font-medium text-slate-400 truncate w-full text-center">{{ day.label }}</span>
                            </div>
                        </template>
                    </div>
                    <div v-if="activityDays().length > 0" class="mt-3 pt-3 border-t border-slate-100 flex justify-between text-xs text-slate-400">
                        <span>Total : {{ activityDays().reduce((s, d) => s + d.count, 0) }} document(s)</span>
                        <span>Max : {{ activityMax() }} / jour</span>
                    </div>
                </div>
            </div>

            <!-- Recent activity table (Nuxt UI style) -->
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h2 class="text-base font-semibold text-slate-900">Activité récente</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50">
                                <th class="text-left py-3 px-6 font-medium text-slate-500">ID</th>
                                <th class="text-left py-3 px-6 font-medium text-slate-500">Date</th>
                                <th class="text-left py-3 px-6 font-medium text-slate-500">Type</th>
                                <th class="text-left py-3 px-6 font-medium text-slate-500">Document</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="doc in recentDocuments"
                                :key="doc.id"
                                class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors"
                            >
                                <td class="py-3 px-6 font-medium text-slate-700">#{{ doc.id }}</td>
                                <td class="py-3 px-6 text-slate-600">{{ doc.date }}</td>
                                <td class="py-3 px-6">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ doc.type }}
                                    </span>
                                </td>
                                <td class="py-3 px-6 text-slate-600 truncate max-w-[200px]">{{ doc.title }}</td>
                            </tr>
                            <tr v-if="recentDocuments.length === 0">
                                <td colspan="4" class="py-8 px-6 text-center text-slate-500">Aucune activité récente</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
