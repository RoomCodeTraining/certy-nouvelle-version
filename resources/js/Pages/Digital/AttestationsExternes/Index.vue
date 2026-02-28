<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted, onUnmounted } from "vue";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import DataTable from "@/Components/DataTable.vue";
import { formatDate } from "@/utils/formatDate";
import { route } from "@/route";

const props = defineProps({
    attestations: [Array, Object],
    links: Object,
    meta: Object,
    error: String,
    filters: Object,
});

const page = usePage();
const displayError = ref(null);
const errorToShow = computed(
    () => displayError.value ?? props.error ?? page.props.flash?.error ?? null,
);

const list = Array.isArray(props.attestations)
    ? props.attestations
    : Array.isArray(props.attestations?.data)
      ? props.attestations.data
      : [];

const dateFrom = computed(() => props.filters?.date_from ?? "");
const dateTo = computed(() => props.filters?.date_to ?? "");
const perPage = computed(() => Number(props.filters?.per_page) || 10);
const perPageOptions = [10, 25, 50];

function buildQuery(overrides = {}) {
    const f = { ...(props.filters ?? {}), ...overrides };
    const q = {};
    if (f.per_page) q.per_page = f.per_page;
    if (f.date_from) q.date_from = f.date_from;
    if (f.date_to) q.date_to = f.date_to;
    if (f.cursor) q.cursor = f.cursor;
    return new URLSearchParams(q).toString();
}

const refreshUrl = computed(() => {
    const q = buildQuery({ cursor: undefined });
    const base = route("digital.attestations-externes");
    return q ? `${base}?${q}` : base;
});

const nextUrl = computed(() => {
    const cursor = props.meta?.next_cursor;
    if (!cursor) return null;
    const q = new URLSearchParams(props.filters ?? {});
    q.set("cursor", cursor);
    return `${route("digital.attestations-externes")}?${q.toString()}`;
});

const prevUrl = computed(() => {
    const cursor = props.meta?.prev_cursor;
    if (!cursor) return null;
    const q = new URLSearchParams(props.filters ?? {});
    q.set("cursor", cursor);
    return `${route("digital.attestations-externes")}?${q.toString()}`;
});

const hasNext = computed(() => !!props.meta?.next_cursor);
const hasPrev = computed(() => !!props.meta?.prev_cursor);

function urlForPerPage(n) {
    const q = buildQuery({ per_page: n, cursor: undefined });
    const base = route("digital.attestations-externes");
    return q ? `${base}?${q}` : base;
}

const breadcrumbs = [
    { label: "Tableau de bord", href: "/dashboard" },
    { label: "Digital", href: "/digital/attestations" },
    { label: "Reporting" },
];

function getAttestationRef(row) {
    return (
        row.reference ??
        row.reference_id ??
        row.certificate_number ??
        row.id ??
        null
    );
}

function formatPeriod(row) {
    const start =
        row.starts_at ??
        row.start_date ??
        row.period_start ??
        row.effective_date;
    const end =
        row.ends_at ?? row.end_date ?? row.period_end ?? row.expiry_date;
    if (!start && !end) return "—";
    return [formatDate(start), formatDate(end)].filter(Boolean).join(" → ");
}

const columns = [
    {
        key: "date_emission",
        label: "Date d'émission",
        getValue: (row) =>
            formatDate(row.printed_at ?? row.issued_at ?? row.created_at),
    },
    {
        key: "reference",
        label: "Référence",
        getValue: (row) => row.reference ?? row.id ?? "—",
    },
    {
        key: "assure",
        label: "Assuré",
        getValue: (row) =>
            row.insured_name ??
            row.assure ??
            row.insured ??
            row.policy_holder ??
            "—",
    },
    {
        key: "plaque",
        label: "Plaque",
        getValue: (row) =>
            row.licence_plate ??
            row.plaque ??
            row.registration_number ??
            row.immat ??
            "—",
    },
    {
        key: "periode",
        label: "Période",
        getValue: (row) => formatPeriod(row),
    },
];

const showViewModal = ref(false);
const viewReference = ref(null);
const viewRow = ref(null);
const viewSource = ref(null);
const viewLoading = ref(true);
const viewError = ref(false);
let viewLoadTimeoutId = null;

