<script setup>
import { Link } from "@inertiajs/vue3";
import FlashNotifications from "@/Components/FlashNotifications.vue";
</script>

<template>
    <div class="min-h-screen flex flex-col lg:flex-row bg-white">
        <FlashNotifications />

        <!-- Gauche : message simple -->
        <div class="hidden lg:flex lg:w-1/2 flex-col justify-between min-h-screen bg-slate-50 border-r border-slate-200/80 p-12 xl:p-16">
            <Link href="/" class="text-lg font-semibold text-slate-900">
                Baaro
            </Link>
            <div class="max-w-md">
                <h1 class="text-2xl xl:text-3xl font-semibold text-slate-900 tracking-tight">
                    Archivage intelligent pour vos documents
                </h1>
                <p class="mt-4 text-slate-600 leading-relaxed">
                    Centralisez vos archives et interrogez-les en langage naturel. Un assistant IA parcourt vos contenus et vous répond.
                </p>
                <ul class="mt-8 space-y-4 list-none p-0 m-0">
                    <li class="flex items-center gap-3 text-slate-600">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Documents centralisés par organisation
                    </li>
                    <li class="flex items-center gap-3 text-slate-600">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Questions en langage naturel
                    </li>
                    <li class="flex items-center gap-3 text-slate-600">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Réponses avec sources
                    </li>
                </ul>
            </div>
            <p class="text-sm text-slate-400">
                © Baaro
            </p>
        </div>

        <!-- Droite : formulaire -->
        <div class="flex-1 flex items-center justify-center p-6 sm:p-10 lg:p-12 min-w-0 min-h-screen lg:min-h-0">
            <div class="w-full max-w-sm">
                <div class="lg:hidden mb-8 text-center">
                    <Link href="/" class="text-lg font-semibold text-slate-900">
                        Baaro
                    </Link>
                    <p class="mt-2 text-sm text-slate-500">
                        Archivage intelligent — Documents et assistant IA
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
