<script setup>
import { computed, ref } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { route } from "@/route";

const showPassword = ref(false);
const copyFeedback = ref(false);

function copyPasswordToClipboard() {
    if (!form.password) return;
    navigator.clipboard.writeText(form.password).then(() => {
        copyFeedback.value = true;
        setTimeout(() => {
            copyFeedback.value = false;
        }, 2000);
    });
}

// Politique : min 12 caractères, alphanumérique (majuscule, minuscule, chiffre)
function generatePassword() {
    const length = 16;
    const lower = "abcdefghijklmnopqrstuvwxyz";
    const upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    const digits = "0123456789";
    const alphanumeric = lower + upper + digits;
    let pwd = "";
    pwd += lower[Math.floor(Math.random() * lower.length)];
    pwd += upper[Math.floor(Math.random() * upper.length)];
    pwd += digits[Math.floor(Math.random() * digits.length)];
    for (let i = pwd.length; i < length; i++) {
        pwd += alphanumeric[Math.floor(Math.random() * alphanumeric.length)];
    }
    form.password = pwd
        .split("")
        .sort(() => Math.random() - 0.5)
        .join("");
}

const props = defineProps({
    roles: { type: Array, default: () => [] },
    offices: { type: Array, default: () => [] },
    flash: { type: Object, default: () => ({}) },
});

const breadcrumbs = [
    { label: "Tableau de bord", href: "/dashboard" },
    { label: "Digital", href: "/digital/attestations" },
    { label: "Utilisateurs", href: "/digital/utilisateurs" },
    { label: "Créer un utilisateur" },
];

const form = useForm({
    first_name: "",
    last_name: "",
    email: "",
    telephone: "",
    password: "",
    role: "",
    office_id: "",
});

// Rôles office_user : standard_user = Producteur, office_admin = Administrateur Representation, etc.
const ROLE_LABEL_MAP = {
    standard_user: "Producteur",
    office_admin: "Administrateur Representation",
    administrateur: "Administrateur",
    representation: "Representation",
};

function getRoleLabel(role) {
    const raw = (role?.name ?? role?.label ?? role?.code ?? "")
        .toString()
        .toLowerCase()
        .replace(/\s+/g, "_");
    return (
        ROLE_LABEL_MAP[raw] ?? role?.name ?? role?.label ?? role?.code ?? "—"
    );
}

// L'API renvoie des rôles avec name/label (pas d'id) : on envoie le "name" (ex. office_admin, standard_user)
const roleOptions = computed(() => {
    const r = props.roles;
    if (!Array.isArray(r)) return [];
    return r.map((x) => ({
        value: String(x.name ?? x.id ?? x.value ?? x.code ?? ""),
        label: getRoleLabel(x),
    }));
});
const officeOptions = computed(() => {
    const o = props.offices;
    if (!Array.isArray(o)) return [];
    return o.map((x) => ({
        value: String(x.id ?? x.value ?? ""),
        label: (x.name ?? x.label ?? "") + (x.code ? ` (${x.code})` : ""),
    }));
});

const inputClass =
    "w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none";
const inputErrorClass =
    "border-red-400 focus:border-red-400 focus:ring-red-400";

