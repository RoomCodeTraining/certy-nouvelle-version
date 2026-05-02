<script setup>
import { useForm, Link, router } from "@inertiajs/vue3";
import { computed, watch, ref, onMounted, nextTick, reactive } from "vue";
import axios from "axios";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import SearchableSelect from "@/Components/SearchableSelect.vue";
import DatePicker from "@/Components/DatePicker.vue";
import { route } from "@/route";
import {
    contractTypeLabel,
    attestationColorLabel,
    attestationColorClasses,
} from "@/utils/contractTypes";

/** Aligné sur Contract::PDF_ENDORSEMENT_CEDEAO_FCFA : seul montant tarifaire pour l’avenant. */
const ENDORSEMENT_CEDEAO_FIXED_FCFA = 1000;

const props = defineProps({
    clients: Array,
    initialVehicleId: { type: [Number, String], default: null },
    companies: Array,
    contractTypes: Array,
    durationOptions: Array,
    parentContract: { type: Object, default: null },
    typeAssureOptions: { type: Array, default: () => [] },
    vehicleBrands: { type: Array, default: () => [] },
    circulationZones: { type: Array, default: () => [] },
    energySources: { type: Array, default: () => [] },
    vehicleUsages: { type: Array, default: () => [] },
    vehicleTypes: { type: Array, default: () => [] },
    vehicleCategories: { type: Array, default: () => [] },
    vehicleGenders: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
    optionalGuaranteesConfig: { type: Array, default: () => [] },
    optionalGuaranteesEnabled: { type: Boolean, default: true },
});

const breadcrumbs = [
    { label: "Tableau de bord", href: "/dashboard" },
    { label: "Contrats", href: "/contracts" },
    { label: "Nouveau contrat" },
];

const step = ref(1);
const previewLoading = ref(false);
/** Liste clients mutable (pour ajout rapide depuis les panneaux). */
const localClients = ref([]);
function syncClientsFromProps() {
    localClients.value = (props.clients || []).map((c) => ({
        ...c,
        vehicles: (c.vehicles || []).map((v) => ({ ...v })),
    }));
}
onMounted(syncClientsFromProps);
watch(() => props.clients, syncClientsFromProps, { deep: true });

const showClientDrawer = ref(false);
const showVehicleDrawer = ref(false);
const clientQuickForm = ref({
    full_name: "",
    email: "",
    phone: "",
    address: "",
    postal_address: "",
    profession: "",
    type_assure: "",
});
const clientQuickErrors = ref({});
const clientQuickSubmitting = ref(false);
const vehicleQuickForm = ref({
    client_id: "",
    pricing_type: "VP",
    registration_number: "",
    vehicle_brand_id: "",
    vehicle_model_id: "",
    body_type: "",
    color_id: "",
    circulation_zone_id: "",
    energy_source_id: "",
    vehicle_usage_id: "",
    vehicle_type_id: "",
    vehicle_category_id: "",
    vehicle_gender_id: "",
    fiscal_power: null,
    payload_capacity: null,
    engine_capacity: null,
    seat_count: 5,
    year_of_first_registration: null,
    first_registration_date: new Date().toISOString().slice(0, 10),
    registration_card_number: "",
    chassis_number: "",
    new_value: null,
    replacement_value: null,
});
const vehicleQuickErrors = ref({});
const vehicleQuickSubmitting = ref(false);

const recap = ref({
    prime_amount: null,
    accessory_amount: null,
    total_premium: null,
    amounts: {},
});

const todayYMD = computed(() => new Date().toISOString().slice(0, 10));

const optionalGuaranteeDefs = computed(() =>
    props.optionalGuaranteesEnabled
        ? (props.optionalGuaranteesConfig || []).filter((g) => g.enabled)
        : [],
);

const optionalGuarantees = ref({});

function initOptionalGuarantees() {
    if (!props.optionalGuaranteesEnabled) {
        optionalGuarantees.value = {};
        form.optional_guarantees_amount = 0;
        form.optional_guarantees_detail = [];
        return;
    }
    const current = optionalGuarantees.value || {};
    const next = {};
    optionalGuaranteeDefs.value.forEach((def) => {
        const existing = current[def.code];
        next[def.code] = existing ?? { enabled: false, amount: 0 };
    });
    optionalGuarantees.value = next;
}

watch(
    () => props.optionalGuaranteesEnabled,
    () => {
        initOptionalGuarantees();
    },
    { immediate: true },
);

watch(optionalGuaranteeDefs, () => {
    initOptionalGuarantees();
});

const vehicleNewValue = ref(null);
const vehicleVenaleValue = ref(null);

/** Libellés des garanties de la grille pour le récap */
const guaranteeLabels = {
    base_amount: "Prime de base",
    rc_amount: "RC (Responsabilité civile)",
    defence_appeal_amount: "Défense recours",
    person_transport_amount: "Transport de personnes",
    accessory_amount: "Accessoires",
    taxes_amount: "Taxes",
    cedeao_amount: "CEDEAO",
    fga_amount: "FGA",
};
const guaranteeKeys = Object.keys(guaranteeLabels);

const form = useForm({
    client_id: "",
    vehicle_id: "",
    is_double_cabine: false,
    second_vehicle_id: "",
    company_id: "",
    contract_type: "VP",
    parent_id: "",
    status: "draft",
    start_date: "",
    end_date: "",
    duration: "12_months",
    creation_mode: null,
    endorsement_type: "",
    reduction_amount: 0,
    accessory_amount_override: null,
    base_amount_override: null,
    rc_amount_override: null,
    defence_appeal_amount_override: null,
    person_transport_amount_override: null,
    taxes_amount_override: null,
    cedeao_amount_override: null,
    fga_amount_override: null,
    reduction_bns: null,
    reduction_on_commission: null,
    reduction_on_profession_percent: null,
    reduction_on_profession_amount: null,
    company_accessory: 0,
    agency_accessory: 0,
    commission_amount: 0,
    optional_guarantees_amount: 0,
    optional_guarantees_detail: [],
});

const selectedClient = computed(() =>
    localClients.value.find((c) => String(c.id) === String(form.client_id)),
);
const vehiclesForClient = computed(() => selectedClient.value?.vehicles ?? []);
const vehiclesForSelect = computed(() =>
    vehiclesForClient.value.map((v) => ({
        ...v,
        name: v.registration_number || `Sans immat (id ${v.id})`,
    })),
);

function onClientChange() {
    form.vehicle_id = "";
    form.contract_type = "VP";
    form.is_double_cabine = false;
    form.second_vehicle_id = "";
}

const isTwoWheeler = computed(() => form.contract_type === "TWO_WHEELER");
const isTPM = computed(() => form.contract_type === "TPM");

const vehiclesForSecondSelect = computed(() =>
    vehiclesForClient.value
        .filter(
            (v) =>
                v.pricing_type === "TPM" &&
                String(v.id) !== String(form.vehicle_id),
        )
        .map((v) => ({
            ...v,
            name:
                (v.registration_number || `Sans immat (id ${v.id})`) +
                (v.payload_capacity ? ` — ${v.payload_capacity} t` : ""),
        })),
);
const isEndorsementMode = computed(
    () => props.parentContract?.creation_mode === "endorsement",
);
/** Avenant : pas avant aujourd'hui, pas après l'échéance du contrat parent. */
const endorsementStartDateMax = computed(() => {
    if (!isEndorsementMode.value || !props.parentContract?.end_date) {
        return undefined;
    }
    return String(props.parentContract.end_date).slice(0, 10);
});
const endorsementDatePickerYearRange = computed(() => {
    const startY = new Date().getFullYear();
    let endY = startY + 1;
    const max = endorsementStartDateMax.value;
    if (max && max.length >= 4) {
        const y = parseInt(max.slice(0, 4), 10);
        if (!Number.isNaN(y)) {
            endY = Math.max(endY, y);
        }
    }
    return [startY, endY];
});
const needsVehicleUpdateForEndorsement = computed(() =>
    ["registration_change", "vehicle_info_update"].includes(
        form.endorsement_type,
    ),
);
const needsClientUpdateForEndorsement = computed(
    () => form.endorsement_type === "client_info_update",
);

const TYPE_TAPP = "TAPP";
const TYPE_TAPM = "TAPM";

const pricingTypeOptions = [
    { value: "VP", label: "VP (Véhicule Particulier)" },
    { value: "TPC", label: "TPC (Transport pour propre compte)" },
    { value: "TPM", label: "TPM (Transport Personnes et Marchandises)" },
    { value: "TWO_WHEELER", label: "Deux roues" },
];

function formatDateForEndorsement(d) {
    return d ? String(d).slice(0, 10) : "";
}

/** IDs en chaîne pour SearchableSelect (alignement avec les options `id`). */
function normalizeSelectId(val) {
    if (val === null || val === undefined || val === "") return "";
    return String(val);
}

function numOrNull(v) {
    if (v === null || v === undefined || v === "") return null;
    const n = Number(v);
    return Number.isNaN(n) ? null : n;
}

/** FK ou id issu de la relation chargée (réponse JSON Laravel en snake_case). */
function fkIdFromVehicle(v, fkAttr, relationSnake) {
    const direct = v?.[fkAttr];
    if (direct !== null && direct !== undefined && direct !== "") {
        return normalizeSelectId(direct);
    }
    const rel = v?.[relationSnake];
    if (rel && typeof rel === "object" && rel.id != null) {
        return normalizeSelectId(rel.id);
    }
    return "";
}

function fiscalPowerOrNull(v) {
    const x = v?.fiscal_power;
    if (x === null || x === undefined || x === "") return null;
    const n =
        typeof x === "number" && Number.isFinite(x)
            ? Math.trunc(x)
            : parseInt(String(x), 10);
    return Number.isNaN(n) ? null : n;
}

function mapVehicleToEndorsementForm(v) {
    return {
        pricing_type:
            v?.pricing_type != null && v.pricing_type !== ""
                ? String(v.pricing_type)
                : "",
        vehicle_type_id: fkIdFromVehicle(
            v,
            "vehicle_type_id",
            "vehicle_type",
        ),
        registration_number: v?.registration_number ?? "",
        vehicle_brand_id: fkIdFromVehicle(v, "vehicle_brand_id", "brand"),
        vehicle_model_id: fkIdFromVehicle(v, "vehicle_model_id", "model"),
        body_type: v?.body_type ?? "",
        color_id: fkIdFromVehicle(v, "color_id", "color"),
        payload_capacity: numOrNull(v?.payload_capacity),
        energy_source_id: fkIdFromVehicle(
            v,
            "energy_source_id",
            "energy_source",
        ),
        engine_capacity: numOrNull(v?.engine_capacity),
        seat_count: numOrNull(v?.seat_count),
        vehicle_usage_id: fkIdFromVehicle(
            v,
            "vehicle_usage_id",
            "vehicle_usage",
        ),
        vehicle_category_id: fkIdFromVehicle(
            v,
            "vehicle_category_id",
            "vehicle_category",
        ),
        vehicle_gender_id: fkIdFromVehicle(
            v,
            "vehicle_gender_id",
            "vehicle_gender",
        ),
        circulation_zone_id: fkIdFromVehicle(
            v,
            "circulation_zone_id",
            "circulation_zone",
        ),
        fiscal_power: fiscalPowerOrNull(v),
        year_of_first_registration: numOrNull(v?.year_of_first_registration),
        first_registration_date: formatDateForEndorsement(v?.first_registration_date),
        registration_card_number: v?.registration_card_number ?? "",
        chassis_number: v?.chassis_number ?? "",
        new_value: numOrNull(v?.new_value),
        replacement_value: numOrNull(v?.replacement_value),
    };
}

const endorsementVehicleForm = reactive(mapVehicleToEndorsementForm({}));
const endorsementVehicleLoading = ref(false);
const endorsementVehicleSaving = ref(false);
const endorsementVehicleErrors = ref({});
const endorsementVehicleLoadedId = ref(null);

const endorsementClientForm = reactive({
    type_assure: TYPE_TAPP,
    full_name: "",
    email: "",
    phone: "",
    address: "",
    postal_address: "",
    profession: "",
});
const endorsementClientLoading = ref(false);
const endorsementClientSaving = ref(false);
const endorsementClientErrors = ref({});
const endorsementClientLoadedId = ref(null);


/** Listes alignées sur la réponse `vehicles.edit` (JSON) pour les selects avenant. */
const endorsementSelectLists = ref(null);

/**
 * `[] ?? props.x` renvoie `[]` (tableau vide ≠ nullish) → les listes Inertia étaient ignorées.
 */
function mergeRefList(apiList, propList) {
    const fromApi = Array.isArray(apiList) ? apiList : [];
    const fromProp = Array.isArray(propList) ? propList : [];
    return fromApi.length > 0 ? fromApi : fromProp;
}

const endorsementVehicleBrandsOptions = computed(() =>
    mergeRefList(
        endorsementSelectLists.value?.brands,
        props.vehicleBrands,
    ),
);

