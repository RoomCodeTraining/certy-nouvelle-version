<script setup>
import { Link, router } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import DataTable from "@/Components/DataTable.vue";
import DataTableAction from "@/Components/DataTableAction.vue";
import { formatDate } from "@/utils/formatDate";
import { route } from "@/route";

const props = defineProps({
    attestations: [Array, Object],
    links: Object,
    meta: Object,
    error: String,
    filters: Object,
});

const list = Array.isArray(props.attestations)
    ? props.attestations
    : Array.isArray(props.attestations?.data)
      ? props.attestations.data
      : [];

const perPage = computed(() => Number(props.filters?.per_page) || 10);
const perPageOptions = [10, 25, 50];

/** Query params de base (per_page, printed_at, search) sans cursor. */
function buildQuery(overrides = {}) {
    const f = { ...(props.filters ?? {}), ...overrides };
    const q = {};
    if (f.per_page) q.per_page = f.per_page;
    if (f.printed_at) q.printed_at = f.printed_at;
    if (f.search != null && String(f.search).trim() !== "")
        q.search = String(f.search).trim();
    return new URLSearchParams(q).toString();
}

/** Lien première page (Actualiser / changement per_page). */
const refreshUrl = computed(() => {
    const q = buildQuery();
    return q ? `/digital/attestations?${q}` : "/digital/attestations";
});

/** Lien page suivante (cursor). */
const nextUrl = computed(() => {
    const cursor = props.meta?.next_cursor;
    if (!cursor) return null;
    const q = new URLSearchParams(props.filters ?? {});
    q.set("cursor", cursor);
    return `/digital/attestations?${q.toString()}`;
});

/** Lien page précédente (cursor). */
const prevUrl = computed(() => {
    const cursor = props.meta?.prev_cursor;
    if (!cursor) return null;
    const q = new URLSearchParams(props.filters ?? {});
    q.set("cursor", cursor);
    return `/digital/attestations?${q.toString()}`;
});

const hasNext = computed(() => !!props.meta?.next_cursor);
const hasPrev = computed(() => !!props.meta?.prev_cursor);

/** URL pour changer le nombre par page (réinitialise le cursor). */
function urlForPerPage(n) {
    const q = buildQuery({ per_page: n });
    return `/digital/attestations?${q}`;
}

/** Modal de visualisation attestation (PDF ou image) */
const showViewModal = ref(false);
const viewReference = ref(null);
const viewRow = ref(null);
const viewSource = ref(null); // 'cima' | 'autres'
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
const downloadUrl = computed(() =>
    viewReference.value
        ? downloadUrlFor(viewReference.value, viewSource.value)
        : null,
);

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

