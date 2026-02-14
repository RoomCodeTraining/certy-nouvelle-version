<script setup>
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    current_password: '',
    password: '',
    password_confirmation: '',
});

const inputClass = 'w-full rounded-lg border border-slate-200 px-2.5 py-2 text-slate-900 placeholder-slate-400 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none';
const inputErrorClass = 'border-red-400 focus:border-red-400 focus:ring-red-400';
</script>

<template>
    <DashboardLayout>
        <template #header>
            <h1 class="text-sm font-medium text-slate-900">Profil</h1>
        </template>

        <div class="max-w-2xl">
            <p class="text-slate-600 text-sm mb-6">Informations de votre compte</p>

            <form @submit.prevent="form.put('/settings/profile')" class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nom</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        :class="[inputClass, form.errors.name && inputErrorClass]"
                        placeholder="Votre nom"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        :class="[inputClass, form.errors.email && inputErrorClass]"
                        placeholder="vous@entreprise.com"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <p class="text-sm font-medium text-slate-700 mb-2">Changer le mot de passe</p>
                    <p class="text-xs text-slate-500 mb-3">Laissez vide pour ne pas modifier</p>
                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1">Mot de passe actuel</label>
                            <input
                                id="current_password"
                                v-model="form.current_password"
                                type="password"
                                autocomplete="current-password"
                                :class="[inputClass, form.errors.current_password && inputErrorClass]"
                                placeholder="••••••••"
                            />
                            <p v-if="form.errors.current_password" class="mt-1 text-sm text-red-600">{{ form.errors.current_password }}</p>
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Nouveau mot de passe</label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                autocomplete="new-password"
                                :class="[inputClass, form.errors.password && inputErrorClass]"
                                placeholder="••••••••"
                            />
                            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirmer</label>
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                :class="inputClass"
                                placeholder="••••••••"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-3 py-2 rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800 disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                    <Link
                        href="/dashboard"
                        class="px-3 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition-colors"
                    >
                        Annuler
                    </Link>
                </div>
            </form>
        </div>
    </DashboardLayout>
</template>