const endorsementVehicleUsagesOptions = computed(() =>
    mergeRefList(
        endorsementSelectLists.value?.vehicleUsages,
        props.vehicleUsages,
    ),
);
const endorsementVehicleTypesOptions = computed(() =>
    mergeRefList(
        endorsementSelectLists.value?.vehicleTypes,
        props.vehicleTypes,
    ),
);
const endorsementVehicleCategoriesOptions = computed(() =>
    mergeRefList(
        endorsementSelectLists.value?.vehicleCategories,
        props.vehicleCategories,
    ),
);
const endorsementVehicleGendersOptions = computed(() =>
    mergeRefList(
        endorsementSelectLists.value?.vehicleGenders,
        props.vehicleGenders,
    ),
);
const endorsementVehicleColorsOptions = computed(() =>
    mergeRefList(endorsementSelectLists.value?.colors, props.colors),
);
const endorsementVehicleCirculationZonesOptions = computed(() =>
    mergeRefList(
        endorsementSelectLists.value?.circulationZones,
        props.circulationZones,
    ),
);
const endorsementVehicleEnergySourcesOptions = computed(() =>
    mergeRefList(
        endorsementSelectLists.value?.energySources,
        props.energySources,
    ),
);

const modelsForEndorsementVehicle = computed(() => {
    if (!endorsementVehicleForm.vehicle_brand_id) return [];
    const brand = endorsementVehicleBrandsOptions.value.find(
        (b) => String(b.id) === String(endorsementVehicleForm.vehicle_brand_id),
    );
    return brand?.models ?? [];
});

function onEndorsementVehicleBrandChange() {
    if (
        !modelsForEndorsementVehicle.value.some(
            (m) => String(m.id) === String(endorsementVehicleForm.vehicle_model_id),
        )
    ) {
        endorsementVehicleForm.vehicle_model_id = "";
    }
}

function endorsementVehicleErr(key) {
    const e = endorsementVehicleErrors.value[key];
    return Array.isArray(e) ? e[0] : e || "";
}

function endorsementClientErr(key) {
    const e = endorsementClientErrors.value[key];
    return Array.isArray(e) ? e[0] : e || "";
}

const endorsementVehicleReady = computed(() => {
    if (!needsVehicleUpdateForEndorsement.value || !form.vehicle_id) return true;
    return (
        String(endorsementVehicleLoadedId.value) === String(form.vehicle_id) &&
        !endorsementVehicleLoading.value
    );
});

const endorsementClientReady = computed(() => {
    if (!needsClientUpdateForEndorsement.value || !form.client_id) return true;
    return (
        String(endorsementClientLoadedId.value) === String(form.client_id) &&
        !endorsementClientLoading.value
    );
});

const endorsementClientNameLabel = computed(() =>
    endorsementClientForm.type_assure === TYPE_TAPM
        ? "Raison sociale *"
        : "Nom complet *",
);
const endorsementClientProfessionLabel = computed(() =>
    endorsementClientForm.type_assure === TYPE_TAPM
        ? "Activité / Secteur *"
        : "Profession *",
);

/**
 * Aligne les FK sur les listes via `code` quand l’id ne matche pas (référentiels / seeds).
 */
function syncEndorsementVehicleFkFromRelations(v) {
    const lists = endorsementSelectLists.value;
    if (!lists || !v) return;

    const pairs = [
        ["vehicle_usage_id", v.vehicle_usage, lists.vehicleUsages],
        ["vehicle_category_id", v.vehicle_category, lists.vehicleCategories],
        ["vehicle_gender_id", v.vehicle_gender, lists.vehicleGenders],
        ["vehicle_type_id", v.vehicle_type, lists.vehicleTypes],
        ["energy_source_id", v.energy_source, lists.energySources],
        ["color_id", v.color, lists.colors],
        ["circulation_zone_id", v.circulation_zone, lists.circulationZones],
    ];

    for (const [field, rel, list] of pairs) {
        if (!list?.length) continue;
        const cur = endorsementVehicleForm[field];
        if (cur !== "" && cur != null) {
            const byId = list.find((o) => String(o.id) === String(cur));
            if (byId) continue;
        }
        if (rel?.code != null) {
            const byCode = list.find(
                (o) =>
                    o.code != null && String(o.code) === String(rel.code),
            );
            if (byCode) {
                endorsementVehicleForm[field] = normalizeSelectId(byCode.id);
            }
        }
    }
}

async function loadEndorsementVehicleData() {
    if (!form.vehicle_id) return;
    endorsementVehicleLoading.value = true;
    endorsementVehicleErrors.value = {};
    try {
        const { data } = await axios.get(route("vehicles.edit", form.vehicle_id), {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')?.content || "",
            },
        });
        const v = data.vehicle;
        endorsementSelectLists.value = {
            brands: mergeRefList(data.brands, props.vehicleBrands),
            vehicleUsages: mergeRefList(
                data.vehicleUsages ?? data.vehicle_usages,
                props.vehicleUsages,
            ),
            vehicleTypes: mergeRefList(
                data.vehicleTypes ?? data.vehicle_types,
                props.vehicleTypes,
            ),
            vehicleCategories: mergeRefList(
                data.vehicleCategories ?? data.vehicle_categories,
                props.vehicleCategories,
            ),
            vehicleGenders: mergeRefList(
                data.vehicleGenders ?? data.vehicle_genders,
                props.vehicleGenders,
            ),
            colors: mergeRefList(data.colors, props.colors),
            circulationZones: mergeRefList(
                data.circulationZones ?? data.circulation_zones,
                props.circulationZones,
            ),
            energySources: mergeRefList(
                data.energySources ?? data.energy_sources,
                props.energySources,
            ),
        };
        if (!v) {
            throw new Error("Réponse véhicule vide");
        }
        Object.assign(endorsementVehicleForm, mapVehicleToEndorsementForm(v));
        syncEndorsementVehicleFkFromRelations(v);
        endorsementVehicleLoadedId.value = String(form.vehicle_id);
        await nextTick();
    } catch {
        endorsementVehicleErrors.value = {
            general: [
                "Impossible de charger le véhicule. Réessayez ou ouvrez la fiche véhicule.",
            ],
        };
        endorsementVehicleLoadedId.value = null;
    } finally {
        endorsementVehicleLoading.value = false;
    }
}

async function loadEndorsementClientData() {
    if (!form.client_id) return;
    endorsementClientLoading.value = true;
    endorsementClientErrors.value = {};
    try {
        const { data } = await axios.get(route("clients.edit", form.client_id), {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')?.content || "",
            },
        });
        const c = data.client;
        endorsementClientForm.type_assure = c.type_assure ?? TYPE_TAPP;
        endorsementClientForm.full_name = c.full_name ?? "";
        endorsementClientForm.email = c.email ?? "";
        endorsementClientForm.phone = c.phone ?? "";
        endorsementClientForm.address = c.address ?? "";
        endorsementClientForm.postal_address = c.postal_address ?? "";
        endorsementClientForm.profession = c.profession?.name ?? "";
        endorsementClientLoadedId.value = String(form.client_id);
        await nextTick();
    } catch {
        endorsementClientErrors.value = {
            general: [
                "Impossible de charger le client. Réessayez ou ouvrez la fiche client.",
            ],
        };
        endorsementClientLoadedId.value = null;
    } finally {
        endorsementClientLoading.value = false;
    }
}

watch(
    () => [isEndorsementMode.value, form.vehicle_id, form.endorsement_type],
    () => {
        if (
            !isEndorsementMode.value ||
            !needsVehicleUpdateForEndorsement.value ||
            !form.vehicle_id
        ) {
            endorsementVehicleLoadedId.value = null;
            endorsementSelectLists.value = null;
            return;
        }
        loadEndorsementVehicleData();
    },
    { immediate: true },
);

watch(
    () => [isEndorsementMode.value, form.client_id, form.endorsement_type],
    () => {
        if (
            !isEndorsementMode.value ||
            !needsClientUpdateForEndorsement.value ||
            !form.client_id
        ) {
            endorsementClientLoadedId.value = null;
            return;
        }
        loadEndorsementClientData();
    },
    { immediate: true },
);

async function persistEndorsementVehicle() {
    endorsementVehicleErrors.value = {};
    endorsementVehicleSaving.value = true;
    try {
        await axios.put(
            route("vehicles.update", form.vehicle_id),
            { ...endorsementVehicleForm },
            {
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')?.content || "",
                    "X-Requested-With": "XMLHttpRequest",
                },
            },
        );
    } catch (err) {
        if (err.response?.status === 422 && err.response?.data?.errors) {
            endorsementVehicleErrors.value = err.response.data.errors;
        }
        throw err;
    } finally {
        endorsementVehicleSaving.value = false;
    }
}

async function persistEndorsementClient() {
    endorsementClientErrors.value = {};
    endorsementClientSaving.value = true;
    try {
        await axios.put(
            route("clients.update", form.client_id),
            { ...endorsementClientForm },
            {
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')?.content || "",
                    "X-Requested-With": "XMLHttpRequest",
                },
            },
        );
    } catch (err) {
        if (err.response?.status === 422 && err.response?.data?.errors) {
            endorsementClientErrors.value = err.response.data.errors;
        }
        throw err;
    } finally {
        endorsementClientSaving.value = false;
    }
}

function toastSuccess(text) {
    window.dispatchEvent(
        new CustomEvent("app:toast", {
            detail: { message: text, type: "success" },
        }),
    );
}

async function saveEndorsementVehicleManual() {
    try {
        await persistEndorsementVehicle();
        toastSuccess("Modifications véhicule enregistrées.");
    } catch {
        /* Erreurs affichées sur les champs */
    }
}

async function saveEndorsementClientManual() {
    try {
        await persistEndorsementClient();
        toastSuccess("Modifications client enregistrées.");
    } catch {
        /* Erreurs affichées sur les champs */
    }
}

async function persistEndorsementMasterDataIfNeeded() {
    if (isEndorsementMode.value && needsVehicleUpdateForEndorsement.value) {
        if (!endorsementVehicleReady.value) {
            form.setError(
                "endorsement_vehicle",
                "Les informations véhicule ne sont pas prêtes. Vérifiez le chargement ou le véhicule sélectionné.",
            );
            return false;
        }
        try {
            await persistEndorsementVehicle();
        } catch {
            return false;
        }
    }
    if (isEndorsementMode.value && needsClientUpdateForEndorsement.value) {
        if (!endorsementClientReady.value) {
            form.setError(
                "endorsement_client",
                "Les informations client ne sont pas prêtes. Vérifiez le chargement ou le client sélectionné.",
            );
            return false;
        }
        try {
            await persistEndorsementClient();
        } catch {
            return false;
        }
    }
    return true;
}

function onVehicleChange() {
    const v = vehiclesForClient.value.find(
        (vh) => String(vh.id) === String(form.vehicle_id),
    );
    form.contract_type = v?.pricing_type ?? "VP";
    // Réinitialiser le 2e véhicule si on change le principal
    if (String(form.second_vehicle_id) === String(form.vehicle_id)) {
        form.second_vehicle_id = "";
    }
    if (form.contract_type === "TWO_WHEELER") {
        form.duration = "12_months";
    }
    if (v) {
        vehicleNewValue.value =
            v.new_value != null ? Number(v.new_value) : null;
        vehicleVenaleValue.value =
            v.replacement_value != null ? Number(v.replacement_value) : null;
    } else {
        vehicleNewValue.value = null;
        vehicleVenaleValue.value = null;
    }
}

const vehicleQuickModels = computed(() => {
    if (!vehicleQuickForm.value.vehicle_brand_id) return [];
    const brand = props.vehicleBrands.find(
        (b) => String(b.id) === String(vehicleQuickForm.value.vehicle_brand_id),
    );
    return brand?.models ?? [];
});

function openClientDrawer() {
    clientQuickForm.value = {
        full_name: "",
        email: "",
        phone: "",
        address: "",
        postal_address: "",
        profession: "",
        type_assure: "",
    };
    clientQuickErrors.value = {};
    showClientDrawer.value = true;
}

function openVehicleDrawer() {
    vehicleQuickForm.value = {
        client_id: String(form.client_id),
        pricing_type: form.contract_type || "VP",
        registration_number: "",
        vehicle_brand_id: "",
        vehicle_model_id: "",
        body_type: "",
        color_id: "",
        circulation_zone_id: "",
        energy_source_id: "",
        vehicle_usage_id: "",
        vehicle_type_id: "",
        vehicle_category_id: "",
        vehicle_gender_id: "",
        fiscal_power: null,
        payload_capacity: null,
        engine_capacity: null,
        seat_count: 5,
        year_of_first_registration: null,
        first_registration_date: new Date().toISOString().slice(0, 10),
        registration_card_number: "",
        chassis_number: "",
        new_value: null,
        replacement_value: null,
    };
    vehicleQuickErrors.value = {};
    showVehicleDrawer.value = true;
}

function closeClientDrawer() {
    showClientDrawer.value = false;
}

function closeVehicleDrawer() {
    showVehicleDrawer.value = false;
}

async function submitClientQuick() {
    clientQuickErrors.value = {};
    clientQuickSubmitting.value = true;
    try {
        const { data } = await axios.post(
            route("clients.store"),
            clientQuickForm.value,
            {
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                    "X-Requested-With": "XMLHttpRequest",
                },
            },
        );
        const client = data.client;
        if (client) {
            localClients.value.push({
                ...client,
                vehicles: client.vehicles ?? [],
            });
            form.client_id = String(client.id);
            closeClientDrawer();
        }
    } catch (err) {
        if (err.response?.status === 422 && err.response?.data?.errors) {
            clientQuickErrors.value = err.response.data.errors;
        }
    } finally {
        clientQuickSubmitting.value = false;
    }
}