/** Dropdown Actions par row (CIMA / Autres) */
const actionMenuOpen = ref(null);
function toggleActionMenu(row) {
    const key = row.reference ?? row.id ?? row.reference_id;
    actionMenuOpen.value = actionMenuOpen.value === key ? null : key;
}
function isActionMenuOpen(row) {
    const key = row.reference ?? row.id ?? row.reference_id;
    return actionMenuOpen.value === key;
}
function closeActionMenu() {
    actionMenuOpen.value = null;
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

function onActionMenuClickOutside(e) {
    if (actionMenuOpen.value !== null && !e.target.closest?.('[data-attestation-actions]')) {
        closeActionMenu();
    }
}
onMounted(() => document.addEventListener("click", onActionMenuClickOutside));
onUnmounted(() => document.removeEventListener("click", onActionMenuClickOutside));

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

// Fermer au Escape et bloquer le scroll du body quand le modal est ouvert
watch(showViewModal, (open) => {
    if (open) {
        document.body.style.overflow = "hidden";
        const onEscape = (e) => {
            if (e.key === "Escape") closeViewModal();
        };
        document.addEventListener("keydown", onEscape);
        return () => {
            document.removeEventListener("keydown", onEscape);
            document.body.style.overflow = "";
        };
    }
    document.body.style.overflow = "";
});

/** Producteur : production.user (email, nom) */
function producerUser(row) {
    const prod = row.production;
    if (typeof prod === "object" && prod?.user) return prod.user;
    return null;
}

/** Email du producteur / personne ayant édité l'attestation (production.user.email) */
function producerEmail(row) {
    const u =
        producerUser(row) ??
        row.user ??
        row.editor ??
        row.created_by ??
        row.printed_by ??
        row.edited_by;
    if (typeof u === "object" && u?.email) return u.email;
    return (
        row.producer_email ??
        row.editor_email ??
        row.printed_by_email ??
        row.user_email ??
        "—"
    );
}

/** Nom du producteur (production.user.nom) */
function producerName(row) {
    const u = producerUser(row);
    if (typeof u === "object") return u.nom ?? u.name ?? u.full_name ?? "—";
    return "—";
}

const breadcrumbs = [
    { label: "Tableau de bord", href: "/dashboard" },
    { label: "Digital", href: "/digital/attestations" },
    { label: "Attestations" },
];

/** Identifiant pour voir / télécharger l'attestation (référence ou id). */
function getAttestationRef(row) {
    return (
        row.reference ??
        row.reference_id ??
        row.certificate_number ??
        row.id ??
        null
    );
}

// API: reference, printed_at, certificate_type{}, certificate_variant{}, state{}, insured_name, licence_plate, starts_at, ends_at, download_link, organization{}, office{}, user{}, producer_email, etc.
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
        key: "type",
        label: "Type",
        getValue: (row) => {
            const ct = row.certificate_type;
            const cv = row.certificate_variant;
            if (typeof cv === "object" && cv?.name) return cv.name;
            if (typeof ct === "object" && ct) return ct.name ?? ct.code ?? "—";
            return row.type ?? "—";
        },
    },
    {
        key: "etat",
        label: "État",
        type: "badge",
        getValue: (row) =>
            typeof row.state === "object" && row.state
                ? (row.state.label ?? row.state.name ?? "—")
                : (row.status ?? row.etat ?? "—"),
        getBadgeClass: (row) => {
            const v = String(
                typeof row.state === "object" && row.state
                    ? (row.state.label ?? row.state.name ?? "")
                    : (row.status ?? row.etat ?? ""),
            ).toLowerCase();
            if (
                [
                    "actif",
                    "active",
                    "approuvée",
                    "approuve",
                    "validé",
                    "valide",
                ].some((s) => v.includes(s))
            )
                return "bg-emerald-100 text-emerald-800";
            if (
                [
                    "désactivé",
                    "desactive",
                    "annulé",
                    "annule",
                    "refusé",
                    "refuse",
                    "rejeté",
                    "expiré",
                    "expire",
                    "expired",
                ].some((s) => v.includes(s))
            )
                return "bg-red-100 text-red-800";
            return "bg-slate-100 text-slate-800";
        },
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
    {
        key: "producteur",
        label: "Producteur / Éditeur",
        getValue: (row) => {
            const name = producerName(row);
            const email = producerEmail(row);
            if (name !== "—" && email !== "—") return `${name} — ${email}`;
            return email !== "—" ? email : name;
        },
    },
];
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Attestations" />
        </template>

        <p class="text-sm text-slate-600 mb-4">
            Données issues de la plateforme ASACI. Consulter et télécharger : <strong>CIMA</strong> (ASACI) ou <strong>Autres</strong> (API EATCI BNICB).
        </p>

        <div
            v-if="error"
            class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm mb-6"
        >
            {{ error }}
        </div>

        <div
            class="rounded-xl border border-slate-200 bg-white overflow-hidden"
        >
            <div
                class="px-4 py-3 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 bg-slate-50"
            >
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">
                        Liste des attestations
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Menu CIMA / Autres : Consulter et Télécharger pour chaque type
                    </p>
                </div>
                <form
                    action="/digital/attestations"
                    method="get"
                    class="flex items-center gap-2"
                >
                    <input type="hidden" name="per_page" :value="perPage" />
                    <input
                        type="search"
                        name="search"
                        :value="filters?.search ?? ''"
                        placeholder="Rechercher (réf., assuré, plaque…)"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm w-48 sm:w-56 focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
                    />
                    <button
                        type="submit"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50"
                    >
                        Rechercher
                    </button>
                </form>
            </div>
            <!-- Mobile : liste en cartes -->
            <div class="md:hidden divide-y divide-slate-100">
                <div
                    v-for="row in list"
                    :key="row.reference ?? row.id ?? row.reference_id"
                    class="p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p
                                class="font-mono text-sm font-medium text-slate-900"
                            >
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
                        <span
                            :class="[
                                'inline-flex px-2.5 py-1 rounded-full text-xs font-medium shrink-0',
                                typeof row.state === 'object' && row.state
                                    ? 'bg-slate-100 text-slate-800'
                                    : 'bg-slate-100 text-slate-800',
                            ]"
                        >
                            {{
                                typeof row.state === "object" && row.state
                                    ? (row.state.label ?? row.state.name ?? "—")
                                    : (row.status ?? "—")
                            }}
                        </span>
                    </div>
                    <div
                        v-if="getAttestationRef(row)"
                        class="mt-2 pt-2 border-t border-slate-100"
                        data-attestation-actions
                    >
                        <div class="flex flex-wrap gap-2">
                            <span class="text-xs font-medium text-slate-500 w-full">CIMA</span>
                            <button
                                type="button"
                                @click="openViewModal(row, 'cima')"
                                class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                Consulter
                            </button>
                            <a
                                :href="downloadUrlFor(getAttestationRef(row), 'cima')"
                                class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
                                download
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                Télécharger
                            </a>
                        </div>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <span class="text-xs font-medium text-slate-500 w-full">Autres</span>
                            <button
                                type="button"
                                @click="openViewModal(row, 'autres')"
                                class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                Consulter
                            </button>
                            <a
                                :href="downloadUrlFor(getAttestationRef(row), 'autres')"
                                class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
                                download
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                Télécharger
                            </a>
                        </div>
                    </div>
                </div>
                <div
                    v-if="!list.length"
                    class="py-10 px-4 text-center text-slate-500 text-sm"
                >
                    Aucune attestation pour le moment.
                </div>
            </div>

            <!-- Desktop : tableau -->
            <div class="hidden md:block">
                <DataTable
                    :data="list"
                    :columns="columns"
                    :row-key="
                        (row) => row.reference ?? row.id ?? row.reference_id
                    "
                    empty-message="Aucune attestation pour le moment."
                >
                    <template #actions="{ row }">
                        <template v-if="getAttestationRef(row)">
                            <div class="relative flex items-center justify-end" data-attestation-actions>
                                <button
                                    type="button"
                                    @click.stop="toggleActionMenu(row)"
                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 hover:border-slate-300 transition-colors"
                                    :aria-expanded="isActionMenuOpen(row)"
                                    aria-haspopup="true"
                                >
                                    <span>Actions</span>
                                    <svg class="w-4 h-4 text-slate-500 shrink-0 transition-transform" :class="{ 'rotate-180': isActionMenuOpen(row) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
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
                                        class="absolute right-0 top-full z-20 mt-2 w-60 rounded-xl border border-slate-200 bg-white shadow-xl ring-1 ring-slate-900/5 overflow-hidden"
                                    >
                                        <div class="bg-slate-50/80 px-3 py-2 border-b border-slate-100">
                                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">CIMA</span>
                                        </div>
                                        <div class="p-1">
                                            <button
                                                type="button"
                                                @click="openViewModal(row, 'cima'); closeActionMenu()"
                                                class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-100 rounded-lg transition-colors text-left"
                                            >
                                                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-600 shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                </span>
                                                <span>Consulter</span>
                                            </button>
                                            <a
                                                :href="downloadUrlFor(getAttestationRef(row), 'cima')"
                                                class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-100 rounded-lg transition-colors text-left"
                                                download
                                                @click="closeActionMenu()"
                                            >
                                                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-600 shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                                </span>
                                                <span>Télécharger</span>
                                            </a>
                                        </div>
                                        <div class="bg-slate-50/80 px-3 py-2 border-t border-slate-100">
                                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Autres</span>
                                        </div>
                                        <div class="p-1">
                                            <button
                                                type="button"
                                                @click="openViewModal(row, 'autres'); closeActionMenu()"
                                                class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-100 rounded-lg transition-colors text-left"
                                            >
                                                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-600 shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                </span>
                                                <span>Consulter</span>
                                            </button>
                                            <a
                                                :href="downloadUrlFor(getAttestationRef(row), 'autres')"
                                                class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-100 rounded-lg transition-colors text-left"
                                                download
                                                @click="closeActionMenu()"
                                            >
                                                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100 text-slate-600 shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                                </span>
                                                <span>Télécharger</span>
                                            </a>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </template>
                        <span v-else class="text-slate-400 text-sm">—</span>
                    </template>
                </DataTable>
            </div>
            <div
                class="px-4 py-3 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 bg-slate-50"
            >
                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-600"
                        >{{ list.length }} attestation(s) sur cette page</span
                    >
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
                        <option v-for="n in perPageOptions" :key="n" :value="n">
                            {{ n }}
                        </option>
                    </select>
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

        <!-- Modal visualisation attestation (PDF ou image) -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showViewModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60"
                    @click.self="closeViewModal"
                    role="dialog"
                    aria-modal="true"
                    :aria-labelledby="'modal-title-' + (viewReference || '')"
                >
                    <div
                        class="bg-white rounded-xl border border-slate-200 flex flex-col max-w-4xl w-full max-h-[90vh] overflow-hidden"
                    >
                        <div
                            class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-200 bg-slate-50 shrink-0"
                        >
                            <h3
                                :id="'modal-title-' + (viewReference || '')"
                                class="text-sm font-semibold text-slate-900 truncate"
                            >
                                Attestation {{ viewSource === 'autres' ? 'Autres' : 'CIMA' }}{{ viewReference ? ` — ${viewReference}` : '' }}
                            </h3>
                            <div class="flex items-center gap-2 shrink-0">
                                <a
                                    v-if="downloadUrl"
                                    :href="downloadUrl"
                                    download
                                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-50"
                                    title="Télécharger l'attestation"
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
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                        />
                                    </svg>
                                    Télécharger
                                </a>
                                <button
                                    type="button"
                                    @click="closeViewModal"
                                    class="p-2 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100"
                                    aria-label="Fermer"
                                >
                                    <svg
                                        class="w-5 h-5"
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
                        </div>
                        <div
                            class="flex-1 min-h-0 flex items-center justify-center bg-slate-100 p-2 relative overflow-auto"
                        >
                            <div
                                v-if="viewLoading"
                                class="absolute inset-0 flex items-center justify-center bg-slate-100 z-10 rounded-lg"
                            >
                                <div class="flex flex-col items-center gap-3">
                                    <svg
                                        class="w-10 h-10 animate-spin text-slate-400"
                                        xmlns="http://www.w3.org/2000/svg"
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
                                    <span class="text-sm text-slate-500"
                                        >Chargement de l'attestation…</span
                                    >
                                </div>
                            </div>
                            <div
                                v-else-if="viewError"
                                class="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-slate-100 z-10 rounded-lg p-6"
                            >
                                <p class="text-sm text-slate-600 text-center">
                                    Impossible d'afficher l'attestation. Vous
                                    pouvez la télécharger.
                                </p>
                                <a
                                    v-if="downloadUrl"
                                    :href="downloadUrl"
                                    download
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-white bg-slate-800 hover:bg-slate-700"
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
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                        />
                                    </svg>
                                    Télécharger l'attestation
                                </a>
                            </div>
                            <iframe
                                v-if="viewUrl && !viewError"
                                :src="viewUrl"
                                class="w-full h-[75vh] min-h-[400px] rounded-lg bg-white border border-slate-200"
                                title="Visualisation de l'attestation"
                                @load="onViewIframeLoad"
                                @error="onViewIframeError"
                            />
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </DashboardLayout>
</template>
