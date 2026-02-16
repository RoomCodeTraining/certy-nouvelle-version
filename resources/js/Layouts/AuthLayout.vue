<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import FlashNotifications from "@/Components/FlashNotifications.vue";

const page = usePage();
const appName = page.props.app?.name || "Certy";
const appLogo = page.props.app?.logo;

const highlights = [
    { text: "Gestion des contrats, véhicules et clients" },
    { text: "Connectée à la plateforme digitale de l'ASACI" },
    { text: "100 % dédiée aux courtiers" },
];
</script>

<template>
    <div class="min-h-screen flex flex-col lg:flex-row bg-white">
        <FlashNotifications />

        <!-- Gauche : fond + overlay + contenu -->
        <aside
            class="auth-panel hidden lg:block lg:w-2/3 relative min-h-screen overflow-hidden"
            role="presentation"
        >
            <!-- Fond : image login.jpg + overlay sombre pour lisibilité -->
            <div
                class="absolute inset-0 bg-cover bg-center bg-no-repeat"
                style="background-image: linear-gradient(135deg, rgb(15 23 42 / 0.88) 0%, rgb(15 23 42 / 0.82) 50%, rgb(15 23 42 / 0.88) 100%), url('/login.jpg');"
                aria-hidden="true"
            />
            <!-- Contenu -->
            <div class="relative z-10 flex flex-col min-h-screen p-12 xl:p-16 2xl:p-20">
                <header class="shrink-0">
                    <Link
                        href="/login"
                        class="inline-block text-white hover:opacity-90 transition-opacity"
                    >
                        <img v-if="appLogo" :src="appLogo" :alt="appName" class="h-8 w-auto max-w-[140px] object-contain object-left" />
                        <span v-else class="text-lg font-semibold">{{ appName }}</span>
                    </Link>
                </header>

                <div class="flex flex-col flex-1 min-h-0 pt-10 xl:pt-14">
                    <div class="flex-1 min-h-0 overflow-y-auto">
                        <div class="max-w-md pb-8">
                            <h1 class="text-2xl xl:text-3xl 2xl:text-4xl font-semibold text-white tracking-tight leading-tight">
                                Contrats, véhicules, clients : tout votre courtage en un seul endroit.
                            </h1>
                            <p class="mt-5 xl:mt-6 text-slate-300 text-base xl:text-lg leading-relaxed">
                                Une plateforme purement dédiée aux courtiers, connectée à la plateforme digitale de l'ASACI. Gérez vos dossiers, suivez les échéances et pilotez votre activité au quotidien.
                            </p>
                            <ul class="mt-8 xl:mt-10 space-y-4 list-none p-0 m-0" role="list">
                                <li
                                    v-for="(item, index) in highlights"
                                    :key="index"
                                    class="flex items-start gap-3 text-white/95"
                                >
                                    <span
                                        class="mt-0.5 shrink-0 w-5 h-5 rounded-full bg-emerald-500/20 flex items-center justify-center"
                                        aria-hidden="true"
                                    >
                                        <svg
                                            class="w-3 h-3 text-emerald-400"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    <span class="text-sm xl:text-base leading-snug">{{ item.text }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <footer class="shrink-0 pt-6 border-t border-white/10">
                        <p class="text-sm text-slate-400">© {{ appName }}</p>
                    </footer>
                </div>
            </div>
        </aside>

        <!-- Droite : formulaire -->
        <div class="flex-1 flex items-center justify-center p-6 sm:p-10 lg:p-12 min-w-0 min-h-screen lg:min-h-0 lg:w-1/3">
            <div class="w-full max-w-sm">
                <div class="lg:hidden mb-8 text-center">
                    <Link href="/login" class="inline-block">
                        <img v-if="appLogo" :src="appLogo" :alt="appName" class="h-7 w-auto max-w-[120px] mx-auto object-contain" />
                        <span v-else class="text-lg font-semibold text-slate-900">{{ appName }}</span>
                    </Link>
                    <p class="mt-2 text-sm text-slate-500">
                        Tout votre courtage en un seul endroit
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