async function submitVehicleQuick() {
    vehicleQuickErrors.value = {};
    vehicleQuickSubmitting.value = true;
    const payload = { ...vehicleQuickForm.value };
    [
        "fiscal_power",
        "payload_capacity",
        "engine_capacity",
        "seat_count",
        "year_of_first_registration",
        "new_value",
        "replacement_value",
    ].forEach((k) => {
        if (payload[k] === "" || payload[k] == null) payload[k] = null;
        else if (typeof payload[k] === "string" && /^\d+$/.test(payload[k]))
            payload[k] = parseInt(payload[k], 10);
    });
    try {
        const { data } = await axios.post(route("vehicles.store"), payload, {
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
                "X-Requested-With": "XMLHttpRequest",
            },
        });
        const vehicle = data.vehicle;
        if (vehicle) {
            const client = localClients.value.find(
                (c) => String(c.id) === String(form.client_id),
            );
            if (client) {
                if (!client.vehicles) client.vehicles = [];
                client.vehicles.push({
                    ...vehicle,
                    name:
                        vehicle.registration_number ||
                        `Sans immat (id ${vehicle.id})`,
                });
            }
            form.vehicle_id = String(vehicle.id);
            form.contract_type = vehicle.pricing_type || form.contract_type;
            closeVehicleDrawer();
        }
    } catch (err) {
        if (err.response?.status === 422 && err.response?.data?.errors) {
            vehicleQuickErrors.value = err.response.data.errors;
        }
    } finally {
        vehicleQuickSubmitting.value = false;
    }
}

function applyParentContract(parent) {
    if (!parent || !parent.id) return;
    const isEndorsement = parent.creation_mode === "endorsement";
    form.parent_id = String(parent.id);
    if (parent.client_id) form.client_id = String(parent.client_id);
    if (parent.company_id) form.company_id = String(parent.company_id);
    if (parent.contract_type) form.contract_type = parent.contract_type;
    if (form.contract_type === "TWO_WHEELER") form.duration = "12_months";
    if (isEndorsement) {
        if (
            parent.commission_amount != null &&
            parent.commission_amount !== ""
        ) {
            form.commission_amount = Number(parent.commission_amount) || 0;
        }
    }
    if (parent.end_date) {
        if (isEndorsement) {
            form.start_date = new Date().toISOString().slice(0, 10);
            form.end_date = parent.end_date;
        } else {
            const end = new Date(parent.end_date + "T12:00:00");
            end.setDate(end.getDate() + 1);
            form.start_date = end.toISOString().slice(0, 10);
            applyDuration();
        }
    }
    // Mettre vehicle_id et lancer le récap après que Vue ait mis à jour
    // vehiclesForSelect (dépend de client_id) pour éviter que le watcher
    // ne vide le récap avec des champs partiels
    nextTick(() => {
        if (parent.vehicle_id) form.vehicle_id = String(parent.vehicle_id);
        nextTick(() => {
            if (
                form.vehicle_id &&
                form.contract_type &&
                form.start_date &&
                form.end_date
            ) {
                fetchPreview();
            }
        });
    });
}

// Pré-remplir le formulaire en cas de renouvellement (parentContract) ou véhicule présélectionné (initialVehicleId)
onMounted(() => {
    if (props.parentContract) {
        applyParentContract(props.parentContract);
    } else if (props.initialVehicleId && props.clients?.length) {
        for (const client of props.clients) {
            const vehicle = (client.vehicles ?? []).find(
                (v) => String(v.id) === String(props.initialVehicleId),
            );
            if (vehicle) {
                form.client_id = String(client.id);
                form.vehicle_id = String(vehicle.id);
                form.contract_type = vehicle.pricing_type ?? "VP";
                nextTick(() => onVehicleChange());
                break;
            }
        }
    }
});

watch(
    () => props.parentContract,
    (parent) => {
        if (parent) applyParentContract(parent);
    },
    { immediate: true },
);

function applyDuration() {
    if (props.parentContract?.creation_mode === "endorsement") {
        if (props.parentContract?.end_date) {
            form.end_date = props.parentContract.end_date;
        }
        return;
    }
    if (!form.start_date || !form.duration) return;
    const start = new Date(form.start_date);
    const monthsMap = {
        "1_month": 1,
        "3_months": 3,
        "6_months": 6,
        "12_months": 12,
    };
    const months = monthsMap[form.duration] ?? 12;
    const end = new Date(start);
    end.setMonth(end.getMonth() + months);
    end.setDate(end.getDate() - 1);
    form.end_date = end.toISOString().slice(0, 10);
}

const canPreview = computed(
    () =>
        form.vehicle_id &&
        form.contract_type &&
        form.start_date &&
        form.end_date,
);

function overrideOrFallback(overrideValue, fallbackValue) {
    if (overrideValue !== null && overrideValue !== "") {
        return Number(overrideValue) || 0;
    }
    return Number(fallbackValue ?? 0);
}

function seedEndorsementOverridesFromRecap() {
    if (!isEndorsementMode.value) return;
    const amounts = recap.value?.amounts ?? {};
    const defaults = {
        base_amount_override: amounts.base_amount ?? 0,
        rc_amount_override: amounts.rc_amount ?? 0,
        defence_appeal_amount_override: amounts.defence_appeal_amount ?? 0,
        person_transport_amount_override: amounts.person_transport_amount ?? 0,
        accessory_amount_override:
            amounts.accessory_amount ?? recap.value?.accessory_amount ?? 0,
        taxes_amount_override: amounts.taxes_amount ?? 0,
        cedeao_amount_override: amounts.cedeao_amount ?? 0,
        fga_amount_override: amounts.fga_amount ?? 0,
    };

    Object.entries(defaults).forEach(([field, value]) => {
        if (form[field] === null || form[field] === "") {
            form[field] = Number(value) || 0;
        }
    });
}

/** Reprend les montants du contrat parent (évite un recalcul grille sur période raccourcie). */
function applyOptionalGuaranteesFromParentMetadata(list) {
    if (!props.optionalGuaranteesEnabled) {
        form.optional_guarantees_detail = [];
        form.optional_guarantees_amount = 0;
        return;
    }
    const detail = [];
    const next = { ...optionalGuarantees.value };
    optionalGuaranteeDefs.value.forEach((def) => {
        const found = Array.isArray(list)
            ? list.find((g) => String(g.code) === String(def.code))
            : null;
        const amt = found ? Number(found.amount) || 0 : 0;
        if (amt > 0) {
            next[def.code] = { enabled: true, amount: amt };
            detail.push({
                code: def.code,
                label: found?.label ?? def.label,
                rate: def.rate,
                base: def.base,
                amount: amt,
            });
        } else {
            next[def.code] = { enabled: false, amount: 0 };
        }
    });
    optionalGuarantees.value = next;
    form.optional_guarantees_detail = detail;
    form.optional_guarantees_amount = detail.reduce(
        (s, g) => s + Number(g.amount ?? 0),
        0,
    );
}

function seedEndorsementFromParent() {
    const p = props.parentContract;
    if (!p || !isEndorsementMode.value) return;
    const num = (v) =>
        v != null && v !== "" && !Number.isNaN(Number(v)) ? Number(v) : 0;
    const z = 0;
    const cedeao = ENDORSEMENT_CEDEAO_FIXED_FCFA;
    recap.value = {
        prime_amount: null,
        accessory_amount: z,
        total_premium: cedeao,
        amounts: {
            base_amount: z,
            rc_amount: z,
            defence_appeal_amount: z,
            person_transport_amount: z,
            accessory_amount: z,
            taxes_amount: z,
            fga_amount: z,
            cedeao_amount: cedeao,
        },
    };
    form.base_amount_override = z;
    form.rc_amount_override = z;
    form.defence_appeal_amount_override = z;
    form.person_transport_amount_override = z;
    form.accessory_amount_override = z;
    form.taxes_amount_override = z;
    form.fga_amount_override = z;
    form.cedeao_amount_override = cedeao;
    form.reduction_amount = z;
    form.reduction_bns = null;
    form.reduction_on_commission = null;
    form.reduction_on_profession_percent = null;
    form.reduction_on_profession_amount = null;
    form.company_accessory = z;
    form.agency_accessory = z;
    form.commission_amount = num(p.commission_amount);
    applyOptionalGuaranteesFromParentMetadata([]);
}

/** Champs minimaux pour enregistrer en brouillon (étape 1 ou 2). */
const canSaveDraft = computed(
    () =>
        form.client_id &&
        form.vehicle_id &&
        form.company_id &&
        form.contract_type &&
        form.start_date &&
        form.end_date,
);

const PREVIEW_LOADER_MIN_MS = 3000;

const optionalGuaranteesTotal = computed(() =>
    props.optionalGuaranteesEnabled
        ? optionalGuaranteeDefs.value.reduce((sum, def) => {
              const g = optionalGuarantees.value[def.code];
              if (!g?.enabled) return sum;
              const amount = Number(g.amount) || 0;
              return sum + amount;
          }, 0)
        : 0,
);

const anyGuaranteeUsingNew = computed(() =>
    props.optionalGuaranteesEnabled
        ? optionalGuaranteeDefs.value.some(
              (def) =>
                  def.base === "new" &&
                  optionalGuarantees.value[def.code]?.enabled,
          )
        : false,
);

const anyGuaranteeUsingVenale = computed(() =>
    props.optionalGuaranteesEnabled
        ? optionalGuaranteeDefs.value.some(
              (def) =>
                  def.base === "venale" &&
                  optionalGuarantees.value[def.code]?.enabled,
          )
        : false,
);

const missingNewBase = computed(
    () => anyGuaranteeUsingNew.value && !vehicleNewValue.value,
);

const missingVenaleBase = computed(
    () => anyGuaranteeUsingVenale.value && !vehicleVenaleValue.value,
);

function recalcOptionalGuarantees() {
    const detail = [];
    optionalGuaranteeDefs.value.forEach((def) => {
        const state = optionalGuarantees.value[def.code];
        if (!state) {
            return;
        }
        if (!state.enabled) {
            state.amount = 0;
            return;
        }
        let baseValue = 0;
        if (def.base === "new") {
            baseValue = Number(vehicleNewValue.value) || 0;
        } else if (def.base === "venale") {
            baseValue = Number(vehicleVenaleValue.value) || 0;
        }
        if (!baseValue || baseValue <= 0) {
            state.amount = 0;
            return;
        }
        const raw = (baseValue * def.rate) / 100;
        state.amount = Math.round(raw);
        if (state.amount > 0) {
            detail.push({
                code: def.code,
                label: def.label,
                rate: def.rate,
                base: def.base,
                amount: state.amount,
            });
        }
    });
    form.optional_guarantees_amount = optionalGuaranteesTotal.value;
    form.optional_guarantees_detail = detail;
}

