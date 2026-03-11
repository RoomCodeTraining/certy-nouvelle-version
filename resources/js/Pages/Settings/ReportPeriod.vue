<script setup>
import { useForm, Link, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";

const props = defineProps({
    setting: Object,
});

const breadcrumbs = [
    { label: "Tableau de bord", href: "/dashboard" },
    { label: "Paramètres", href: "/settings/profile" },
    { label: "Export production" },
];

const page = usePage();
const authUser = page.props.auth?.user ?? {};
const defaultExternalEmail =
    authUser.external_username || authUser.email || "";

const form = useForm({
    enabled: props.setting?.enabled ?? false,
    frequency: props.setting?.frequency ?? "quinzaine",
    day_of_week: props.setting?.day_of_week ?? 1,
    day_of_month: props.setting?.day_of_month ?? 16,
    time: props.setting?.time ?? "08:00",
    emails: props.setting?.emails ?? "",
    external_email: defaultExternalEmail,
    external_password: "",
});

const inputClass =
    "w-full rounded-lg border border-slate-200 px-2.5 py-2 text-slate-900 placeholder-slate-400 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none";
const inputErrorClass = "border-red-400 focus:border-red-400 focus:ring-red-400";

const frequencyOptions = [
    { value: "quinzaine", label: "Quinzaine (1er-15 et 16-fin du mois)" },
    { value: "daily", label: "Quotidien" },
    { value: "weekly", label: "Hebdomadaire" },
    { value: "monthly", label: "Mensuel" },
    { value: "test_5min", label: "TEST : toutes les 5 min (jour précédent)" },
];

const weekdays = [
    { value: 1, label: "Lundi" },
    { value: 2, label: "Mardi" },
    { value: 3, label: "Mercredi" },
    { value: 4, label: "Jeudi" },
    { value: 5, label: "Vendredi" },
    { value: 6, label: "Samedi" },
    { value: 7, label: "Dimanche" },
];
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Export production" />
        </template>

        <div class="min-h-[80vh] flex flex-col w-full">
            <p class="text-slate-600 text-sm mb-6">
                Périodes et destinataires pour recevoir l'export des attestations
                externes (Reporting) par email.
            </p>
            <p class="text-amber-700 text-sm mb-6 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3">
                <strong>Important :</strong> L'export automatique utilise la session externe (ASACI) du compte root connecté.
                Si le token expire, les exports échoueront et un email de notification sera envoyé. Connectez-vous régulièrement
                pour maintenir la session à jour.
            </p>
            <div class="flex-1 w-full">
                <form
                    @submit.prevent="form.put('/settings/report-period')"
                    class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 space-y-6 w-full"
                >
                    <div class="flex items-center gap-3">
                        <input
                            id="enabled"
                            v-model="form.enabled"
                            type="checkbox"
                            class="rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                        />
                        <label for="enabled" class="text-sm font-medium text-slate-700">
                            Activer l'envoi automatique
                        </label>
                    </div>

                    <div>
                        <label for="emails" class="block text-sm font-medium text-slate-700 mb-1">
                            Emails destinataires
                        </label>
                        <textarea
                            id="emails"
                            v-model="form.emails"
                            rows="3"
                            :class="[inputClass, form.errors.emails && inputErrorClass]"
                            placeholder="email1@exemple.com, email2@exemple.com, ..."
                        />
                        <p class="mt-1 text-xs text-slate-500">
                            Séparez les adresses par des virgules. Tous les destinataires recevront
                            l'export (1ère quinzaine : 1er-15 ; 2ème quinzaine : 16-fin du mois).
                        </p>
                        <p v-if="form.errors.emails" class="mt-1 text-sm text-red-600">
                            {{ form.errors.emails }}
                        </p>
                    </div>

                    <div class="border-t border-slate-100 pt-4 mt-2 space-y-3">
                        <p class="text-sm text-slate-700 font-medium">
                            Identifiants session externe (ASACI) pour l'export automatique
                        </p>
                        <p class="text-xs text-slate-500">
                            Ces identifiants seront utilisés uniquement pour générer un token dédié à
                            l'export automatique des attestations externes. Le mot de passe n'est jamais
                            stocké, seul le token généré est conservé.
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label
                                    for="external_email"
                                    class="block text-sm font-medium text-slate-700 mb-1"
                                >
                                    Email (compte ASACI)
                                </label>
                                <input
                                    id="external_email"
                                    v-model="form.external_email"
                                    type="email"
                                    :class="[
                                        inputClass,
                                        form.errors.external_email && inputErrorClass,
                                    ]"
                                    placeholder="adresse@exemple.com"
                                    autocomplete="off"
                                />
                                <p v-if="form.errors.external_email" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.external_email }}
                                </p>
                            </div>
                            <div>
                                <label
                                    for="external_password"
                                    class="block text-sm font-medium text-slate-700 mb-1"
                                >
                                    Mot de passe
                                </label>
                                <input
                                    id="external_password"
                                    v-model="form.external_password"
                                    type="password"
                                    :class="[
                                        inputClass,
                                        form.errors.external_password && inputErrorClass,
                                    ]"
                                    autocomplete="new-password"
                                />
                                <p
                                    v-if="form.errors.external_password"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ form.errors.external_password }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="frequency" class="block text-sm font-medium text-slate-700 mb-1">
                            Fréquence
                        </label>
                        <select
                            id="frequency"
                            v-model="form.frequency"
                            :class="[inputClass, form.errors.frequency && inputErrorClass]"
                        >
                            <option
                                v-for="opt in frequencyOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>

                    <template v-if="form.frequency === 'quinzaine'">
                        <p class="text-sm text-slate-600">
                            Envoi automatique le 1er du mois (période 16-fin du mois précédent) et le
                            16 (période 1er-15 du mois en cours).
                        </p>
                    </template>
                    <template v-if="form.frequency === 'test_5min'">
                        <p class="text-sm text-amber-700 rounded-lg bg-amber-50 border border-amber-200 px-4 py-2">
                            Mode test : envoi toutes les 5 minutes avec la production du jour précédent.
                            Utile pour vérifier que l'export et l'envoi par email fonctionnent.
                        </p>
                    </template>

                    <template v-if="form.frequency === 'weekly'">
                        <div>
                            <label for="day_of_week" class="block text-sm font-medium text-slate-700 mb-1">
                                Jour de la semaine
                            </label>
                            <select
                                id="day_of_week"
                                v-model.number="form.day_of_week"
                                :class="inputClass"
                            >
                                <option
                                    v-for="d in weekdays"
                                    :key="d.value"
                                    :value="d.value"
                                >
                                    {{ d.label }}
                                </option>
                            </select>
                        </div>
                    </template>

                    <template v-if="form.frequency === 'monthly'">
                        <div>
                            <label for="day_of_month" class="block text-sm font-medium text-slate-700 mb-1">
                                Jour du mois (1-31)
                            </label>
                            <input
                                id="day_of_month"
                                v-model.number="form.day_of_month"
                                type="number"
                                min="1"
                                max="31"
                                :class="[inputClass, form.errors.day_of_month && inputErrorClass]"
                            />
                        </div>
                    </template>

                    <div>
                        <label for="time" class="block text-sm font-medium text-slate-700 mb-1">
                            Heure d'envoi
                        </label>
                        <input
                            id="time"
                            v-model="form.time"
                            type="time"
                            :class="[inputClass, form.errors.time && inputErrorClass]"
                        />
                    </div>

                    <div class="flex items-center gap-3 pt-4">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 disabled:opacity-70"
                        >
                            Enregistrer
                        </button>
                        <Link
                            href="/settings/config"
                            class="text-sm text-slate-600 hover:text-slate-900"
                        >
                            Annuler
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
