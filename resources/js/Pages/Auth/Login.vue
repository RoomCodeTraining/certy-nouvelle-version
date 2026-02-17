<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import AuthLayout from "@/Layouts/AuthLayout.vue";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const showPassword = ref(false);

const inputClass =
    "w-full rounded-lg border border-slate-200 px-2.5 py-2.5 text-slate-900 placeholder-slate-400 text-sm focus:border-brand-primary focus:ring-1 focus:ring-brand-primary focus:outline-none";
const inputErrorClass =
    "border-red-400 focus:border-red-400 focus:ring-red-400";
</script>

<template>
    <AuthLayout>
        <h1 class="text-xl font-semibold text-slate-900 mb-1">Connexion</h1>
        <p class="text-sm text-slate-500 mb-6">Accédez à votre espace de gestion.</p>

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
                    placeholder="vous@cabinet.com"
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
                <div class="relative">
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        :class="[
                            inputClass,
                            'pr-10',
                            form.errors.password && inputErrorClass,
                        ]"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 rounded"
                        :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                        @click="showPassword = !showPassword"
                    >
                        <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878a4.501 4.501 0 117.757 2.829M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                </div>
                <p
                    v-if="form.errors.password"
                    class="mt-1 text-sm text-red-600"
                >
                    {{ form.errors.password }}
                </p>
            </div>

            <button
                type="submit"
                class="w-full py-3 px-4 rounded-lg bg-brand-primary text-white text-sm font-medium hover:brightness-95 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="form.processing"
            >
                {{ form.processing ? "Connexion…" : "Se connecter" }}
            </button>
        </form>
    </AuthLayout>
</template>
