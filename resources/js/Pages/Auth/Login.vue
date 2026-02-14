<script setup>
import { useForm } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import AuthLayout from "@/Layouts/AuthLayout.vue";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const inputClass =
    "w-full rounded-lg border border-slate-200 px-2.5 py-2 text-slate-900 placeholder-slate-400 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none";
const inputErrorClass =
    "border-red-400 focus:border-red-400 focus:ring-red-400";
</script>

<template>
    <AuthLayout>
        <h1 class="text-xl font-semibold text-slate-900 mb-1">Connexion</h1>
        <p class="text-sm text-slate-500 mb-6">Entrez vos identifiants</p>

        <form @submit.prevent="form.post('/login')" class="space-y-4">
            <div>
                <label
                    for="email"
                    class="block text-sm font-medium text-slate-700 mb-1"
                    >Email</label
                >
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    autocomplete="username"
                    :class="[inputClass, form.errors.email && inputErrorClass]"
                    placeholder="vous@entreprise.com"
                />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                    {{ form.errors.email }}
                </p>
            </div>

            <div>
                <label
                    for="password"
                    class="block text-sm font-medium text-slate-700 mb-1"
                    >Mot de passe</label
                >
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    autocomplete="current-password"
                    :class="[
                        inputClass,
                        form.errors.password && inputErrorClass,
                    ]"
                    placeholder="••••••••"
                />
                <p
                    v-if="form.errors.password"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ form.errors.password }}
                </p>
            </div>

            <div class="flex items-center">
                <input
                    id="remember"
                    v-model="form.remember"
                    type="checkbox"
                    class="rounded border-slate-300 text-slate-900 focus:ring-slate-400"
                />
                <label for="remember" class="ml-2 text-sm text-slate-600"
                    >Se souvenir de moi</label
                >
            </div>

            <button
                type="submit"
                class="w-full py-2.5 px-4 rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="form.processing"
            >
                {{ form.processing ? "Connexion…" : "Se connecter" }}
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500">
            Pas de compte ?
            <Link
                href="/register"
                class="text-slate-900 font-medium hover:underline"
                >S'inscrire</Link
            >
        </p>
    </AuthLayout>
</template>
