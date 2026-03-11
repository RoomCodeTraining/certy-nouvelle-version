<script setup>
import { computed, ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { route } from "@/route";
import {
    contractTypeLabel,
    attestationColorLabel,
    attestationColorClasses,
} from "@/utils/contractTypes";
import { CONTRACT_STATUS_LABELS } from "@/utils/contractStatus";
import { formatDate } from "@/utils/formatDate";
import { useConfirm } from "@/Composables/useConfirm";

const props = defineProps({
    contract: Object,
    can_edit_attestation: { type: Boolean, default: false },
    has_attestation: { type: Boolean, default: false },
});

const contractReference = computed(() => props.contract?.reference ?? "—");
const _typeLabel = contractTypeLabel(props.contract?.contract_type);
const _companyName = props.contract?.company?.name ?? "";
const contractTitle =
    [
        contractReference.value,
        _typeLabel !== "—" ? _typeLabel : "",
        _companyName,
    ]
        .filter(Boolean)
        .join(" — ") || "Fiche contrat";
const breadcrumbs = [
    { label: "Tableau de bord", href: "/dashboard" },
    { label: "Contrats", href: "/contracts" },
    { label: contractTitle },
];

const statusConfig = computed(() => {
    const s = props.contract?.status ?? "draft";
    const map = {
        draft: { label: "Brouillon", class: "bg-slate-100 text-slate-700" },
        validated: { label: "Validé", class: "bg-emerald-50 text-emerald-700" },
        active: { label: "Actif", class: "bg-emerald-50 text-emerald-700" },
        cancelled: { label: "Annulé", class: "bg-red-50 text-red-700" },
        expired: { label: "Expiré", class: "bg-red-100 text-red-800" },
    };
    return {
        label: CONTRACT_STATUS_LABELS[s] ?? s,
        class: (map[s] ?? map.draft).class,
    };
});

const guaranteeFieldsPdf = [
    { code: "RC", key: "rc_amount", label: "Responsabilité Civile" },
    { code: "DR", key: "defence_appeal_amount", label: "Défense et Recours" },
    {
        code: "TP",
        key: "person_transport_amount",
        label: "Transport de personnes",
    },
];

const hasPremiumData = computed(
    () =>
        props.contract?.rc_amount != null ||
        props.contract?.total_premium != null,
);

const optionalGuaranteesList = computed(() =>
    Array.isArray(props.contract?.metadata?.optional_guarantees)
        ? props.contract.metadata.optional_guarantees
        : [],
);

const primeNette = computed(
    () =>
        (props.contract?.rc_amount ?? 0) +
        (props.contract?.defence_appeal_amount ?? 0) +
        (props.contract?.person_transport_amount ?? 0) +
        (props.contract?.optional_guarantees_amount ?? 0),
);

const montantReduction = computed(() => {
    const c = props.contract;
    const pctBns = Number(c?.reduction_bns ?? 0);
    const pctComm = Number(c?.reduction_on_commission ?? 0);
    const pctProf = Number(c?.reduction_on_profession_percent ?? 0);
    const amtProf = Number(
        c?.reduction_on_profession_amount_stored ??
            c?.reduction_on_profession_amount ??
            0,
    );
    const bnsAmt =
        pctBns > 0 ? Math.round((primeNette.value * pctBns) / 100) : 0;
    const commAmt =
        pctComm > 0 ? Math.round((primeNette.value * pctComm) / 100) : 0;
    const profAmt =
        pctProf > 0 ? Math.round((primeNette.value * pctProf) / 100) : amtProf;
    return bnsAmt + commAmt + profAmt;
});

const montantApresReduction = computed(() =>
    Math.max(0, primeNette.value - montantReduction.value),
);

/** Taxe = Prime nette × 14,5 % */
const taxesAmount = computed(() =>
    Math.round(primeNette.value * 0.145),
);

const totalDisplay = computed(
    () =>
        montantApresReduction.value +
        (props.contract?.accessory_amount ?? 0) +
        taxesAmount.value +
        (props.contract?.fga_amount ?? 0) +
        (props.contract?.cedeao_amount ?? 0),
);

const reductionBnsPct = computed(() =>
    Number(props.contract?.reduction_bns ?? 0),
);
const reductionCommPct = computed(() =>
    Number(props.contract?.reduction_on_commission ?? 0),
);
const reductionProfPct = computed(() => {
    const c = props.contract;
    const pct = Number(c?.reduction_on_profession_percent ?? 0);
    if (pct > 0) return pct;
    const amt = Number(
        c?.reduction_on_profession_amount_stored ??
            c?.reduction_on_profession_amount ??
            0,
    );
    if (primeNette.value > 0 && amt > 0)
        return Math.round((amt / primeNette.value) * 1000) / 10;
    return 0;
});

const canEdit = computed(() => props.contract?.status === "draft");
const canCancel = computed(() =>
    ["draft", "validated", "active"].includes(props.contract?.status),
);
const canRenew = computed(
    () => !canEdit.value && props.contract?.status !== "cancelled",
);

const showPostValidateRecap = computed(() =>
    ["validated", "active"].includes(props.contract?.status),
);

const isActive = computed(() => props.contract?.status === "active");

const isNewBusiness = computed(() => !props.contract?.parent_id);
const parentContract = computed(() => props.contract?.parent ?? null);
const childContracts = computed(() => props.contract?.children ?? []);

const { confirm: confirmDialog } = useConfirm();
function cancel(contract) {
    confirmDialog({
        title: "Annuler le contrat",
        message:
            "Cette action est irréversible et changera le statut du contrat en « Annulé ». Aucune modification ultérieure ne sera possible. Continuer ?",
        confirmLabel: "Annuler le contrat",
        variant: "danger",
    }).then((ok) => {
        if (ok) router.post(route("contracts.cancel", contract.id));
    });
}

function validateContract(contract) {
    confirmDialog({
        title: "Valider le contrat",
        message:
            "Voulez-vous valider ce contrat ? Il passera au statut « Validé » et ne pourra plus être modifié en brouillon.",
        confirmLabel: "Valider",
        variant: "default",
    }).then((ok) => {
        if (ok) router.post(route("contracts.validate", contract.id));
    });
}

const generatingAttestation = ref(false);
const csrfToken =
    typeof document !== "undefined"
        ? document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute("content") || ""
        : "";

function markAttestationIssued(contract) {
    router.post(route("contracts.mark-attestation-issued", contract.id));
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" :title="contractTitle">
                <template #actions>
                    <span
                        :class="[
                            'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium',
                            statusConfig.class,
                        ]"
                    >
                        {{ statusConfig.label }}
                    </span>
                    <Link
                        v-if="canEdit"
                        :href="route('contracts.edit', contract.id)"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition-colors"
                    >
                        Modifier
                    </Link>
                    <button
                        v-if="canEdit"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-emerald-200 bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors"
                        @click="validateContract(contract)"
                    >
                        Valider le contrat
                    </button>
                    <Link
                        v-if="canRenew"
                        :href="route('contracts.renew', contract.id)"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-violet-200 bg-violet-50 text-violet-700 text-sm font-medium hover:bg-violet-100 transition-colors"
                    >
                        Renouveler
                    </Link>
                    <button
                        v-if="canCancel"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-amber-200 text-amber-700 text-sm font-medium hover:bg-amber-50 transition-colors"
                        @click="cancel(contract)"
                    >
                        Annuler le contrat
                    </button>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-8">
            <!-- Nouvelle affaire / Renouvellement + lien contrat de base ou liste des renouvellements -->
            <section class="rounded-2xl border border-slate-200 bg-white p-6">
                <div class="flex flex-wrap items-center gap-4">
                    <span
                        :class="[
                            'inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-medium whitespace-nowrap',
                            isNewBusiness
                                ? 'bg-emerald-100 text-emerald-800'
                                : 'bg-violet-100 text-violet-800',
                        ]"
                    >
                        {{
                            isNewBusiness
                                ? "Nouvelle affaire"
                                : "Renouvellement"
                        }}
                    </span>
                    <template v-if="parentContract">
                        <span class="text-slate-400">|</span>
                        <Link
                            :href="route('contracts.show', parentContract.id)"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900"
                        >
                            Contrat de base ({{
                                formatDate(parentContract.start_date)
                            }}
                            → {{ formatDate(parentContract.end_date) }})
                        </Link>
                    </template>
                    <template
                        v-if="
                            contract.created_by ||
                            contract.updated_by ||
                            contract.updated_at
                        "
                    >
                        <span class="text-slate-400">|</span>
                        <span class="text-sm text-slate-600">
                            <template v-if="contract.created_by">
                                Créé par
                                <strong>{{ contract.created_by.name }}</strong>
                                <template v-if="contract.created_at">
                                    le
                                    {{
                                        formatDate(contract.created_at)
                                    }}</template
                                >
                            </template>
                            <template
                                v-if="
                                    (contract.created_by ||
                                        contract.created_at) &&
                                    (contract.updated_by || contract.updated_at)
                                "
                            >
                                ·
                            </template>
                            <template
                                v-if="
                                    contract.updated_by || contract.updated_at
                                "
                            >
                                Modifié par
                                <strong>{{
                                    contract.updated_by?.name ?? "—"
                                }}</strong>
                                <template v-if="contract.updated_at">
                                    le
                                    {{
                                        formatDate(contract.updated_at)
                                    }}</template
                                >
                            </template>
                        </span>
                    </template>
                </div>
                <div
                    v-if="childContracts.length > 0"
                    class="mt-4 pt-4 border-t border-slate-100"
                >
                    <h3 class="text-sm font-medium text-slate-700 mb-2">
                        Renouvellements de ce contrat
                    </h3>
                    <ul class="space-y-2">
                        <li
                            v-for="child in childContracts"
                            :key="child.id"
                            class="flex flex-wrap items-center gap-2 text-sm"
                        >
                            <Link
                                :href="route('contracts.show', child.id)"
                                class="font-medium text-slate-900 hover:underline"
                            >
                                {{ formatDate(child.start_date) }} →
                                {{ formatDate(child.end_date) }}
                            </Link>
                            <span class="text-slate-500">·</span>
                            <span class="text-slate-600">{{
                                child.total_amount != null
                                    ? Number(child.total_amount).toLocaleString(
                                          "fr-FR",
                                      ) + " F CFA"
                                    : "—"
                            }}</span>
                            <span
                                :class="[
                                    'inline-flex px-2 py-0.5 rounded text-xs font-medium',
                                    child.status === 'active' ||
                                    child.status === 'validated'
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : 'bg-slate-100 text-slate-600',
                                ]"
                            >
                                {{
                                    CONTRACT_STATUS_LABELS[child.status] ??
                                    child.status
                                }}
                            </span>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Récap après validation ; pour contrat actif : édition d'attestation si pas encore faite -->
            <section
                v-if="showPostValidateRecap"
                class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-6"
            >
                <h2 class="text-sm font-semibold text-emerald-900 mb-3">
                    Contrat validé
                </h2>
                <p
                    v-if="can_edit_attestation"
                    class="text-sm text-emerald-800 mb-4"
                >
                    Ce contrat peut faire l'objet d'édition d'attestation. Si ce
                    n'est pas encore fait, générez une attestation digitale
                    ci-dessous.
                </p>
                <p
                    v-else-if="isActive && has_attestation"
                    class="text-sm text-emerald-800 mb-4"
                >
                    L'attestation digitale a été générée pour ce contrat. Vous
                    pouvez la télécharger ci‑dessous.
                </p>
                <p v-else class="text-sm text-emerald-800 mb-4">
                    Vous pouvez consulter le contrat en PDF et, une fois le
                    contrat actif, générer l'attestation digitale si ce n'est
                    pas encore fait.
                </p>
                <div class="flex flex-wrap gap-3 items-center">
                    <template v-if="can_edit_attestation">
                        <form
                            :action="
                                route(
                                    'contracts.generate-attestation',
                                    contract.id,
                                )
                            "
                            method="post"
                            class="inline"
                            @submit="generatingAttestation = true"
                        >
                            <input
                                type="hidden"
                                name="_token"
                                :value="csrfToken"
                            />
                            <button
                                type="submit"
                                :disabled="generatingAttestation"
                                :class="[
                                    'inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed',
                                ]"
                            >
                                <span v-if="generatingAttestation"
                                    >Génération…</span
                                >
                                <span v-else
                                    >Générer une attestation digitale</span
                                >
                            </button>
                        </form>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-emerald-300 text-emerald-700 text-sm font-medium bg-white hover:bg-emerald-50 transition-colors"
                            @click="markAttestationIssued(contract)"
                        >
                            J'ai déjà généré l'attestation
                        </button>
                    </template>
                    <template v-else-if="isActive && has_attestation">
                        <template
                            v-if="
                                contract.attestation_number ||
                                contract.attestation_link
                            "
                        >
                            <span
                                v-if="contract.attestation_number"
                                class="text-sm font-medium text-emerald-800"
                            >
                                N° {{ contract.attestation_number }}
                            </span>
                            <a
                                v-if="contract.attestation_link"
                                :href="contract.attestation_link"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors"
                            >
                                Télécharger l'attestation (PDF)
                            </a>
                        </template>
                        <Link
                            v-else
                            :href="route('digital.attestations')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors"
                        >
                            Consulter les attestations
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('digital.attestations')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors"
                        >
                            Consulter les attestations
                        </Link>
                    </template>
                    <a
                        :href="route('contracts.pdf', contract.id)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border border-emerald-300 text-emerald-800 text-sm font-medium bg-white hover:bg-emerald-50 transition-colors"
                    >
                        Consulter le contrat en PDF
                    </a>
                </div>
            </section>

            <!-- Bloc principal : 2 colonnes sur grand écran -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Colonne gauche : Infos contrat + Période -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Carte Détails du contrat -->
                    <section
                        class="rounded-2xl border border-slate-200 bg-white overflow-hidden"
                    >
                        <div
                            class="px-6 py-4 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between gap-3"
                        >
                            <h2 class="text-sm font-semibold text-slate-800">
                                Détails du contrat
                            </h2>
                            <Link
                                v-if="canEdit"
                                :href="route('contracts.edit', contract.id)"
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition-colors"
                            >
                                Modifier
                            </Link>
                            <button
                                v-if="canEdit"
                                type="button"
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-emerald-200 bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors"
                                @click="validateContract(contract)"
                            >
                                Valider
                            </button>
                            <Link
                                v-if="canRenew"
                                :href="route('contracts.renew', contract.id)"
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-violet-200 bg-violet-50 text-violet-700 text-sm font-medium hover:bg-violet-100 transition-colors"
                            >
                                Renouveler
                            </Link>
                        </div>
                        <div class="p-6">
                            <dl
                                class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5 text-sm"
                            >
                                <div>
                                    <dt
                                        class="text-slate-500 font-medium mb-0.5"
                                    >
                                        Référence
                                    </dt>
                                    <dd
                                        class="text-slate-900 font-mono font-semibold"
                                    >
                                        {{ contractReference }}
                                    </dd>
                                </div>
                                <div>
                                    <dt
                                        class="text-slate-500 font-medium mb-0.5"
                                    >
                                        Numéro de police
                                    </dt>
                                    <dd
                                        class="text-slate-900 font-mono font-semibold"
                                    >
                                        {{ contract.policy_number ?? "—" }}
                                    </dd>
                                </div>
                                <div>
                                    <dt
                                        class="text-slate-500 font-medium mb-0.5"
                                    >
                                        Type de contrat
                                    </dt>
                                    <dd class="text-slate-900 font-semibold">
                                        {{
                                            contractTypeLabel(
                                                contract.contract_type,
                                            )
                                        }}
                                        <span
                                            :class="[
                                                'ml-2 inline-flex rounded-full border px-2 py-0.5 text-xs font-medium',
                                                attestationColorClasses(
                                                    contract.contract_type,
                                                ),
                                            ]"
                                        >
                                            Attestation
                                            {{
                                                attestationColorLabel(
                                                    contract.contract_type,
                                                )
                                            }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt
                                        class="text-slate-500 font-medium mb-0.5"
                                    >
                                        Compagnie
                                    </dt>
                                    <dd class="text-slate-900">
                                        {{ contract.company?.name ?? "—" }}
                                    </dd>
                                </div>
                                <div>
                                    <dt
                                        class="text-slate-500 font-medium mb-0.5"
                                    >
                                        Date d'effet
                                    </dt>
                                    <dd class="text-slate-900">
                                        {{ formatDate(contract.start_date) }}
                                    </dd>
                                </div>
                                <div>
                                    <dt
                                        class="text-slate-500 font-medium mb-0.5"
                                    >
                                        Date d'échéance
                                    </dt>
                                    <dd class="text-slate-900">
                                        {{ formatDate(contract.end_date) }}
                                    </dd>
                                </div>
                                <!-- Attestation : numéro et lien téléchargement si présents -->
                                <template
                                    v-if="
                                        contract.attestation_number ||
                                        contract.attestation_link
                                    "
                                >
                                    <div
                                        class="sm:col-span-2 pt-4 mt-4 border-t border-slate-100"
                                    >
                                        <dt
                                            class="text-slate-500 font-medium mb-2"
                                        >
                                            Attestation
                                        </dt>
                                        <dd
                                            class="flex flex-wrap items-center gap-3"
                                        >
                                            <span
                                                v-if="
                                                    contract.attestation_number
                                                "
                                                class="font-semibold text-slate-900"
                                            >
                                                N°
                                                {{
                                                    contract.attestation_number
                                                }}
                                            </span>
                                            <a
                                                v-if="contract.attestation_link"
                                                :href="
                                                    contract.attestation_link
                                                "
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 transition-colors"
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
                                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                    />
                                                </svg>
                                                Télécharger l'attestation (PDF)
                                            </a>
                                        </dd>
                                    </div>
                                </template>
                                <template
                                    v-if="
                                        (contract.optional_guarantees_amount ||
                                            0) > 0
                                    "
                                >
                                    <div
                                        class="sm:col-span-2 pt-4 mt-4 border-t border-slate-100"
                                    >
                                        <dt
                                            class="text-slate-500 font-medium mb-1"
                                        >
                                            Garanties
                                        </dt>
                                        <dd class="text-sm text-slate-700">
                                            Montant des garanties :
                                            <span
                                                class="font-semibold text-slate-900"
                                            >
                                                {{
                                                    Number(
                                                        contract.optional_guarantees_amount,
                                                    ).toLocaleString("fr-FR")
                                                }}
                                                FCFA
                                            </span>
                                        </dd>
                                    </div>
                                </template>
                            </dl>
                        </div>
                    </section>

                    <!-- Carte Partie prenante -->
                    <section
                        class="rounded-2xl border border-slate-200 bg-white overflow-hidden"
                    >
                        <div
                            class="px-6 py-4 border-b border-slate-100 bg-slate-50/80"
                        >
                            <h2 class="text-sm font-semibold text-slate-800">
                                Partie prenante
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div
                                    class="flex gap-4 p-4 rounded-xl bg-slate-50/60 border border-slate-100"
                                >
                                    <div
                                        class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-semibold text-sm shrink-0"
                                    >
                                        {{
                                            contract.client?.full_name
                                                ?.slice(0, 2)
                                                ?.toUpperCase() ?? "—"
                                        }}
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Client
                                        </p>
                                        <Link
                                            :href="
                                                route(
                                                    'clients.show',
                                                    contract.client?.id,
                                                )
                                            "
                                            class="font-semibold text-slate-900 hover:text-slate-700 hover:underline truncate block"
                                        >
                                            {{
                                                contract.client?.full_name ??
                                                "—"
                                            }}
                                        </Link>
                                    </div>
                                </div>
                                <div
                                    class="flex gap-4 p-4 rounded-xl bg-slate-50/60 border border-slate-100"
                                >
                                    <div
                                        class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-semibold text-sm shrink-0"
                                    >
                                        <span class="font-normal">🚗</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                        >
                                            Véhicule
                                        </p>
                                        <Link
                                            :href="
                                                route(
                                                    'vehicles.show',
                                                    contract.vehicle?.id,
                                                )
                                            "
                                            class="font-semibold text-slate-900 hover:text-slate-700 hover:underline block"
                                        >
                                            <span
                                                v-if="
                                                    contract.vehicle?.brand
                                                        ?.name ||
                                                    contract.vehicle?.model
                                                        ?.name
                                                "
                                            >
                                                {{
                                                    contract.vehicle?.brand
                                                        ?.name
                                                }}
                                                {{
                                                    contract.vehicle?.model
                                                        ?.name
                                                }}
                                            </span>
                                            <span v-else class="text-slate-500"
                                                >—</span
                                            >
                                            <span
                                                v-if="
                                                    contract.vehicle
                                                        ?.registration_number
                                                "
                                                class="text-slate-600 font-normal"
                                            >
                                                ·
                                                {{
                                                    contract.vehicle
                                                        .registration_number
                                                }}
                                            </span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Colonne droite : Récap prime (sticky sur desktop) -->
                <div class="lg:col-span-1">
                    <section
                        v-if="hasPremiumData"
                        class="rounded-2xl border border-slate-200 bg-white overflow-hidden sticky top-4"
                    >
                        <div
                            class="px-6 py-4 border-b border-slate-100 bg-slate-900"
                        >
                            <h2 class="text-sm font-semibold text-white">
                                Récapitulatif prime
                            </h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <!-- Garanties : Code | Désignation | Primes (FCFA) -->
                            <div
                                v-if="primeNette > 0"
                                class="space-y-2 overflow-x-auto"
                            >
                                <p
                                    class="text-xs font-medium text-slate-500 uppercase tracking-wide"
                                >
                                    Garanties
                                </p>
                                <table
                                    class="w-full min-w-[240px] text-sm border border-slate-200 rounded-lg overflow-hidden"
                                >
                                    <thead>
                                        <tr class="bg-slate-900 text-white">
                                            <th
                                                class="px-2 py-2 text-left font-semibold text-xs"
                                            >
                                                Désignation
                                            </th>
                                            <th
                                                class="w-28 px-2 py-2 text-right font-semibold text-xs shrink-0"
                                            >
                                                Primes (FCFA)
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="f in guaranteeFieldsPdf"
                                            :key="f.key"
                                            v-show="(contract[f.key] ?? 0) > 0"
                                            class="border-t border-slate-100"
                                        >
                                            <td
                                                class="px-2 py-2 text-slate-700"
                                            >
                                                {{ f.label }}
                                            </td>
                                            <td
                                                class="w-28 px-2 py-2 text-right font-medium text-slate-900 tabular-nums shrink-0 whitespace-nowrap"
                                            >
                                                {{
                                                    Number(
                                                        contract[f.key],
                                                    ).toLocaleString("fr-FR")
                                                }}
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="g in optionalGuaranteesList"
                                            :key="g.code"
                                            class="border-t border-slate-100"
                                        >
                                            <td
                                                class="px-2 py-2 text-slate-700"
                                            >
                                                {{
                                                    g.label || "Autre garantie"
                                                }}
                                            </td>
                                            <td
                                                class="w-28 px-2 py-2 text-right font-medium text-slate-900 tabular-nums shrink-0 whitespace-nowrap"
                                            >
                                                {{
                                                    Number(
                                                        g.amount ?? 0,
                                                    ).toLocaleString("fr-FR")
                                                }}
                                            </td>
                                        </tr>
                                        <tr
                                            v-if="
                                                !optionalGuaranteesList.length &&
                                                (contract.optional_guarantees_amount ??
                                                    0) > 0
                                            "
                                            class="border-t border-slate-100"
                                        >
                                            <td
                                                class="px-2 py-2 text-slate-700"
                                            >
                                                Autres garanties
                                            </td>
                                            <td
                                                class="w-28 px-2 py-2 text-right font-medium text-slate-900 tabular-nums shrink-0 whitespace-nowrap"
                                            >
                                                {{
                                                    Number(
                                                        contract.optional_guarantees_amount,
                                                    ).toLocaleString("fr-FR")
                                                }}
                                            </td>
                                        </tr>
                                        <tr
                                            class="border-t border-slate-200 bg-slate-50 font-semibold"
                                        >
                                            <td
                                                class="px-2 py-2 text-slate-700 text-right"
                                            >
                                                Prime nette
                                            </td>
                                            <td
                                                class="w-28 px-2 py-2 text-right text-slate-900 tabular-nums shrink-0 whitespace-nowrap"
                                            >
                                                {{
                                                    primeNette.toLocaleString(
                                                        "fr-FR",
                                                    )
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Résumé financier -->
                            <p
                                class="text-xs font-medium text-slate-500 uppercase tracking-wide pt-2"
                            >
                                Résumé Financier
                            </p>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-600">Prime nette</dt>
                                    <dd
                                        class="font-medium text-slate-900 tabular-nums text-right"
                                    >
                                        {{ primeNette.toLocaleString("fr-FR") }}
                                    </dd>
                                </div>
                                <div
                                    v-if="reductionBnsPct > 0"
                                    class="flex justify-between gap-2"
                                >
                                    <dt class="text-slate-600">
                                        Réduction BNS
                                    </dt>
                                    <dd
                                        class="font-medium text-slate-900 tabular-nums text-right"
                                    >
                                        {{
                                            reductionBnsPct.toLocaleString(
                                                "fr-FR",
                                                { minimumFractionDigits: 1 },
                                            )
                                        }}
                                        %
                                    </dd>
                                </div>
                                <div
                                    v-if="reductionCommPct > 0"
                                    class="flex justify-between gap-2"
                                >
                                    <dt class="text-slate-600">
                                        Réduction commission
                                    </dt>
                                    <dd
                                        class="font-medium text-slate-900 tabular-nums text-right"
                                    >
                                        {{
                                            reductionCommPct.toLocaleString(
                                                "fr-FR",
                                                { minimumFractionDigits: 1 },
                                            )
                                        }}
                                        %
                                    </dd>
                                </div>
                                <div
                                    v-if="reductionProfPct > 0"
                                    class="flex justify-between gap-2"
                                >
                                    <dt class="text-slate-600">
                                        Réduction profession
                                    </dt>
                                    <dd
                                        class="font-medium text-slate-900 tabular-nums text-right"
                                    >
                                        {{
                                            reductionProfPct.toLocaleString(
                                                "fr-FR",
                                                { minimumFractionDigits: 1 },
                                            )
                                        }}
                                        %
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-600">
                                        Montant après réduction
                                    </dt>
                                    <dd
                                        class="font-medium text-slate-900 tabular-nums text-right"
                                    >
                                        {{
                                            montantApresReduction.toLocaleString(
                                                "fr-FR",
                                            )
                                        }}
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-600">Accessoire</dt>
                                    <dd
                                        class="font-medium text-slate-900 tabular-nums text-right"
                                    >
                                        {{
                                            Number(
                                                contract.accessory_amount ?? 0,
                                            ).toLocaleString("fr-FR")
                                        }}
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-600">Taxes</dt>
                                    <dd
                                        class="font-medium text-slate-900 tabular-nums text-right"
                                    >
                                        {{ taxesAmount.toLocaleString("fr-FR") }}
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-600">Taxe FGA</dt>
                                    <dd
                                        class="font-medium text-slate-900 tabular-nums text-right"
                                    >
                                        {{
                                            Number(
                                                contract.fga_amount ?? 0,
                                            ).toLocaleString("fr-FR")
                                        }}
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-2">
                                    <dt class="text-slate-600">CEDEAO</dt>
                                    <dd
                                        class="font-medium text-slate-900 tabular-nums text-right"
                                    >
                                        {{
                                            Number(
                                                contract.cedeao_amount ?? 0,
                                            ).toLocaleString("fr-FR")
                                        }}
                                    </dd>
                                </div>
                            </dl>

                            <div
                                class="pt-4 border-t-2 border-slate-200 space-y-2"
                            >
                                <div
                                    class="flex justify-between items-baseline gap-2"
                                >
                                    <span
                                        class="text-sm font-semibold text-slate-700"
                                        >Prime TTC</span
                                    >
                                    <span
                                        class="text-xl font-bold text-slate-900 tabular-nums"
                                    >
                                        {{
                                            totalDisplay.toLocaleString("fr-FR")
                                        }}
                                        FCFA
                                    </span>
                                </div>
                                <div
                                    v-if="(contract.agency_accessory ?? 0) > 0"
                                    class="flex justify-between gap-2 text-sm"
                                >
                                    <dt class="text-slate-600">
                                        Accessoire agence
                                    </dt>
                                    <dd
                                        class="font-medium text-slate-900 tabular-nums text-right"
                                    >
                                        {{
                                            Number(
                                                contract.agency_accessory,
                                            ).toLocaleString("fr-FR")
                                        }}
                                    </dd>
                                </div>
                                <div
                                    v-if="(contract.company_accessory ?? 0) > 0"
                                    class="flex justify-between gap-2 text-sm"
                                >
                                    <dt class="text-slate-600">
                                        Accessoire compagnie
                                    </dt>
                                    <dd
                                        class="font-medium text-slate-900 tabular-nums text-right"
                                    >
                                        {{
                                            Number(
                                                contract.company_accessory,
                                            ).toLocaleString("fr-FR")
                                        }}
                                    </dd>
                                </div>
                                <div
                                    v-if="
                                        (contract.agency_accessory ?? 0) > 0 ||
                                        (contract.company_accessory ?? 0) > 0
                                    "
                                    class="flex justify-between items-baseline gap-2 pt-2 border-t border-slate-200"
                                >
                                    <span
                                        class="text-sm font-semibold text-slate-800"
                                        >Montant à payer</span
                                    >
                                    <span
                                        class="text-lg font-bold text-slate-900 tabular-nums"
                                    >
                                        {{
                                            (
                                                totalDisplay +
                                                Number(
                                                    contract.agency_accessory ??
                                                        0,
                                                ) +
                                                Number(
                                                    contract.company_accessory ??
                                                        0,
                                                )
                                            ).toLocaleString("fr-FR")
                                        }}
                                        FCFA
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>
                    <section
                        v-else
                        class="rounded-2xl border border-slate-200 bg-slate-50/50 p-6 text-center text-slate-500 text-sm"
                    >
                        Aucune donnée de prime pour ce contrat.
                    </section>
                </div>
            </div>

            <!-- Pied de page -->
            <div class="flex flex-wrap items-center gap-3 pt-2">
                <Link
                    :href="route('contracts.index')"
                    class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors"
                >
                    ← Retour à la liste des contrats
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>
