<script setup>
import { Link } from '@inertiajs/vue3';
import FlashNotifications from '@/Components/FlashNotifications.vue';
</script>

<template>
    <div class="min-h-screen flex">
        <FlashNotifications />
        <!-- Gauche : 3/4 -->
        <div class="hidden lg:flex lg:w-3/4 flex-col justify-between bg-slate-50 border-r border-slate-100 p-12 xl:p-16">
            <Link href="/" class="text-base font-semibold text-slate-900 w-fit">Baaro</Link>

            <div class="max-w-xl">
                <h1 class="text-3xl xl:text-4xl font-semibold text-slate-900 leading-tight tracking-tight">
                    Finalisez votre espace
                </h1>
                <p class="mt-4 text-lg text-slate-600 leading-relaxed">
                    Quelques informations pour configurer votre organisation. Vous pourrez les modifier à tout moment.
                </p>
                <div class="mt-8 flex flex-wrap gap-6 text-sm text-slate-600">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Création en 1 minute
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Données modifiables
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Par organisation
                    </span>
                </div>
            </div>

            <div class="mt-12 flex items-end gap-4">
                <div class="flex flex-col gap-3">
                    <div class="w-24 h-28 rounded-lg bg-white border border-slate-200 flex items-center justify-center">
                        <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Droite : 1/4 - Formulaire -->
        <div class="flex-1 lg:w-1/4 flex items-center justify-center p-6 sm:p-10 bg-white min-w-0 overflow-y-auto">
            <div class="w-full max-w-md">
                <div class="lg:hidden mb-8">
                    <Link href="/" class="text-base font-semibold text-slate-900">Baaro</Link>
                    <p class="mt-2 text-sm text-slate-600">Finalisez la configuration de votre espace</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