async function fetchPreview() {
    if (!canPreview.value) {
        recap.value = {
            prime_amount: null,
            accessory_amount: null,
            total_premium: null,
            amounts: {},
        };
        return;
    }
    if (isEndorsementMode.value) {
        seedEndorsementFromParent();
        previewLoading.value = false;
        return;
    }
    previewLoading.value = true;
    const startAt = Date.now();
    try {
        const { data } = await axios.post(
            route("contracts.preview"),
            {
                vehicle_id: form.vehicle_id,
                contract_type: form.contract_type,
                start_date: form.start_date,
                end_date: form.end_date,
            },
            {
                headers: {
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            },
        );
        recap.value = {
            prime_amount: data.prime_amount,
            accessory_amount: data.accessory_amount,
            total_premium: data.total_premium,
            amounts: data.amounts ?? {},
        };
        seedEndorsementOverridesFromRecap();
    } catch {
        recap.value = {
            prime_amount: null,
            accessory_amount: null,
            total_premium: null,
            amounts: {},
        };
    } finally {
        const elapsed = Date.now() - startAt;
        const remaining = Math.max(0, PREVIEW_LOADER_MIN_MS - elapsed);
        if (remaining > 0) {
            setTimeout(() => {
                previewLoading.value = false;
            }, remaining);
        } else {
            previewLoading.value = false;
        }
    }

    recalcOptionalGuarantees();
}

/** Accessoire issu de la grille (non modifiable) */
const displayAccessory = computed(() => recap.value.accessory_amount ?? 0);

const companyAccessoryDisplay = computed(
    () => Number(form.company_accessory) || 0,
);
const agencyAccessoryDisplay = computed(
    () => Number(form.agency_accessory) || 0,
);
/** Total accessoires = grille (ou override) + compagnie + agence */
const totalAccessoryDisplay = computed(
    () =>
        displayAccessory.value +
        companyAccessoryDisplay.value +
        agencyAccessoryDisplay.value,
);

/** Montant total avant toute réduction (pour affichage réduction) */
const totalBeforeReduction = computed(
    () =>
        overrideOrFallback(
            form.rc_amount_override,
            recap.value.amounts?.rc_amount ?? 0,
        ) +
        overrideOrFallback(
            form.defence_appeal_amount_override,
            recap.value.amounts?.defence_appeal_amount ?? 0,
        ) +
        overrideOrFallback(
            form.person_transport_amount_override,
            recap.value.amounts?.person_transport_amount ?? 0,
        ) +
        optionalGuaranteesTotal.value,
);

const reductionOtherAmount = computed(() => Number(form.reduction_amount) || 0);

/** Montants par garantie : RC, DR, TP, puis chaque optionnelle */
const guaranteeAmounts = computed(() => {
    const amounts = [];
    const rc = overrideOrFallback(
        form.rc_amount_override,
        recap.value.amounts?.rc_amount ?? 0,
    );
    const dr = overrideOrFallback(
        form.defence_appeal_amount_override,
        recap.value.amounts?.defence_appeal_amount ?? 0,
    );
    const tp = overrideOrFallback(
        form.person_transport_amount_override,
        recap.value.amounts?.person_transport_amount ?? 0,
    );
    if (rc > 0) amounts.push({ key: "rc", amount: rc });
    if (dr > 0) amounts.push({ key: "dr", amount: dr });
    if (tp > 0) amounts.push({ key: "tp", amount: tp });
    (form.optional_guarantees_detail ?? []).forEach((g) => {
        const amt = Number(g?.amount ?? 0);
        if (amt > 0) amounts.push({ key: g.code, amount: amt });
    });
    return amounts;
});

/** Réductions appliquées sur chaque garantie, puis somme = prime nette */
const pctBns = computed(() => Number(form.reduction_bns) || 0);
const pctComm = computed(() => Number(form.reduction_on_commission) || 0);
const pctProf = computed(() => Number(form.reduction_on_profession_percent) || 0);
const profFixed = computed(() => Number(form.reduction_on_profession_amount) || 0);
const totalPct = computed(() => pctBns.value + pctComm.value + pctProf.value);
const hasPercentReduction = computed(() => totalPct.value > 0);

/** Pour chaque garantie : montant après réduction (réductions % + part de prof fixe) */
function amountAfterReductionForGuarantee(guaranteeAmount, totalGuarantees, index) {
    if (totalGuarantees <= 0) return guaranteeAmount;
    let reduced = guaranteeAmount;
    if (hasPercentReduction.value) {
        reduced = Math.max(0, guaranteeAmount - Math.round((guaranteeAmount * totalPct.value) / 100));
    }
    const profFixedAmt = profFixed.value;
    if (profFixedAmt > 0) {
        const share = Math.round((guaranteeAmount / totalGuarantees) * profFixedAmt);
        reduced = Math.max(0, reduced - share);
    }
    return reduced;
}

/** Garanties avec montants réduits pour affichage (chaque garantie réduite, somme = prime nette) */
const guaranteeDisplayItems = computed(() => {
    const amounts = guaranteeAmounts.value;
    const total = amounts.reduce((s, a) => s + a.amount, 0);
    const labels = {
        rc: "Responsabilité Civile",
        dr: "Défense et Recours",
        tp: "Transport de personnes",
    };
    const optionalDetail = form.optional_guarantees_detail ?? [];
    return amounts.map(({ key, amount }) => {
        let label = labels[key];
        if (!label) {
            label = optionalDetail.find((g) => g.code === key)?.label || "Autre garantie";
        }
        return {
            key,
            label: label || key,
            amountReduced: amountAfterReductionForGuarantee(amount, total, 0),
        };
    });
});

/** Prime nette = somme des (chaque garantie après réduction) */
const primeNetteCreate = computed(() => {
    const amounts = guaranteeAmounts.value;
    const total = amounts.reduce((s, a) => s + a.amount, 0);
    if (total <= 0) return totalBeforeReduction.value;
    let sum = 0;
    amounts.forEach(({ amount }) => {
        sum += amountAfterReductionForGuarantee(amount, total, 0);
    });
    return sum;
});

/** RC après réduction (pour FGA = 2% si réduction) */
const rcAfterReduction = computed(() => {
    const rc = overrideOrFallback(
        form.rc_amount_override,
        recap.value.amounts?.rc_amount ?? 0,
    );
    if (rc <= 0) return 0;
    const amounts = guaranteeAmounts.value;
    const total = amounts.reduce((s, a) => s + a.amount, 0);
    if (total <= 0) return rc;
    return amountAfterReductionForGuarantee(rc, total, 0);
});

/** Montant après réduction = prime nette (déjà réduite) - réduction "autre" si applicable */
const montantApresReductionCreate = computed(() =>
    Math.max(0, primeNetteCreate.value - reductionOtherAmount.value),
);

/** Accessoire grille (pour calcul taxe et total) */
const accessoryForTax = computed(
    () =>
        overrideOrFallback(
            form.accessory_amount_override,
            recap.value.amounts?.accessory_amount ??
                recap.value.accessory_amount ??
                0,
        ),
);

/** Taxe = 14,5 % de (prime nette + accessoire) */
const taxesAmountCreate = computed(() =>
    overrideOrFallback(
        form.taxes_amount_override,
        Math.round(
            (montantApresReductionCreate.value + accessoryForTax.value) * 0.145,
        ),
    ),
);

/** Taxe FGA = 2 % de la RC après réduction si réduction appliquée, sinon grille */
const fgaAmountCreate = computed(() => {
    if (form.fga_amount_override !== null && form.fga_amount_override !== "") {
        return Number(form.fga_amount_override) || 0;
    }
    const hasReduction = hasPercentReduction.value || profFixed.value > 0 || reductionOtherAmount.value > 0;
    if (hasReduction && rcAfterReduction.value > 0) {
        return Math.round(rcAfterReduction.value * 0.02);
    }
    return recap.value.amounts?.fga_amount ?? 0;
});

const cedeaoAmountCreate = computed(() =>
    overrideOrFallback(
        form.cedeao_amount_override,
        recap.value.amounts?.cedeao_amount ?? 0,
    ),
);

/** Prime TTC = Montant après réduction + Accessoire + Taxes + FGA + CEDEAO */
const displayTotal = computed(
    () =>
        montantApresReductionCreate.value +
        accessoryForTax.value +
        taxesAmountCreate.value +
        fgaAmountCreate.value +
        cedeaoAmountCreate.value,
);

watch(
    () => [form.vehicle_id, form.contract_type, form.start_date, form.end_date],
    () => {
        fetchPreview();
    },
    { deep: true },
);

watch(
    () => [form.duration, form.start_date],
    () => {
        applyDuration();
    },
    { deep: true },
);

watch(
    () => form.contract_type,
    (type) => {
        if (type === "TWO_WHEELER") {
            form.duration = "12_months";
            applyDuration();
        }
        if (type !== "TPM") {
            form.is_double_cabine = false;
            form.second_vehicle_id = "";
        }
    },
);

watch(
    () => form.start_date,
    () => {
        if (
            !isEndorsementMode.value ||
            !props.parentContract?.end_date
        ) {
            return;
        }
        form.end_date = formatDateForEndorsement(
            props.parentContract.end_date,
        );
    },
);

watch(
    () => [
        vehicleNewValue.value,
        vehicleVenaleValue.value,
        ...optionalGuaranteeDefs.value.map(
            (def) => optionalGuarantees.value[def.code]?.enabled,
        ),
    ],
    () => {
        recalcOptionalGuarantees();
    },
);

const inputClass =
    "w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none";
const inputErrorClass =
    "border-red-400 focus:border-red-400 focus:ring-red-400";

async function submitValidate() {
    const ok = await persistEndorsementMasterDataIfNeeded();
    if (!ok) return;
    form.status = "validated";
    form.transform((data) => ({
        ...data,
        creation_mode: props.parentContract?.creation_mode ?? null,
        // Toujours envoyer parent_id en renouvellement pour que le contrat soit bien lié
        parent_id:
            data.parent_id ||
            (props.parentContract?.id ? String(props.parentContract.id) : null),
    }));
    form.post(route("contracts.store"), { preserveScroll: true });
}

async function submitDraft() {
    const ok = await persistEndorsementMasterDataIfNeeded();
    if (!ok) return;
    form.status = "draft";
    form.transform((data) => ({
        ...data,
        creation_mode: props.parentContract?.creation_mode ?? null,
        parent_id:
            data.parent_id ||
            (props.parentContract?.id ? String(props.parentContract.id) : null),
    }));
    form.post(route("contracts.store"), { preserveScroll: true });
}

/** Soumission formulaire (Entrée ou comportement natif) — évite les blocages de validation HTML sur l’avenant. */
function onFormSubmit() {
    if (isEndorsementMode.value) {
        submitValidate();
        return;
    }
    if (step.value === 1) {
        step.value = 2;
        return;
    }
    submitValidate();
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Nouveau contrat" />
        </template>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Formulaire principal -->
            <form
                class="flex-1 space-y-6"
                :novalidate="isEndorsementMode"
                @submit.prevent="onFormSubmit"
            >
                <!-- Wizard : une seule étape visible à la fois -->
                <!-- Étape 1 : Client, véhicule, période, compagnie -->
                <div
                    v-show="!isEndorsementMode && step === 1"
                    class="rounded-xl border border-slate-200 bg-white p-6 space-y-4"
                >
                    <h2
                        class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-2"
                    >
                        Étape 1 — Client, véhicule & couverture
                    </h2>
                    <div>
                        <label
                            class="block text-sm font-medium text-slate-700 mb-1"
                            >Client *</label
                        >
                        <SearchableSelect
                            v-model="form.client_id"
                            :options="localClients"
                            value-key="id"
                            label-key="full_name"
                            placeholder="Sélectionner un client"
                            :required="true"
                            :error="!!form.errors.client_id"
                            :input-class="inputClass"
                            search-placeholder="Rechercher un client…"
                            @change="onClientChange"
                        />
                        <p
                            v-if="form.errors.client_id"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.client_id }}
                        </p>
                        <button
                            type="button"
                            class="mt-2 text-sm font-medium text-slate-600 hover:text-slate-900 inline-flex items-center gap-1"
                            @click="openClientDrawer"
                        >
                            + Nouveau client
                        </button>
                        <Link
                            v-if="isEndorsementMode && form.client_id"
                            :href="route('clients.edit', form.client_id)"
                            class="mt-2 ml-3 text-sm font-medium text-sky-700 hover:text-sky-900 inline-flex items-center gap-1"
                        >
                            Modifier le client
                        </Link>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-slate-700 mb-1"
                            >Véhicule *</label
                        >
                        <SearchableSelect
                            v-model="form.vehicle_id"
                            :options="vehiclesForSelect"
                            value-key="id"
                            label-key="name"
                            placeholder="Choisir un véhicule du client"
                            :required="true"
                            :error="!!form.errors.vehicle_id"
                            :input-class="inputClass"
                            :disabled="!form.client_id"
                            search-placeholder="Rechercher…"
                            @change="onVehicleChange"
                        />
                        <p
                            v-if="form.errors.vehicle_id"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.vehicle_id }}
                        </p>
                        <p
                            v-if="form.client_id && !vehiclesForClient.length"
                            class="mt-1 text-sm text-amber-600"
                        >
                            Ce client n'a aucun véhicule.
                        </p>
                        <button
                            v-if="form.client_id"
                            type="button"
                            class="mt-2 text-sm font-medium text-slate-600 hover:text-slate-900 inline-flex items-center gap-1"
                            @click="openVehicleDrawer"
                        >
                            + Nouveau véhicule
                        </button>
                        <Link
                            v-if="isEndorsementMode && form.vehicle_id"
                            :href="route('vehicles.edit', form.vehicle_id)"
                            class="mt-2 ml-3 text-sm font-medium text-sky-700 hover:text-sky-900 inline-flex items-center gap-1"
                        >
                            Modifier le véhicule
                        </Link>
                        <p
                            v-if="form.vehicle_id && form.contract_type"
                            class="mt-2 flex items-center gap-2"
                        >
                            <span class="text-xs text-slate-500"
                                >Type :
                                {{ contractTypeLabel(form.contract_type) }} —
                                Attestation :</span
                            >
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium',
                                    attestationColorClasses(form.contract_type),
                                ]"
                            >
                                {{ attestationColorLabel(form.contract_type) }}
                            </span>
                        </p>
                    </div>

                    <!-- Double cabine (TPM uniquement) -->
                    <div v-if="isTPM">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                v-model="form.is_double_cabine"
                                class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                            />
                            <span class="text-sm font-medium text-slate-700">Double cabine</span>
                        </label>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Cocher si le véhicule est une double cabine : vous pouvez alors associer un second véhicule TPM. La prime est calculée sur le véhicule principal (avec charge utile).
                        </p>
                    </div>

                    <!-- Second véhicule (double cabine activée) -->
                    <div v-if="isTPM && form.is_double_cabine">
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Second véhicule TPM *
                        </label>
                        <SearchableSelect
                            v-model="form.second_vehicle_id"
                            :options="vehiclesForSecondSelect"
                            value-key="id"
                            label-key="name"
                            placeholder="Choisir le second véhicule TPM"
                            :required="true"
                            :error="!!form.errors.second_vehicle_id"
                            :input-class="inputClass"
                            :disabled="!form.client_id"
                            search-placeholder="Rechercher…"
                        />
                        <p
                            v-if="form.errors.second_vehicle_id"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.second_vehicle_id }}
                        </p>
                        <p
                            v-if="vehiclesForSecondSelect.length === 0 && form.vehicle_id"
                            class="mt-1 text-xs text-amber-600"
                        >
                            Aucun autre véhicule TPM disponible pour ce client.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Période de couverture *</label
                            >
                            <select
                                v-model="form.duration"
                                :class="inputClass"
                                :disabled="isTwoWheeler"
                                @change="applyDuration"
                            >
                                <option
                                    v-for="opt in durationOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </option>
                            </select>
                            <p
                                v-if="isTwoWheeler"
                                class="mt-1 text-xs text-slate-500"
                            >
                                Durée annuelle obligatoire pour deux roues
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Date d'effet *</label
                            >
                            <DatePicker
                                v-model="form.start_date"
                                placeholder="Sélectionner une date"
                                :error="!!form.errors.start_date"
                                :input-class="inputClass"
                                :year-range="[2020, 2030]"
                                :min="todayYMD"
                            />
                            <p
                                v-if="form.errors.start_date"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.start_date }}
                            </p>
                        </div>
                    </div>
                    <div v-if="form.end_date">
                        <label
                            class="block text-sm font-medium text-slate-700 mb-1"
                            >Date d'échéance</label
                        >
                        <p class="text-sm font-medium text-slate-900">
                            {{ form.end_date }}
                        </p>
                        <p class="text-xs text-slate-500">
                            Calculée automatiquement selon la période et la date
                            d'effet.
                        </p>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-slate-700 mb-1"
                            >Compagnie *</label
                        >
                        <SearchableSelect
                            v-model="form.company_id"
                            :options="companies"
                            value-key="id"
                            label-key="name"
                            image-key="logo_url"
                            placeholder="Sélectionner une compagnie"
                            :required="true"
                            :error="!!form.errors.company_id"
                            :input-class="inputClass"
                            search-placeholder="Rechercher une compagnie…"
                        />
                        <p
                            v-if="form.errors.company_id"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.company_id }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800"
                            @click="step = 2"
                        >
                            Suivant
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50"
                            :disabled="!canSaveDraft || form.processing"
                            @click="submitDraft"
                        >
                            {{
                                form.processing
                                    ? "Enregistrement…"
                                    : "Enregistrer en brouillon"
                            }}
                        </button>
                    </div>
                </div>

                <!-- Étape 2 : Accessoires, garanties et réductions -->
                <div
                    v-show="isEndorsementMode || step === 2"
                    class="rounded-xl border border-slate-200 bg-white p-6 space-y-4"
                >
                    <h2
                        class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-2"
                    >
                        {{
                            isEndorsementMode
                                ? "Avenant — Type & informations"
                                : "Étape 2 — Accessoires, garanties & réductions"
                        }}
                    </h2>
                    <div
                        v-if="isEndorsementMode && parentContract"
                        class="rounded-lg border border-slate-200 bg-slate-50/80 p-4 grid grid-cols-1 md:grid-cols-2 gap-4"
                    >
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Date d'effet de l'avenant *</label
                            >
                            <DatePicker
                                v-model="form.start_date"
                                placeholder="À partir d'aujourd'hui"
                                :error="!!form.errors.start_date"
                                :input-class="inputClass"
                                :year-range="endorsementDatePickerYearRange"
                                :min="todayYMD"
                                :max="endorsementStartDateMax"
                            />
                            <p
                                v-if="form.errors.start_date"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.start_date }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                À partir de la date du jour, jusqu’à
                                l’échéance du contrat de base.
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Date d'échéance (contrat de base)</label
                            >
                            <p
                                class="text-sm font-semibold text-slate-900 py-2"
                            >
                                {{
                                    form.end_date
                                        ? form.end_date
                                        : parentContract.end_date ?? "—"
                                }}
                            </p>
                            <p class="text-xs text-slate-500">
                                Alignée sur le contrat parent ; aucune période
                                (1 mois, 12 mois…) pour un avenant.
                            </p>
                        </div>
                    </div>
                    <div v-if="isEndorsementMode">
                        <label
                            class="block text-sm font-medium text-slate-700 mb-1"
                        >
                            Type d'avenant *
                        </label>
                        <select
                            v-model="form.endorsement_type"
                            :class="[
                                inputClass,
                                form.errors.endorsement_type && inputErrorClass,
                            ]"
                            required
                        >
                            <option value="">Sélectionner un type</option>
                            <option value="registration_change">
                                Changement d'immatriculation
                            </option>
                            <option value="vehicle_info_update">
                                Mise à jour infos véhicule
                            </option>
                            <option value="client_info_update">
                                Mise à jour infos client
                            </option>
                            <option value="other">Autre</option>
                        </select>
                        <p
                            v-if="form.errors.endorsement_type"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.endorsement_type }}
                        </p>
                        <p
                            v-if="form.errors.endorsement_vehicle"
                            class="mt-2 text-sm text-red-600"
                        >
                            {{ form.errors.endorsement_vehicle }}
                        </p>
                        <p
                            v-if="form.errors.endorsement_client"
                            class="mt-2 text-sm text-red-600"
                        >
                            {{ form.errors.endorsement_client }}
                        </p>
                        <div class="mt-3 space-y-3">
                            <div
                                v-if="needsVehicleUpdateForEndorsement && form.vehicle_id"
                                class="rounded border border-slate-200 bg-slate-50 p-4 space-y-3"
                            >
                                <h3 class="text-sm font-semibold text-slate-800">
                                    {{
                                        form.endorsement_type ===
                                        "registration_change"
                                            ? "Mise à jour — immatriculation & carte grise"
                                            : "Mise à jour — informations véhicule"
                                    }}
                                </h3>
                                <p
                                    v-if="endorsementVehicleLoading"
                                    class="text-sm text-slate-600"
                                >
                                    Chargement du véhicule…
                                </p>
                                <p
                                    v-else-if="endorsementVehicleErr('general')"
                                    class="text-sm text-red-600"
                                >
                                    {{ endorsementVehicleErr("general") }}
                                </p>
                                <div
                                    v-else
                                    :key="'ev-' + String(endorsementVehicleLoadedId ?? '')"
                                    class="space-y-4"
                                >
                                    <div
                                        v-if="
                                            form.endorsement_type ===
                                            'registration_change'
                                        "
                                        class="grid grid-cols-1 md:grid-cols-2 gap-3"
                                    >
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-slate-700 mb-1"
                                                >Immatriculation *</label
                                            >
                                            <input
                                                v-model="
                                                    endorsementVehicleForm.registration_number
                                                "
                                                type="text"
                                                :class="[
                                                    inputClass,
                                                    endorsementVehicleErr(
                                                        'registration_number',
                                                    ) && inputErrorClass,
                                                ]"
                                            />
                                            <p
                                                v-if="
                                                    endorsementVehicleErr(
                                                        'registration_number',
                                                    )
                                                "
                                                class="mt-1 text-xs text-red-600"
                                            >
                                                {{
                                                    endorsementVehicleErr(
                                                        "registration_number",
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-slate-700 mb-1"
                                                >N° carte grise</label
                                            >
                                            <input
                                                v-model="
                                                    endorsementVehicleForm.registration_card_number
                                                "
                                                type="text"
                                                :class="inputClass"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-slate-700 mb-1"
                                                >N° châssis</label
                                            >
                                            <input
                                                v-model="
                                                    endorsementVehicleForm.chassis_number
                                                "
                                                type="text"
                                                :class="inputClass"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-slate-700 mb-1"
                                                >Date 1ère mise en circulation *</label
                                            >
                                            <DatePicker
                                                v-model="
                                                    endorsementVehicleForm.first_registration_date
                                                "
                                                placeholder="Sélectionner une date"
                                                :input-class="inputClass"
                                                :year-range="[1990, 2030]"
                                            />
                                            <p
                                                v-if="
                                                    endorsementVehicleErr(
                                                        'first_registration_date',
                                                    )
                                                "
                                                class="mt-1 text-xs text-red-600"
                                            >
                                                {{
                                                    endorsementVehicleErr(
                                                        "first_registration_date",
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        v-else-if="
                                            form.endorsement_type ===
                                            'vehicle_info_update'
                                        "
                                        class="space-y-4"
                                    >
                                        <fieldset class="space-y-3">
                                            <legend
                                                class="text-xs font-semibold text-slate-700 border-b border-slate-200 pb-1 w-full"
                                                >Informations générales</legend
                                            >
                                            <div
                                                class="grid grid-cols-1 md:grid-cols-2 gap-3"
                                            >
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Type *</label
                                                    >
                                                    <SearchableSelect
                                                        v-model="
                                                            endorsementVehicleForm.pricing_type
                                                        "
                                                        :options="pricingTypeOptions"
                                                        value-key="value"
                                                        label-key="label"
                                                        placeholder="Type"
                                                        :input-class="inputClass"
                                                        search-placeholder="Rechercher…"
                                                    />
                                                    <p
                                                        v-if="
                                                            endorsementVehicleErr(
                                                                'pricing_type',
                                                            )
                                                        "
                                                        class="mt-1 text-xs text-red-600"
                                                    >
                                                        {{
                                                            endorsementVehicleErr(
                                                                "pricing_type",
                                                            )
                                                        }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Immatriculation *</label
                                                    >
                                                    <input
                                                        v-model="
                                                            endorsementVehicleForm.registration_number
                                                        "
                                                        type="text"
                                                        :class="inputClass"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Marque *</label
                                                    >
                                                    <SearchableSelect
                                                        v-model="
                                                            endorsementVehicleForm.vehicle_brand_id
                                                        "
                                                        :options="
                                                            endorsementVehicleBrandsOptions
                                                        "
                                                        value-key="id"
                                                        label-key="name"
                                                        placeholder="Marque"
                                                        :input-class="inputClass"
                                                        search-placeholder="Rechercher…"
                                                        @change="
                                                            onEndorsementVehicleBrandChange
                                                        "
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Modèle *</label
                                                    >
                                                    <SearchableSelect
                                                        v-model="
                                                            endorsementVehicleForm.vehicle_model_id
                                                        "
                                                        :options="
                                                            modelsForEndorsementVehicle
                                                        "
                                                        value-key="id"
                                                        label-key="name"
                                                        placeholder="Modèle"
                                                        :disabled="
                                                            !endorsementVehicleForm.vehicle_brand_id
                                                        "
                                                        :input-class="inputClass"
                                                        search-placeholder="Rechercher…"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Carrosserie *</label
                                                    >
                                                    <input
                                                        v-model="
                                                            endorsementVehicleForm.body_type
                                                        "
                                                        type="text"
                                                        :class="inputClass"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Couleur *</label
                                                    >
                                                    <SearchableSelect
                                                        v-model="
                                                            endorsementVehicleForm.color_id
                                                        "
                                                        :options="
                                                            endorsementVehicleColorsOptions
                                                        "
                                                        value-key="id"
                                                        label-key="name"
                                                        placeholder="Couleur"
                                                        :input-class="inputClass"
                                                        search-placeholder="Rechercher…"
                                                    />
                                                </div>
                                            </div>
                                        </fieldset>
                                        <fieldset class="space-y-3">
                                            <legend
                                                class="text-xs font-semibold text-slate-700 border-b border-slate-200 pb-1 w-full"
                                                >Spécifications techniques</legend
                                            >
                                            <div
                                                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3"
                                            >
                                                <div
                                                    v-if="
                                                        !endorsementVehicleForm.pricing_type ||
                                                        endorsementVehicleForm.pricing_type ===
                                                            'TPC' ||
                                                        endorsementVehicleForm.pricing_type ===
                                                            'TPM'
                                                    "
                                                >
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Charge utile (t)</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            endorsementVehicleForm.payload_capacity
                                                        "
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        :class="inputClass"
                                                    />
                                                </div>
                                                <div
                                                    v-if="
                                                        !endorsementVehicleForm.pricing_type ||
                                                        endorsementVehicleForm.pricing_type ===
                                                            'VP'
                                                    "
                                                >
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Énergie</label
                                                    >
                                                    <SearchableSelect
                                                        v-model="
                                                            endorsementVehicleForm.energy_source_id
                                                        "
                                                        :options="
                                                            endorsementVehicleEnergySourcesOptions
                                                        "
                                                        value-key="id"
                                                        label-key="name"
                                                        placeholder="Énergie"
                                                        :input-class="inputClass"
                                                        search-placeholder="Rechercher…"
                                                    />
                                                </div>
                                                <div
                                                    v-if="
                                                        !endorsementVehicleForm.pricing_type ||
                                                        endorsementVehicleForm.pricing_type ===
                                                            'TWO_WHEELER'
                                                    "
                                                >
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Cylindrée (cm³)</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            endorsementVehicleForm.engine_capacity
                                                        "
                                                        type="number"
                                                        min="0"
                                                        :class="inputClass"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Places *</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            endorsementVehicleForm.seat_count
                                                        "
                                                        type="number"
                                                        min="0"
                                                        :class="inputClass"
                                                    />
                                                </div>
                                            </div>
                                        </fieldset>
                                        <fieldset class="space-y-3">
                                            <legend
                                                class="text-xs font-semibold text-slate-700 border-b border-slate-200 pb-1 w-full"
                                                >Classification</legend
                                            >
                                            <div
                                                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3"
                                            >
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Usage *</label
                                                    >
                                                    <SearchableSelect
                                                        v-model="
                                                            endorsementVehicleForm.vehicle_usage_id
                                                        "
                                                        :options="
                                                            endorsementVehicleUsagesOptions
                                                        "
                                                        value-key="id"
                                                        label-key="name"
                                                        placeholder="Usage"
                                                        :input-class="inputClass"
                                                        search-placeholder="Rechercher…"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Catégorie *</label
                                                    >
                                                    <SearchableSelect
                                                        v-model="
                                                            endorsementVehicleForm.vehicle_category_id
                                                        "
                                                        :options="
                                                            endorsementVehicleCategoriesOptions
                                                        "
                                                        value-key="id"
                                                        label-key="name"
                                                        placeholder="Catégorie"
                                                        :input-class="inputClass"
                                                        search-placeholder="Rechercher…"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Genre *</label
                                                    >
                                                    <SearchableSelect
                                                        v-model="
                                                            endorsementVehicleForm.vehicle_gender_id
                                                        "
                                                        :options="
                                                            endorsementVehicleGendersOptions
                                                        "
                                                        value-key="id"
                                                        label-key="name"
                                                        placeholder="Genre"
                                                        :input-class="inputClass"
                                                        search-placeholder="Rechercher…"
                                                    />
                                                </div>
                                            </div>
                                            <div
                                                class="grid grid-cols-1 md:grid-cols-2 gap-3"
                                            >
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Type véhicule *</label
                                                    >
                                                    <SearchableSelect
                                                        v-model="
                                                            endorsementVehicleForm.vehicle_type_id
                                                        "
                                                        :options="
                                                            endorsementVehicleTypesOptions
                                                        "
                                                        value-key="id"
                                                        label-key="name"
                                                        placeholder="Type"
                                                        :input-class="inputClass"
                                                        search-placeholder="Rechercher…"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Zone de circulation</label
                                                    >
                                                    <SearchableSelect
                                                        v-model="
                                                            endorsementVehicleForm.circulation_zone_id
                                                        "
                                                        :options="
                                                            endorsementVehicleCirculationZonesOptions
                                                        "
                                                        value-key="id"
                                                        label-key="name"
                                                        placeholder="Zone"
                                                        :input-class="inputClass"
                                                        search-placeholder="Rechercher…"
                                                    />
                                                </div>
                                            </div>
                                        </fieldset>
                                        <fieldset class="space-y-3">
                                            <legend
                                                class="text-xs font-semibold text-slate-700 border-b border-slate-200 pb-1 w-full"
                                                >Informations techniques</legend
                                            >
                                            <div
                                                class="grid grid-cols-1 md:grid-cols-2 gap-3"
                                            >
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Date 1ère mise en circulation *</label
                                                    >
                                                    <DatePicker
                                                        v-model="
                                                            endorsementVehicleForm.first_registration_date
                                                        "
                                                        placeholder="Date"
                                                        :input-class="inputClass"
                                                        :year-range="[1990, 2030]"
                                                    />
                                                </div>
                                                <div
                                                    v-if="
                                                        !endorsementVehicleForm.pricing_type ||
                                                        endorsementVehicleForm.pricing_type ===
                                                            'VP'
                                                    "
                                                >
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Puissance fiscale (CV)</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            endorsementVehicleForm.fiscal_power
                                                        "
                                                        type="number"
                                                        min="0"
                                                        :class="inputClass"
                                                    />
                                                </div>
                                            </div>
                                            <div
                                                class="grid grid-cols-1 md:grid-cols-2 gap-3"
                                            >
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >N° carte grise</label
                                                    >
                                                    <input
                                                        v-model="
                                                            endorsementVehicleForm.registration_card_number
                                                        "
                                                        type="text"
                                                        :class="inputClass"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >N° châssis</label
                                                    >
                                                    <input
                                                        v-model="
                                                            endorsementVehicleForm.chassis_number
                                                        "
                                                        type="text"
                                                        :class="inputClass"
                                                    />
                                                </div>
                                            </div>
                                        </fieldset>
                                        <fieldset class="space-y-3">
                                            <legend
                                                class="text-xs font-semibold text-slate-700 border-b border-slate-200 pb-1 w-full"
                                                >Valeurs financières</legend
                                            >
                                            <div
                                                class="grid grid-cols-1 md:grid-cols-2 gap-3"
                                            >
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Valeur neuve</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            endorsementVehicleForm.new_value
                                                        "
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        :class="inputClass"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-slate-700 mb-1"
                                                        >Valeur de remplacement</label
                                                    >
                                                    <input
                                                        v-model.number="
                                                            endorsementVehicleForm.replacement_value
                                                        "
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        :class="inputClass"
                                                    />
                                                </div>
                                            </div>
                                        </fieldset>
                                    </div>
                                    <div
                                        class="flex flex-wrap items-center gap-3 pt-3 border-t border-slate-200"
                                    >
                                        <button
                                            type="button"
                                            class="px-3 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                                            :disabled="
                                                endorsementVehicleSaving ||
                                                endorsementVehicleLoading
                                            "
                                            @click="saveEndorsementVehicleManual"
                                        >
                                            {{
                                                endorsementVehicleSaving
                                                    ? "Enregistrement…"
                                                    : "Enregistrer le véhicule"
                                            }}
                                        </button>
                                    </div>
                                </div>
                                <p
                                    v-if="endorsementVehicleSaving"
                                    class="text-xs text-slate-500"
                                >
                                    Enregistrement du véhicule avant création du contrat…
                                </p>
                            </div>
                            <div
                                v-if="needsClientUpdateForEndorsement && form.client_id"
                                class="rounded border border-slate-200 bg-slate-50 p-4 space-y-3"
                            >
                                <h3 class="text-sm font-semibold text-slate-800">
                                    Mise à jour — informations client
                                </h3>
                                <p
                                    v-if="endorsementClientLoading"
                                    class="text-sm text-slate-600"
                                >
                                    Chargement du client…
                                </p>
                                <p
                                    v-else-if="endorsementClientErr('general')"
                                    class="text-sm text-red-600"
                                >
                                    {{ endorsementClientErr("general") }}
                                </p>
                                <div
                                    v-else
                                    :key="'ec-' + String(endorsementClientLoadedId ?? '')"
                                    class="space-y-3"
                                >
                                    <div
                                        class="flex flex-wrap gap-4 pb-2 border-b border-slate-200"
                                    >
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                v-model="
                                                    endorsementClientForm.type_assure
                                                "
                                                type="radio"
                                                :value="TYPE_TAPP"
                                                class="rounded-full border-slate-300 text-slate-900 focus:ring-slate-400"
                                            />
                                            <span class="text-sm text-slate-700"
                                                >Personne physique (TAPP)</span
                                            >
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                v-model="
                                                    endorsementClientForm.type_assure
                                                "
                                                type="radio"
                                                :value="TYPE_TAPM"
                                                class="rounded-full border-slate-300 text-slate-900 focus:ring-slate-400"
                                            />
                                            <span class="text-sm text-slate-700"
                                                >Personne morale (TAPM)</span
                                            >
                                        </label>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-slate-700 mb-1"
                                            >{{ endorsementClientNameLabel }}</label
                                        >
                                        <input
                                            v-model="endorsementClientForm.full_name"
                                            type="text"
                                            :class="inputClass"
                                        />
                                        <p
                                            v-if="endorsementClientErr('full_name')"
                                            class="mt-1 text-xs text-red-600"
                                        >
                                            {{ endorsementClientErr("full_name") }}
                                        </p>
                                    </div>
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 gap-3"
                                    >
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-slate-700 mb-1"
                                                >Email</label
                                            >
                                            <input
                                                v-model="endorsementClientForm.email"
                                                type="email"
                                                :class="inputClass"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-slate-700 mb-1"
                                                >Téléphone</label
                                            >
                                            <input
                                                v-model="endorsementClientForm.phone"
                                                type="text"
                                                :class="inputClass"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-slate-700 mb-1"
                                            >Adresse</label
                                        >
                                        <input
                                            v-model="endorsementClientForm.address"
                                            type="text"
                                            :class="inputClass"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-slate-700 mb-1"
                                            >Code postal</label
                                        >
                                        <input
                                            v-model="
                                                endorsementClientForm.postal_address
                                            "
                                            type="text"
                                            :class="inputClass"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-slate-700 mb-1"
                                            >{{ endorsementClientProfessionLabel }}</label
                                        >
                                        <input
                                            v-model="
                                                endorsementClientForm.profession
                                            "
                                            type="text"
                                            :class="inputClass"
                                        />
                                        <p
                                            v-if="endorsementClientErr('profession')"
                                            class="mt-1 text-xs text-red-600"
                                        >
                                            {{
                                                endorsementClientErr("profession")
                                            }}
                                        </p>
                                    </div>
                                    <div
                                        class="flex flex-wrap items-center gap-3 pt-3 border-t border-slate-200"
                                    >
                                        <button
                                            type="button"
                                            class="px-3 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                                            :disabled="
                                                endorsementClientSaving ||
                                                endorsementClientLoading
                                            "
                                            @click="saveEndorsementClientManual"
                                        >
                                            {{
                                                endorsementClientSaving
                                                    ? "Enregistrement…"
                                                    : "Enregistrer le client"
                                            }}
                                        </button>
                                    </div>
                                </div>
                                <p
                                    v-if="endorsementClientSaving"
                                    class="text-xs text-slate-500"
                                >
                                    Enregistrement du client avant création du contrat…
                                </p>
                            </div>
                        </div>
                    </div>
                    <p v-if="!isEndorsementMode" class="text-xs text-slate-500">
                        Accessoires compagnie et agence (FCFA). Réductions
                        ci‑dessous.
                    </p>
                    <div
                        v-if="!isEndorsementMode"
                        class="grid grid-cols-1 md:grid-cols-2 gap-4"
                    >
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Accessoires compagnie (FCFA)</label
                            >
                            <input
                                v-model.number="form.company_accessory"
                                type="number"
                                min="0"
                                step="1"
                                :class="[
                                    inputClass,
                                    form.errors.company_accessory &&
                                        inputErrorClass,
                                ]"
                            />
                            <p
                                v-if="form.errors.company_accessory"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.company_accessory }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Accessoires agence (FCFA)</label
                            >
                            <input
                                v-model.number="form.agency_accessory"
                                type="number"
                                min="0"
                                step="1"
                                :class="[
                                    inputClass,
                                    form.errors.agency_accessory &&
                                        inputErrorClass,
                                ]"
                            />
                            <p
                                v-if="form.errors.agency_accessory"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.agency_accessory }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="!isEndorsementMode"
                        class="pt-2 border-t border-slate-200 space-y-3"
                    >
                        <h3 class="text-sm font-medium text-slate-700">
                            Garanties
                        </h3>
                        <p class="text-xs text-slate-500">
                            Cochez les garanties souhaitées. Les montants sont
                            calculés à partir de la valeur neuve ou vénale du
                            véhicule.
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2" v-if="anyGuaranteeUsingNew">
                                <label
                                    class="block text-sm font-medium text-slate-700 mb-1"
                                >
                                    Valeur neuve du véhicule (FCFA)
                                </label>
                                <input
                                    v-model.number="vehicleNewValue"
                                    type="number"
                                    min="0"
                                    step="1"
                                    :class="inputClass"
                                    placeholder="Ex. 10 000 000"
                                />
                                <p
                                    v-if="missingNewBase"
                                    class="text-xs text-amber-600"
                                >
                                    Requis pour les garanties basées sur la
                                    valeur neuve.
                                </p>
                            </div>
                            <div
                                class="space-y-2"
                                v-if="anyGuaranteeUsingVenale"
                            >
                                <label
                                    class="block text-sm font-medium text-slate-700 mb-1"
                                >
                                    Valeur vénale du véhicule (FCFA)
                                </label>
                                <input
                                    v-model.number="vehicleVenaleValue"
                                    type="number"
                                    min="0"
                                    step="1"
                                    :class="inputClass"
                                    placeholder="Ex. 8 000 000"
                                />
                                <p
                                    v-if="missingVenaleBase"
                                    class="text-xs text-amber-600"
                                >
                                    Requis pour les garanties basées sur la
                                    valeur vénale.
                                </p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="(def, idx) in optionalGuaranteeDefs"
                                :key="def?.code || idx"
                                class="flex items-center justify-between gap-3 border border-slate-200 rounded-lg px-3 py-2 bg-slate-50"
                            >
                                <div class="flex items-start gap-2">
                                    <input
                                        v-model="
                                            optionalGuarantees[def.code].enabled
                                        "
                                        type="checkbox"
                                        class="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-500"
                                    />
                                    <div>
                                        <p
                                            class="text-sm font-medium text-slate-800"
                                        >
                                            {{ def.label }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            {{ def.rate }} % de la
                                            {{
                                                def.base === "new"
                                                    ? "valeur neuve"
                                                    : "valeur vénale"
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right text-sm">
                                    <p class="text-slate-400 text-xs">
                                        Montant
                                    </p>
                                    <p class="font-medium text-slate-900">
                                        {{
                                            (
                                                optionalGuarantees[def.code]
                                                    .amount || 0
                                            ).toLocaleString("fr-FR")
                                        }}
                                        FCFA
                                    </p>
                                </div>
                            </div>
                            <p
                                v-if="optionalGuaranteesTotal > 0"
                                class="text-xs text-slate-600 text-right"
                            >
                                Total garanties optionnelles :
                                <span class="font-semibold">
                                    {{
                                        optionalGuaranteesTotal.toLocaleString(
                                            "fr-FR",
                                        )
                                    }}
                                    FCFA
                                </span>
                            </p>
                        </div>
                    </div>
                    <template v-if="!isEndorsementMode">
                        <h3 class="text-sm font-medium text-slate-700 pt-2">
                            Réductions
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >(%) Réduction BNS</label
                            >
                            <input
                                v-model.number="form.reduction_bns"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                :class="[
                                    inputClass,
                                    form.errors.reduction_bns &&
                                        inputErrorClass,
                                ]"
                                placeholder="0–100"
                            />
                            <p
                                v-if="form.errors.reduction_bns"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.reduction_bns }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >(%) Réduction sur commission</label
                            >
                            <input
                                v-model.number="form.reduction_on_commission"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                :class="[
                                    inputClass,
                                    form.errors.reduction_on_commission &&
                                        inputErrorClass,
                                ]"
                                placeholder="0–100"
                            />
                            <p
                                v-if="form.errors.reduction_on_commission"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.reduction_on_commission }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >(%) Réduction profession</label
                            >
                            <input
                                v-model.number="
                                    form.reduction_on_profession_percent
                                "
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                :class="[
                                    inputClass,
                                    form.errors
                                        .reduction_on_profession_percent &&
                                        inputErrorClass,
                                ]"
                                placeholder="0–100"
                            />
                            <p
                                v-if="
                                    form.errors.reduction_on_profession_percent
                                "
                                class="mt-1 text-sm text-red-600"
                            >
                                {{
                                    form.errors.reduction_on_profession_percent
                                }}
                            </p>
                        </div>
                    </div>
                    </template>
                    <div class="flex flex-wrap gap-3 pt-2">
                        <button
                            v-if="!isEndorsementMode"
                            type="button"
                            class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50"
                            @click="step = 1"
                        >
                            ← Précédent
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 disabled:opacity-50"
                            :disabled="form.processing"
                            @click.prevent="submitValidate()"
                        >
                            {{
                                form.processing
                                    ? "Validation…"
                                    : isEndorsementMode
                                      ? "Valider l'avenant"
                                      : "Valider le contrat"
                            }}
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50"
                            :disabled="form.processing"
                            @click="submitDraft"
                        >
                            Enregistrer en brouillon
                        </button>
                    </div>
                </div>
            </form>

            <!-- Récap à droite (largeur élargie pour que les montants tiennent sur une ligne) -->
            <aside class="lg:w-[28rem] shrink-0">
                <div
                    class="rounded-xl border border-slate-200 bg-slate-50 p-5 sticky top-4 space-y-4"
                >
                    <h3 class="text-sm font-semibold text-slate-800">
                        {{
                            isEndorsementMode
                                ? "Montant — Avenant"
                                : "Récapitulatif prime"
                        }}
                    </h3>
                    <template v-if="previewLoading && !isEndorsementMode">
                        <div
                            class="flex flex-col items-center justify-center py-8 gap-3"
                        >
                            <div
                                class="w-10 h-10 border-2 border-slate-300 border-t-slate-700 rounded-full animate-spin"
                                aria-hidden="true"
                            />
                            <p class="text-sm text-slate-500">
                                Calcul en cours…
                            </p>
                        </div>
                    </template>
                    <template v-else-if="isEndorsementMode && canPreview">
                        <p class="text-xs text-slate-600">
                            Seule la CEDEAO est facturée sur cet avenant.
                        </p>
                        <dl class="space-y-2 text-sm pt-2">
                            <div
                                class="flex justify-between gap-2 pt-2 border-t-2 border-slate-200"
                            >
                                <dt class="font-semibold text-slate-800">
                                    CEDEAO
                                </dt>
                                <dd
                                    class="font-semibold text-slate-900 shrink-0 whitespace-nowrap"
                                >
                                    {{
                                        ENDORSEMENT_CEDEAO_FIXED_FCFA.toLocaleString(
                                            "fr-FR",
                                        )
                                    }}
                                    FCFA
                                </dd>
                            </div>
                        </dl>
                    </template>
                    <template
                        v-else-if="
                            !isEndorsementMode && recap.total_premium != null
                        "
                    >
                        <!-- Garanties (comme sur le PDF) -->
                        <div v-if="primeNetteCreate > 0" class="space-y-2">
                            <h4
                                class="text-xs font-semibold text-slate-600 uppercase tracking-wide"
                            >
                                Garanties
                            </h4>
                            <dl class="space-y-1.5 text-sm">
                                <div
                                    v-for="item in guaranteeDisplayItems"
                                    :key="item.key"
                                    class="flex justify-between gap-2"
                                >
                                    <dt class="text-slate-600 truncate">
                                        {{ item.label }}
                                    </dt>
                                    <dd class="font-medium text-slate-900 shrink-0 whitespace-nowrap">
                                        <template v-if="isEndorsementMode && item.key === 'rc'">
                                            <input
                                                v-model.number="form.rc_amount_override"
                                                type="number"
                                                min="0"
                                                step="1"
                                                class="w-28 rounded border border-slate-200 px-2 py-1 text-right text-xs"
                                            />
                                        </template>
                                        <template v-else-if="isEndorsementMode && item.key === 'dr'">
                                            <input
                                                v-model.number="form.defence_appeal_amount_override"
                                                type="number"
                                                min="0"
                                                step="1"
                                                class="w-28 rounded border border-slate-200 px-2 py-1 text-right text-xs"
                                            />
                                        </template>
                                        <template v-else-if="isEndorsementMode && item.key === 'tp'">
                                            <input
                                                v-model.number="form.person_transport_amount_override"
                                                type="number"
                                                min="0"
                                                step="1"
                                                class="w-28 rounded border border-slate-200 px-2 py-1 text-right text-xs"
                                            />
                                        </template>
                                        <template v-else>
                                            {{ item.amountReduced.toLocaleString("fr-FR") }} FCFA
                                        </template>
                                    </dd>
                                </div>
                                <div
                                    class="flex justify-between gap-2 pt-1.5 border-t border-slate-200"
                                >
                                    <dt class="text-slate-700 font-medium">
                                        Prime nette
                                    </dt>
                                    <dd
                                        class="font-medium text-slate-900 shrink-0 whitespace-nowrap"
                                    >
                                        {{
                                            primeNetteCreate.toLocaleString(
                                                "fr-FR",
                                            )
                                        }}
                                        FCFA
                                    </dd>
                                </div>
                            </dl>
                        </div>
                        <!-- Résumé financier (comme sur le PDF) -->
                        <h4
                            class="text-xs font-semibold text-slate-600 uppercase tracking-wide pt-3"
                        >
                            Résumé financier
                        </h4>
                        <dl class="space-y-2 text-sm">
                            <div
                                v-if="primeNetteCreate > 0"
                                class="flex justify-between gap-2"
                            >
                                <dt class="text-slate-600">Prime nette</dt>
                                <dd
                                    class="font-medium text-slate-900 shrink-0 whitespace-nowrap"
                                >
                                    {{
                                        primeNetteCreate.toLocaleString("fr-FR")
                                    }}
                                    FCFA
                                </dd>
                            </div>
                            <div
                                v-if="accessoryForTax > 0"
                                class="flex justify-between gap-2"
                            >
                                <dt class="text-slate-600">Accessoire</dt>
                                <dd class="font-medium text-slate-900 shrink-0 whitespace-nowrap">
                                    <template v-if="isEndorsementMode">
                                        <input
                                            v-model.number="form.accessory_amount_override"
                                            type="number"
                                            min="0"
                                            step="1"
                                            class="w-28 rounded border border-slate-200 px-2 py-1 text-right text-xs"
                                        />
                                    </template>
                                    <template v-else>
                                        {{ Number(accessoryForTax).toLocaleString("fr-FR") }} FCFA
                                    </template>
                                </dd>
                            </div>
                            <div
                                v-if="taxesAmountCreate > 0"
                                class="flex justify-between gap-2"
                            >
                                <dt class="text-slate-600">Taxes</dt>
                                <dd class="font-medium text-slate-900 shrink-0 whitespace-nowrap">
                                    <template v-if="isEndorsementMode">
                                        <input
                                            v-model.number="form.taxes_amount_override"
                                            type="number"
                                            min="0"
                                            step="1"
                                            class="w-28 rounded border border-slate-200 px-2 py-1 text-right text-xs"
                                        />
                                    </template>
                                    <template v-else>
                                        {{ taxesAmountCreate.toLocaleString("fr-FR") }} FCFA
                                    </template>
                                </dd>
                            </div>
                            <div
                                v-if="fgaAmountCreate > 0"
                                class="flex justify-between gap-2"
                            >
                                <dt class="text-slate-600">Taxe FGA</dt>
                                <dd class="font-medium text-slate-900 shrink-0 whitespace-nowrap">
                                    <template v-if="isEndorsementMode">
                                        <input
                                            v-model.number="form.fga_amount_override"
                                            type="number"
                                            min="0"
                                            step="1"
                                            class="w-28 rounded border border-slate-200 px-2 py-1 text-right text-xs"
                                        />
                                    </template>
                                    <template v-else>
                                        {{ fgaAmountCreate.toLocaleString("fr-FR") }} FCFA
                                    </template>
                                </dd>
                            </div>
                            <div
                                v-if="cedeaoAmountCreate > 0"
                                class="flex justify-between gap-2"
                            >
                                <dt class="text-slate-600">CEDEAO</dt>
                                <dd class="font-medium text-slate-900 shrink-0 whitespace-nowrap">
                                    <template v-if="isEndorsementMode">
                                        <input
                                            v-model.number="form.cedeao_amount_override"
                                            type="number"
                                            min="0"
                                            step="1"
                                            class="w-28 rounded border border-slate-200 px-2 py-1 text-right text-xs"
                                        />
                                    </template>
                                    <template v-else>
                                        {{ Number(cedeaoAmountCreate).toLocaleString("fr-FR") }} FCFA
                                    </template>
                                </dd>
                            </div>
                            <div
                                class="flex justify-between gap-2 pt-2 border-t-2 border-slate-200"
                            >
                                <dt
                                    class="font-semibold text-slate-800 min-w-0"
                                >
                                    Prime TTC
                                </dt>
                                <dd
                                    class="font-semibold text-slate-900 shrink-0 whitespace-nowrap"
                                >
                                    {{
                                        displayTotal.toLocaleString("fr-FR")
                                    }}
                                    FCFA
                                </dd>
                            </div>
                            <div
                                v-if="(form.agency_accessory ?? 0) > 0"
                                class="flex justify-between gap-2"
                            >
                                <dt class="text-slate-600">
                                    Accessoire agence
                                </dt>
                                <dd
                                    class="font-medium text-slate-900 shrink-0 whitespace-nowrap"
                                >
                                    {{
                                        Number(
                                            form.agency_accessory,
                                        ).toLocaleString("fr-FR")
                                    }}
                                    FCFA
                                </dd>
                            </div>
                            <div
                                v-if="(form.company_accessory ?? 0) > 0"
                                class="flex justify-between gap-2"
                            >
                                <dt class="text-slate-600">
                                    Accessoire compagnie
                                </dt>
                                <dd
                                    class="font-medium text-slate-900 shrink-0 whitespace-nowrap"
                                >
                                    {{
                                        Number(
                                            form.company_accessory,
                                        ).toLocaleString("fr-FR")
                                    }}
                                    FCFA
                                </dd>
                            </div>
                            <div
                                v-if="
                                    (form.agency_accessory ?? 0) > 0 ||
                                    (form.company_accessory ?? 0) > 0
                                "
                                class="flex justify-between gap-2 pt-2 border-t border-slate-200"
                            >
                                <dt
                                    class="font-semibold text-slate-800 min-w-0"
                                >
                                    Montant à payer
                                </dt>
                                <dd
                                    class="font-semibold text-slate-900 shrink-0 whitespace-nowrap"
                                >
                                    {{
                                        (
                                            displayTotal +
                                            Number(form.agency_accessory ?? 0) +
                                            Number(form.company_accessory ?? 0)
                                        ).toLocaleString("fr-FR")
                                    }}
                                    FCFA
                                </dd>
                            </div>
                        </dl>
                    </template>
                    <template v-else>
                        <p
                            v-if="
                                isEndorsementMode && !canPreview
                            "
                            class="text-sm text-slate-500"
                        >
                            Indiquez la date d’effet et le type d’avenant pour
                            afficher le montant.
                        </p>
                        <p v-else class="text-sm text-slate-500">
                            Sélectionnez un client, un véhicule, la période et
                            les dates pour voir le montant.
                        </p>
                    </template>
                </div>
            </aside>
        </div>

        <!-- Panneau latéral : Nouveau client -->
        <Teleport to="body">
            <div
                v-if="showClientDrawer"
                class="fixed inset-0 z-50 flex"
                aria-modal="true"
            >
                <div
                    class="fixed inset-0 bg-slate-900/50 transition-opacity"
                    aria-hidden="true"
                    @click="closeClientDrawer"
                />
                <div
                    class="fixed right-0 top-0 bottom-0 w-full max-w-md bg-white border-l border-slate-200 flex flex-col shadow-lg overflow-hidden"
                >
                    <div
                        class="flex items-center justify-between px-5 py-4 border-b border-slate-200"
                    >
                        <h3 class="text-base font-semibold text-slate-900">
                            Nouveau client
                        </h3>
                        <button
                            type="button"
                            class="p-2 text-slate-500 hover:text-slate-700 rounded-lg"
                            @click="closeClientDrawer"
                        >
                            <span class="sr-only">Fermer</span>
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
                    <form
                        class="flex-1 overflow-auto p-5 space-y-4"
                        @submit.prevent="submitClientQuick"
                    >
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Nom complet *</label
                            >
                            <input
                                v-model="clientQuickForm.full_name"
                                type="text"
                                required
                                :class="[
                                    inputClass,
                                    clientQuickErrors.full_name &&
                                        inputErrorClass,
                                ]"
                                placeholder="Nom du client"
                            />
                            <p
                                v-if="clientQuickErrors.full_name"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ clientQuickErrors.full_name[0] }}
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700 mb-1"
                                    >Email</label
                                >
                                <input
                                    v-model="clientQuickForm.email"
                                    type="email"
                                    :class="[
                                        inputClass,
                                        clientQuickErrors.email &&
                                            inputErrorClass,
                                    ]"
                                />
                                <p
                                    v-if="clientQuickErrors.email"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ clientQuickErrors.email[0] }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700 mb-1"
                                    >Téléphone</label
                                >
                                <input
                                    v-model="clientQuickForm.phone"
                                    type="text"
                                    :class="[
                                        inputClass,
                                        clientQuickErrors.phone &&
                                            inputErrorClass,
                                    ]"
                                />
                                <p
                                    v-if="clientQuickErrors.phone"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ clientQuickErrors.phone[0] }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Adresse</label
                            >
                            <input
                                v-model="clientQuickForm.address"
                                type="text"
                                :class="[
                                    inputClass,
                                    clientQuickErrors.address &&
                                        inputErrorClass,
                                ]"
                            />
                            <p
                                v-if="clientQuickErrors.address"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ clientQuickErrors.address[0] }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Adresse postale</label
                            >
                            <input
                                v-model="clientQuickForm.postal_address"
                                type="text"
                                :class="[
                                    inputClass,
                                    clientQuickErrors.postal_address &&
                                        inputErrorClass,
                                ]"
                            />
                            <p
                                v-if="clientQuickErrors.postal_address"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ clientQuickErrors.postal_address[0] }}
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700 mb-1"
                                    >Profession *</label
                                >
                                <input
                                    v-model="clientQuickForm.profession"
                                    type="text"
                                    required
                                    :class="[
                                        inputClass,
                                        clientQuickErrors.profession &&
                                            inputErrorClass,
                                    ]"
                                    placeholder="Profession"
                                />
                                <p
                                    v-if="clientQuickErrors.profession"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ clientQuickErrors.profession[0] }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700 mb-1"
                                    >Type assuré</label
                                >
                                <select
                                    v-model="clientQuickForm.type_assure"
                                    :class="inputClass"
                                >
                                    <option value="">—</option>
                                    <option
                                        v-for="opt in typeAssureOptions || []"
                                        :key="opt.value"
                                        :value="opt.value"
                                    >
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="flex gap-3 pt-4 border-t border-slate-200">
                            <button
                                type="submit"
                                class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                                :disabled="clientQuickSubmitting"
                            >
                                {{
                                    clientQuickSubmitting
                                        ? "Création…"
                                        : "Créer"
                                }}
                            </button>
                            <button
                                type="button"
                                class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50"
                                @click="closeClientDrawer"
                            >
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Panneau latéral : Nouveau véhicule -->
        <Teleport to="body">
            <div
                v-if="showVehicleDrawer"
                class="fixed inset-0 z-50 flex"
                aria-modal="true"
            >
                <div
                    class="fixed inset-0 bg-slate-900/50 transition-opacity"
                    aria-hidden="true"
                    @click="closeVehicleDrawer"
                />
                <div
                    class="fixed right-0 top-0 bottom-0 w-full max-w-2xl bg-white border-l border-slate-200 flex flex-col shadow-lg overflow-hidden"
                >
                    <div
                        class="flex items-center justify-between px-5 py-4 border-b border-slate-200"
                    >
                        <h3 class="text-base font-semibold text-slate-900">
                            Nouveau véhicule
                        </h3>
                        <button
                            type="button"
                            class="p-2 text-slate-500 hover:text-slate-700 rounded-lg"
                            @click="closeVehicleDrawer"
                        >
                            <span class="sr-only">Fermer</span>
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
                    <form
                        class="flex-1 overflow-auto p-5 space-y-4"
                        @submit.prevent="submitVehicleQuick"
                    >
                        <p class="text-sm text-slate-600">
                            Client :
                            <strong>{{ selectedClient?.full_name }}</strong>
                        </p>

                        <fieldset class="space-y-3">
                            <legend
                                class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-1 w-full"
                            >
                                Informations générales
                            </legend>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Type *</label
                                    >
                                    <select
                                        v-model="vehicleQuickForm.pricing_type"
                                        :class="inputClass"
                                        required
                                        @change="
                                            vehicleQuickForm.vehicle_model_id =
                                                ''
                                        "
                                    >
                                        <option value="VP">VP</option>
                                        <option value="TPC">TPC</option>
                                        <option value="TPM">TPM</option>
                                        <option value="TWO_WHEELER">
                                            Deux roues
                                        </option>
                                    </select>
                                    <p
                                        v-if="vehicleQuickErrors.pricing_type"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ vehicleQuickErrors.pricing_type[0] }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Immatriculation *</label
                                    >
                                    <input
                                        v-model="
                                            vehicleQuickForm.registration_number
                                        "
                                        type="text"
                                        required
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.registration_number &&
                                                inputErrorClass,
                                        ]"
                                        placeholder="Numéro d'immatriculation"
                                    />
                                    <p
                                        v-if="
                                            vehicleQuickErrors.registration_number
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .registration_number[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Marque *</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.vehicle_brand_id
                                        "
                                        :class="inputClass"
                                        required
                                        @change="
                                            vehicleQuickForm.vehicle_model_id =
                                                ''
                                        "
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="b in vehicleBrands || []"
                                            :key="b.id"
                                            :value="b.id"
                                        >
                                            {{ b.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.vehicle_brand_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .vehicle_brand_id[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Modèle *</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.vehicle_model_id
                                        "
                                        :class="inputClass"
                                        required
                                        :disabled="
                                            !vehicleQuickForm.vehicle_brand_id
                                        "
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="m in vehicleQuickModels"
                                            :key="m.id"
                                            :value="m.id"
                                        >
                                            {{ m.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.vehicle_model_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .vehicle_model_id[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Carrosserie *</label
                                    >
                                    <input
                                        v-model="vehicleQuickForm.body_type"
                                        type="text"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.body_type &&
                                                inputErrorClass,
                                        ]"
                                        placeholder="Ex. Berline, SUV"
                                    />
                                    <p
                                        v-if="vehicleQuickErrors.body_type"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ vehicleQuickErrors.body_type[0] }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Couleur *</label
                                    >
                                    <select
                                        v-model="vehicleQuickForm.color_id"
                                        :class="inputClass"
                                        required
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="c in colors || []"
                                            :key="c.id"
                                            :value="c.id"
                                        >
                                            {{ c.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="vehicleQuickErrors.color_id"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ vehicleQuickErrors.color_id[0] }}
                                    </p>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="space-y-3">
                            <legend
                                class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-1 w-full"
                            >
                                Spécifications techniques
                            </legend>
                            <div class="grid grid-cols-2 gap-4">
                                <div
                                    v-if="
                                        vehicleQuickForm.pricing_type ===
                                            'TPC' ||
                                        vehicleQuickForm.pricing_type === 'TPM'
                                    "
                                >
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Charge utile (tonne) *</label
                                    >
                                    <input
                                        v-model.number="
                                            vehicleQuickForm.payload_capacity
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.payload_capacity &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="
                                            vehicleQuickErrors.payload_capacity
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .payload_capacity[0]
                                        }}
                                    </p>
                                </div>
                                <div
                                    v-if="
                                        vehicleQuickForm.pricing_type === 'VP'
                                    "
                                >
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Énergie *</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.energy_source_id
                                        "
                                        :class="inputClass"
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="e in energySources || []"
                                            :key="e.id"
                                            :value="e.id"
                                        >
                                            {{ e.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.energy_source_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .energy_source_id[0]
                                        }}
                                    </p>
                                </div>
                                <div
                                    v-if="
                                        vehicleQuickForm.pricing_type ===
                                        'TWO_WHEELER'
                                    "
                                >
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Cylindrée (cm³) *</label
                                    >
                                    <input
                                        v-model.number="
                                            vehicleQuickForm.engine_capacity
                                        "
                                        type="number"
                                        min="0"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.engine_capacity &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="
                                            vehicleQuickErrors.engine_capacity
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .engine_capacity[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Nombre de places *</label
                                    >
                                    <input
                                        v-model.number="
                                            vehicleQuickForm.seat_count
                                        "
                                        type="number"
                                        min="0"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.seat_count &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="vehicleQuickErrors.seat_count"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ vehicleQuickErrors.seat_count[0] }}
                                    </p>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="space-y-3">
                            <legend
                                class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-1 w-full"
                            >
                                Classification
                            </legend>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Usage *</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.vehicle_usage_id
                                        "
                                        :class="inputClass"
                                        required
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="u in vehicleUsages || []"
                                            :key="u.id"
                                            :value="u.id"
                                        >
                                            {{ u.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.vehicle_usage_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .vehicle_usage_id[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Catégorie *</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.vehicle_category_id
                                        "
                                        :class="inputClass"
                                        required
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="cat in vehicleCategories ||
                                            []"
                                            :key="cat.id"
                                            :value="cat.id"
                                        >
                                            {{ cat.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.vehicle_category_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .vehicle_category_id[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Genre *</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.vehicle_gender_id
                                        "
                                        :class="inputClass"
                                        required
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="g in vehicleGenders || []"
                                            :key="g.id"
                                            :value="g.id"
                                        >
                                            {{ g.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.vehicle_gender_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .vehicle_gender_id[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Type</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.vehicle_type_id
                                        "
                                        :class="inputClass"
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="t in vehicleTypes || []"
                                            :key="t.id"
                                            :value="t.id"
                                        >
                                            {{ t.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.vehicle_type_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .vehicle_type_id[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Zone de circulation</label
                                    >
                                    <select
                                        v-model="
                                            vehicleQuickForm.circulation_zone_id
                                        "
                                        :class="inputClass"
                                    >
                                        <option value="">— Choisir —</option>
                                        <option
                                            v-for="z in circulationZones || []"
                                            :key="z.id"
                                            :value="z.id"
                                        >
                                            {{ z.name }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            vehicleQuickErrors.circulation_zone_id
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .circulation_zone_id[0]
                                        }}
                                    </p>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="space-y-3">
                            <legend
                                class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-1 w-full"
                            >
                                Informations techniques
                            </legend>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Date 1ère mise en circulation *</label
                                    >
                                    <input
                                        v-model="
                                            vehicleQuickForm.first_registration_date
                                        "
                                        type="date"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.first_registration_date &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="
                                            vehicleQuickErrors.first_registration_date
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .first_registration_date[0]
                                        }}
                                    </p>
                                </div>
                                <div
                                    v-if="
                                        vehicleQuickForm.pricing_type === 'VP'
                                    "
                                >
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Puissance fiscale (CV) *</label
                                    >
                                    <input
                                        v-model.number="
                                            vehicleQuickForm.fiscal_power
                                        "
                                        type="number"
                                        min="0"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.fiscal_power &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="vehicleQuickErrors.fiscal_power"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ vehicleQuickErrors.fiscal_power[0] }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Numéro de carte grise</label
                                    >
                                    <input
                                        v-model="
                                            vehicleQuickForm.registration_card_number
                                        "
                                        type="text"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.registration_card_number &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="
                                            vehicleQuickErrors.registration_card_number
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .registration_card_number[0]
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Numéro de châssis</label
                                    >
                                    <input
                                        v-model="
                                            vehicleQuickForm.chassis_number
                                        "
                                        type="text"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.chassis_number &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="vehicleQuickErrors.chassis_number"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors.chassis_number[0]
                                        }}
                                    </p>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="space-y-3">
                            <legend
                                class="text-sm font-semibold text-slate-800 border-b border-slate-200 pb-1 w-full"
                            >
                                Valeurs financières
                            </legend>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Valeur neuve</label
                                    >
                                    <input
                                        v-model.number="
                                            vehicleQuickForm.new_value
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.new_value &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="vehicleQuickErrors.new_value"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ vehicleQuickErrors.new_value[0] }}
                                    </p>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-700 mb-1"
                                        >Valeur de remplacement</label
                                    >
                                    <input
                                        v-model.number="
                                            vehicleQuickForm.replacement_value
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :class="[
                                            inputClass,
                                            vehicleQuickErrors.replacement_value &&
                                                inputErrorClass,
                                        ]"
                                    />
                                    <p
                                        v-if="
                                            vehicleQuickErrors.replacement_value
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            vehicleQuickErrors
                                                .replacement_value[0]
                                        }}
                                    </p>
                                </div>
                            </div>
                        </fieldset>

                        <div class="flex gap-3 pt-4 border-t border-slate-200">
                            <button
                                type="submit"
                                class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                                :disabled="vehicleQuickSubmitting"
                            >
                                {{
                                    vehicleQuickSubmitting
                                        ? "Création…"
                                        : "Créer"
                                }}
                            </button>
                            <button
                                type="button"
                                class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50"
                                @click="closeVehicleDrawer"
                            >
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <div class="mt-4">
            <Link
                :href="route('contracts.index')"
                class="text-sm text-slate-600 hover:text-slate-900"
                >← Retour à la liste</Link
            >
        </div>
    </DashboardLayout>
</template>