// Erreurs de validation renvoyées par l'API (après redirect)
const apiErrors = computed(() => props.flash?.validation_errors ?? {});
function fieldError(field) {
    return form.errors[field] ?? apiErrors.value[field] ?? null;
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader
                :breadcrumbs="breadcrumbs"
                title="Créer un utilisateur"
            />
        </template>

        <div class="min-h-[60vh] flex flex-col">
            <p class="text-sm text-slate-600 mb-4">
                Créer un utilisateur sur la plateforme ASACI (service externe).
            </p>

            <div
                v-if="flash?.error"
                class="rounded-xl border-2 border-red-200 bg-red-50 p-4 mb-6"
                role="alert"
            >
                <div class="flex gap-3">
                    <span
                        class="shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-red-100 text-red-600"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </span>
                    <div class="text-sm text-red-800">
                        <p class="font-medium">Erreur de validation</p>
                        <p class="mt-1">{{ flash.error }}</p>
                    </div>
                </div>
            </div>

            <div class="flex-1 w-full max-w-none">
                <form
                    @submit.prevent="
                        form.post(route('digital.utilisateurs.store'))
                    "
                    class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 space-y-4 w-full"
                >
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Nom *</label
                            >
                            <input
                                v-model="form.first_name"
                                type="text"
                                required
                                :class="[
                                    inputClass,
                                    fieldError('first_name') && inputErrorClass,
                                ]"
                                placeholder="Prénom"
                            />
                            <p
                                v-if="fieldError('first_name')"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ fieldError("first_name") }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Prénoms *</label
                            >
                            <input
                                v-model="form.last_name"
                                type="text"
                                required
                                :class="[
                                    inputClass,
                                    fieldError('last_name') && inputErrorClass,
                                ]"
                                placeholder="Nom"
                            />
                            <p
                                v-if="fieldError('last_name')"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ fieldError("last_name") }}
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Email *</label
                            >
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                :class="[
                                    inputClass,
                                    fieldError('email') && inputErrorClass,
                                ]"
                            />
                            <p
                                v-if="fieldError('email')"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ fieldError("email") }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Téléphone</label
                            >
                            <input
                                v-model="form.telephone"
                                type="text"
                                :class="[
                                    inputClass,
                                    fieldError('telephone') && inputErrorClass,
                                ]"
                                placeholder="Téléphone"
                            />
                            <p
                                v-if="fieldError('telephone')"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ fieldError("telephone") }}
                            </p>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">
                        Le nom d'utilisateur (username) est généré
                        automatiquement (16 caractères).
                    </p>
                    <div>
                        <label
                            class="block text-sm font-medium text-slate-700 mb-1"
                            >Mot de passe</label
                        >
                        <p class="text-xs text-slate-500 mb-1">
                            Minimum 12 caractères (exigé par la plateforme ASACI).
                        </p>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    :class="[
                                        inputClass,
                                        'pr-10',
                                        fieldError('password') &&
                                            inputErrorClass,
                                    ]"
                                    placeholder="Min. 12 caractères (obligatoire)"
                                />
                                <button
                                    type="button"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-700"
                                    :aria-label="
                                        showPassword
                                            ? 'Masquer le mot de passe'
                                            : 'Afficher le mot de passe'
                                    "
                                    @click="showPassword = !showPassword"
                                >
                                    <svg
                                        v-if="!showPassword"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                        />
                                    </svg>
                                </button>
                            </div>
                            <button
                                type="button"
                                class="shrink-0 px-3 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 whitespace-nowrap"
                                @click="generatePassword"
                            >
                                Générer
                            </button>
                            <button
                                type="button"
                                class="shrink-0 px-3 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 whitespace-nowrap"
                                :class="{
                                    'bg-emerald-50 border-emerald-200 text-emerald-700':
                                        copyFeedback,
                                }"
                                :disabled="!form.password"
                                @click="copyPasswordToClipboard"
                            >
                                {{ copyFeedback ? "Copié !" : "Copier" }}
                            </button>
                        </div>
                        <p
                            v-if="fieldError('password')"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ fieldError("password") }}
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Rôle</label
                            >
                            <select
                                v-model="form.role"
                                :class="[
                                    inputClass,
                                    fieldError('role') && inputErrorClass,
                                ]"
                            >
                                <option value="">—</option>
                                <option
                                    v-for="opt in roleOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </option>
                            </select>
                            <p
                                v-if="fieldError('role')"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ fieldError("role") }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 mb-1"
                                >Bureau</label
                            >
                            <select
                                v-model="form.office_id"
                                :class="[
                                    inputClass,
                                    fieldError('office_id') && inputErrorClass,
                                ]"
                            >
                                <option value="">—</option>
                                <option
                                    v-for="opt in officeOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </option>
                            </select>
                            <p
                                v-if="fieldError('office_id')"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ fieldError("office_id") }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex flex-wrap gap-3 pt-6 border-t border-slate-200"
                    >
                        <button
                            type="submit"
                            class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? "Création…" : "Créer" }}
                        </button>
                        <Link
                            :href="route('digital.utilisateurs')"
                            class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50"
                        >
                            Annuler
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