function viewUrlFor(ref, source) {
    if (!ref) return null;
    const url = route("digital.attestations.view", ref);
    return source ? `${url}?source=${encodeURIComponent(source)}` : url;
}
function downloadUrlFor(ref, source) {
    if (!ref) return null;
    const url = route("digital.attestations.download", ref);
    return source ? `${url}?source=${encodeURIComponent(source)}` : url;
}

const viewUrl = computed(() =>
    viewReference.value
        ? viewUrlFor(viewReference.value, viewSource.value)
        : null,
);

const downloadingKey = ref(null);
function isDownloading(ref, source) {
    if (!ref || !source) return false;
    return downloadingKey.value === `${ref}|${source}`;
}
async function handleDownload(ref, source) {
    const url = downloadUrlFor(ref, source);
    if (!url) return;
    if (source === "autres") {
        const key = `${ref}|${source}`;
        downloadingKey.value = key;
        displayError.value = null;
        try {
            const res = await fetch(
                route("digital.attestations.download_url", ref) +
                    "?source=autres",
                { credentials: "same-origin", method: "GET" },
            );
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.url && typeof data.url === "string") {
                window.open(data.url, "_blank");
                return;
            }
            if (
                res.status === 404 &&
                data.message &&
                typeof data.message === "string"
            ) {
                displayError.value = data.message;
                return;
            }
        } catch (_) {
            /* ignore */
        } finally {
            if (downloadingKey.value === key) downloadingKey.value = null;
        }
        window.open(url, "_blank");
        return;
    }
    const key = `${ref}|${source}`;
    downloadingKey.value = key;
    try {
        const res = await fetch(url, { credentials: "same-origin", method: "GET" });
        if (!res.ok) throw new Error(res.statusText);
        const blob = await res.blob();
        const disposition = res.headers.get("Content-Disposition");
        const match =
            disposition &&
            disposition.match(/filename\*?=(?:UTF-8'')?["']?([^"'\s;]+)["']?/i);
        const filename = match ? match[1].trim() : `attestation-${ref}.pdf`;
        const a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = filename;
        a.click();
        URL.revokeObjectURL(a.href);
    } catch (_) {
        window.open(url, "_blank");
    } finally {
        if (downloadingKey.value === key) downloadingKey.value = null;
    }
}

function openViewModal(row, source) {
    const ref = getAttestationRef(row);
    if (ref) {
        viewReference.value = ref;
        viewRow.value = row;
        viewSource.value = source || null;
        viewLoading.value = true;
        viewError.value = false;
        showViewModal.value = true;
        if (viewLoadTimeoutId) clearTimeout(viewLoadTimeoutId);
        viewLoadTimeoutId = setTimeout(() => {
            if (viewLoading.value) {
                viewLoading.value = false;
                viewError.value = true;
            }
            viewLoadTimeoutId = null;
        }, 25000);
    }
}

function closeViewModal() {
    if (viewLoadTimeoutId) {
        clearTimeout(viewLoadTimeoutId);
        viewLoadTimeoutId = null;
    }
    showViewModal.value = false;
    viewReference.value = null;
    viewRow.value = null;
    viewSource.value = null;
    viewLoading.value = true;
    viewError.value = false;
}

function onViewIframeLoad() {
    if (viewLoadTimeoutId) {
        clearTimeout(viewLoadTimeoutId);
        viewLoadTimeoutId = null;
    }
    viewLoading.value = false;
}

function onViewIframeError() {
    if (viewLoadTimeoutId) {
        clearTimeout(viewLoadTimeoutId);
        viewLoadTimeoutId = null;
    }
    viewLoading.value = false;
    viewError.value = true;
}

const actionMenuOpen = ref(null);
const actionMenuDirection = ref({});

function getActionRowKey(row) {
    return row.reference ?? row.id ?? row.reference_id;
}

function getActionMenuDirection(row) {
    const key = getActionRowKey(row);
    return actionMenuDirection.value[key] || "down";
}

function getActionMenuPositionClass(row) {
    return getActionMenuDirection(row) === "up"
        ? "bottom-full mb-2 origin-bottom-right"
        : "top-full mt-2 origin-top-right";
}

function toggleActionMenu(row, event) {
    const key = getActionRowKey(row);
    if (actionMenuOpen.value === key) {
        actionMenuOpen.value = null;
        return;
    }
    if (event?.currentTarget && typeof window !== "undefined") {
        const triggerRect = event.currentTarget.getBoundingClientRect();
        const viewportHeight =
            window.innerHeight || document.documentElement.clientHeight || 0;
        const estimatedMenuHeight = 260;
        const spaceBelow = viewportHeight - triggerRect.bottom;
        const spaceAbove = triggerRect.top;
        const direction =
            spaceBelow < estimatedMenuHeight && spaceAbove > spaceBelow
                ? "up"
                : "down";
        actionMenuDirection.value = {
            ...actionMenuDirection.value,
            [key]: direction,
        };
    } else {
        actionMenuDirection.value = {
            ...actionMenuDirection.value,
            [key]: "down",
        };
    }
    actionMenuOpen.value = key;
}

function isActionMenuOpen(row) {
    const key = getActionRowKey(row);
    return actionMenuOpen.value === key;
}

function onActionMenuClickOutside(e) {
    if (
        actionMenuOpen.value !== null &&
        !e.target.closest?.("[data-attestation-actions]")
    ) {
        actionMenuOpen.value = null;
    }
}

onMounted(() => document.addEventListener("click", onActionMenuClickOutside));
onUnmounted(() =>
    document.removeEventListener("click", onActionMenuClickOutside),
);

const isSearching = ref(false);

function handleSearch(e) {
    e.preventDefault();
    const form = e.target;
    const df = form.date_from?.value ?? "";
    const dt = form.date_to?.value ?? "";
    const params = new URLSearchParams(props.filters ?? {});
    params.set("date_from", df);
    params.set("date_to", dt);
    params.delete("cursor");
    if (!params.get("per_page")) params.set("per_page", String(perPage.value));
    const url = route("digital.attestations-externes");
    const qs = params.toString();
    isSearching.value = true;
    router.get(qs ? `${url}?${qs}` : url, {}, {
        preserveState: true,
        onFinish: () => {
            isSearching.value = false;
        },
    });
}

const isExporting = ref(false);

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (dateFrom.value) params.set("date_from", dateFrom.value);
    if (dateTo.value) params.set("date_to", dateTo.value);
    if (perPage.value) params.set("per_page", String(perPage.value));
    const base = route("digital.attestations-externes.export");
    const qs = params.toString();
    return qs ? `${base}?${qs}` : base;
});

function handleExport() {
    if (isExporting.value) return;
    isExporting.value = true;
    const link = document.createElement("a");
    link.href = exportUrl.value;
    link.target = "_blank";
    link.rel = "noopener";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(() => {
        isExporting.value = false;
    }, 2000);
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Reporting" />
        </template>

        <p class="text-sm text-slate-600 mb-4">
            Consulter les attestations externes sur une période donnée (format
            Année-mois-jour).
        </p>

        <div
            v-if="errorToShow"
            class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm mb-6 flex items-start justify-between gap-3"
        >
            <span>{{ errorToShow }}</span>
            <button
                type="button"
                class="shrink-0 text-amber-600 hover:text-amber-800"
                aria-label="Fermer"
                @click="displayError = null"
            >
                <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <div
                class="px-4 py-3 border-b border-slate-200 flex flex-wrap items-center gap-4 bg-slate-50"
            >
                <h2 class="text-sm font-semibold text-slate-900">
                    Filtrer par période
                </h2>
                <form
                    @submit="handleSearch"
                    class="flex flex-wrap items-center gap-3"
                >
                    <div class="flex items-center gap-2">
                        <label for="date_from" class="text-xs font-medium text-slate-600"
                            >Du</label
                        >
                        <input
                            id="date_from"
                            name="date_from"
                            type="date"
                            :value="dateFrom"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="date_to" class="text-xs font-medium text-slate-600"
                            >Au</label
                        >
                        <input
                            id="date_to"
                            name="date_to"
                            type="date"
                            :value="dateTo"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
                        />
                    </div>
                    <button
                        type="submit"
                        :disabled="isSearching"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 disabled:opacity-70 disabled:cursor-wait"
                    >
                        <svg
                            v-if="isSearching"
                            class="w-4 h-4 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            />
                        </svg>
                        <span>{{ isSearching ? "Recherche…" : "Rechercher" }}</span>
                    </button>
                </form>
                <button
                    type="button"
                    :disabled="isExporting || !dateFrom || !dateTo"
                    @click="handleExport"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <svg
                        v-if="isExporting"
                        class="w-4 h-4 animate-spin text-slate-500"
                        fill="none"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        />
                    </svg>
                    <span>Exporter Excel</span>
                </button>
                <p
                    v-if="!dateFrom && !dateTo"
                    class="text-xs text-slate-500 w-full"
                >
                    Sélectionnez une période (du / au) pour afficher les
                    attestations externes.
                </p>
            </div>

            <div class="md:hidden divide-y divide-slate-100">
                <div
                    v-for="row in list"
                    :key="row.reference ?? row.id ?? row.reference_id"
                    class="p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-mono text-sm font-medium text-slate-900">
                                {{ row.reference ?? "—" }}
                            </p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{
                                    formatDate(
                                        row.printed_at ??
                                            row.issued_at ??
                                            row.created_at,
                                    )
                                }}
                            </p>
                            <p class="text-sm text-slate-700 mt-1">
                                {{ row.insured_name ?? row.assure ?? "—" }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ row.licence_plate ?? row.plaque ?? "—" }}
                            </p>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ formatPeriod(row) }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="getAttestationRef(row)"
                        class="mt-3 pt-3 flex flex-wrap gap-2"
                        data-attestation-actions
                    >
                        <button
                            type="button"
                            @click="openViewModal(row, 'cima')"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
                        >
                            CIMA Consulter
                        </button>
                        <button
                            type="button"
                            :disabled="isDownloading(getAttestationRef(row), 'cima')"
                            @click="handleDownload(getAttestationRef(row), 'cima')"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-70"
                        >
                            CIMA Télécharger
                        </button>
                        <button
                            type="button"
                            @click="openViewModal(row, 'autres')"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
                        >
                            Autres Consulter
                        </button>
                        <button
                            type="button"
                            :disabled="isDownloading(getAttestationRef(row), 'autres')"
                            @click="handleDownload(getAttestationRef(row), 'autres')"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-70"
                        >
                            Autres Télécharger
                        </button>
                    </div>
                </div>
                <div
                    v-if="!list.length"
                    class="py-10 px-4 text-center text-slate-500 text-sm"
                >
                    {{
                        dateFrom || dateTo
                            ? "Aucune attestation pour cette période."
                            : "Sélectionnez une période pour afficher les attestations."
                    }}
                </div>
            </div>

            <div class="hidden md:block">
                <DataTable
                    :data="list"
                    :columns="columns"
                    :row-key="(row) => row.reference ?? row.id ?? row.reference_id"
                    :empty-message="
                        dateFrom || dateTo
                            ? 'Aucune attestation pour cette période.'
                            : 'Sélectionnez une période pour afficher les attestations.'
                    "
                >
                    <template #actions="{ row }">
                        <template v-if="getAttestationRef(row)">
                            <div
                                class="relative flex items-center justify-end"
                                data-attestation-actions
                            >
                                <button
                                    type="button"
                                    @click.stop="toggleActionMenu(row, $event)"
                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100"
                                    :aria-expanded="isActionMenuOpen(row)"
                                    aria-haspopup="true"
                                >
                                    <span>Actions</span>
                                    <svg
                                        class="w-4 h-4 shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 9l-7 7-7-7"
                                        />
                                    </svg>
                                </button>
                                <Transition
                                    enter-active-class="transition ease-out duration-150"
                                    enter-from-class="opacity-0 scale-95"
                                    enter-to-class="opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100 scale-100"
                                    leave-to-class="opacity-0 scale-95"
                                >
                                    <div
                                        v-show="isActionMenuOpen(row)"
                                        :class="[
                                            'absolute right-0 z-20 w-60 rounded-xl border border-slate-200 bg-white shadow-xl overflow-hidden',
                                            getActionMenuPositionClass(row),
                                        ]"
                                    >
                                        <div
                                            class="p-1 space-y-0.5 bg-slate-50 border-b border-slate-100"
                                        >
                                            <button
                                                type="button"
                                                @click="openViewModal(row, 'cima')"
                                                class="w-full flex items-center gap-3 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 rounded-lg text-left"
                                            >
                                                CIMA Consulter
                                            </button>
                                            <button
                                                type="button"
                                                :disabled="
                                                    isDownloading(
                                                        getAttestationRef(row),
                                                        'cima',
                                                    )
                                                "
                                                @click="
                                                    handleDownload(
                                                        getAttestationRef(row),
                                                        'cima',
                                                    )
                                                "
                                                class="w-full flex items-center gap-3 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 rounded-lg text-left disabled:opacity-70"
                                            >
                                                CIMA Télécharger
                                            </button>
                                            <button
                                                type="button"
                                                @click="openViewModal(row, 'autres')"
                                                class="w-full flex items-center gap-3 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 rounded-lg text-left"
                                            >
                                                Autres Consulter
                                            </button>
                                            <button
                                                type="button"
                                                :disabled="
                                                    isDownloading(
                                                        getAttestationRef(row),
                                                        'autres',
                                                    )
                                                "
                                                @click="
                                                    handleDownload(
                                                        getAttestationRef(row),
                                                        'autres',
                                                    )
                                                "
                                                class="w-full flex items-center gap-3 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 rounded-lg text-left disabled:opacity-70"
                                            >
                                                Autres Télécharger
                                            </button>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </template>
                    </template>
                </DataTable>
            </div>

            <div
                class="px-4 py-3 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 bg-slate-50"
            >
                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-600">
                        {{ list.length }} attestation(s) sur cette page
                        <template v-if="meta?.total != null">
                            — {{ meta.total }} au total
                        </template>
                    </span>
                    <template v-if="dateFrom || dateTo">
                        <span class="text-slate-300">|</span>
                        <span class="text-sm text-slate-600">Par page</span>
                        <select
                            :value="perPage"
                            @change="
                                (e) =>
                                    router.visit(
                                        urlForPerPage(Number(e.target.value)),
                                    )
                            "
                            class="rounded-lg border border-slate-200 px-2 py-1.5 text-sm text-slate-700 focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
                        >
                            <option
                                v-for="n in perPageOptions"
                                :key="n"
                                :value="n"
                            >
                                {{ n }}
                            </option>
                        </select>
                    </template>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="refreshUrl"
                        class="text-sm text-slate-600 hover:text-slate-900 font-medium"
                    >
                        Actualiser
                    </Link>
                    <template v-if="hasPrev || hasNext">
                        <span class="text-slate-300">|</span>
                        <Link
                            v-if="prevUrl"
                            :href="prevUrl"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 px-2 py-1 rounded hover:bg-slate-100"
                        >
                            ← Précédent
                        </Link>
                        <Link
                            v-if="nextUrl"
                            :href="nextUrl"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 px-2 py-1 rounded hover:bg-slate-100"
                        >
                            Suivant →
                        </Link>
                    </template>
                </div>
            </div>
        </div>

        <div
            v-if="showViewModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-title"
        >
            <div
                class="relative w-full max-w-4xl max-h-[90vh] bg-white rounded-xl shadow-xl overflow-hidden flex flex-col"
            >
                <div
                    class="flex items-center justify-between px-4 py-3 border-b border-slate-200 bg-slate-50"
                >
                    <h2 id="modal-title" class="text-sm font-semibold text-slate-900">
                        Attestation {{ viewReference }}
                    </h2>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-500 hover:bg-slate-200"
                        aria-label="Fermer"
                        @click="closeViewModal"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 min-h-0 overflow-auto p-4 bg-slate-100">
                    <div
                        v-if="viewLoading"
                        class="flex items-center justify-center py-16 text-slate-500"
                    >
                        Chargement…
                    </div>
                    <div
                        v-else-if="viewError"
                        class="flex flex-col items-center justify-center py-16 text-amber-600"
                    >
                        <p>Impossible de charger l'attestation.</p>
                    </div>
                    <iframe
                        v-else-if="viewUrl"
                        :src="viewUrl"
                        class="w-full h-[70vh] min-h-[400px] rounded-lg bg-white border border-slate-200"
                        title="Attestation"
                        @load="onViewIframeLoad"
                        @error="onViewIframeError"
                    />
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
