<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import SearchModal from "@/Components/SearchModal.vue";
import { Link, usePage } from "@inertiajs/vue3";
import FlashNotifications from "@/Components/FlashNotifications.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import { useConfirm } from "@/Composables/useConfirm";
import { router } from "@inertiajs/vue3";

const { state: confirmState } = useConfirm();

const page = usePage();
const auth = page.props.auth;

const userMenuOpen = ref(false);
const userMenuRef = ref(null);
const userMenuRefMobile = ref(null);
const notifMenuOpen = ref(false);
const notifMenuRef = ref(null);
const notifMenuRefMobile = ref(null);
const settingsOpen = ref(false);
const sidebarUserMenuOpen = ref(false);
const sidebarUserRef = ref(null);
const mobileMenuOpen = ref(false);
const searchOpen = ref(false);
const logoError = ref(false);
const appName = computed(() => page.props.app?.name || "Certy");

const getInitials = (name) => {
    if (!name) return "?";
    return name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase()
        .slice(0, 2);
};

const closeMenus = (e) => {
    const inUser =
        userMenuRef.value?.contains(e.target) ||
        userMenuRefMobile.value?.contains(e.target);
    if (!inUser) userMenuOpen.value = false;
    const inNotif =
        notifMenuRef.value?.contains(e.target) ||
        notifMenuRefMobile.value?.contains(e.target);
    if (!inNotif) notifMenuOpen.value = false;
    if (sidebarUserRef.value && !sidebarUserRef.value.contains(e.target)) {
        sidebarUserMenuOpen.value = false;
    }
};
const openNotifMenu = () => {
    userMenuOpen.value = false;
    notifMenuOpen.value = !notifMenuOpen.value;
};

const openUserMenu = () => {
    notifMenuOpen.value = false;
    userMenuOpen.value = !userMenuOpen.value;
};

function onKeydown(e) {
    if ((e.metaKey || e.ctrlKey) && e.key === "k") {
        e.preventDefault();
        searchOpen.value = true;
    }
    if (e.key === "Escape") {
        searchOpen.value = false;
        if (confirmState?.open) confirmState.resolve?.(false);
    }
}

onMounted(() => {
    document.addEventListener("click", closeMenus);
    document.addEventListener("keydown", onKeydown);
    router.on("navigate", () => {
        mobileMenuOpen.value = false;
    });
    if (settingsItems.value.some((item) => page.url.startsWith(item.href))) {
        settingsOpen.value = true;
    }
    if (digitalItems.value.some((item) => page.url.startsWith(item.href))) {
        digitalOpen.value = true;
    }
    if (referentialItems.some((item) => page.url.startsWith(item.href))) {
        referentialOpen.value = true;
    }
});
onUnmounted(() => {
    document.removeEventListener("click", closeMenus);
    document.removeEventListener("keydown", onKeydown);
});

watch(mobileMenuOpen, (open) => {
    document.body.style.overflow = open ? "hidden" : "";
});

function onLogoError() {
    logoError.value = true;
}

const isRoot = computed(() => !!auth?.user?.is_root);

const certyIa = computed(() => page.props.certy_ia ?? null);

const navItems = computed(() => {
    const items = [
        { href: "/dashboard", label: "Tableau de bord", icon: "home" },
        { href: "/clients", label: "Clients", icon: "folder" },
        { href: "/vehicles", label: "Véhicules", icon: "sparkles" },
        { href: "/contracts", label: "Contrats", icon: "credit" },
    ];
    if (isRoot.value) {
        items.push(
            {
                href: "/bordereaux",
                label: "Bordereaux",
                icon: "documentText",
            },
            {
                href: "/reports/production",
                label: "Production",
                icon: "chart",
            },
        );
    }
    if (certyIa.value?.enabled) {
        items.push({
            href: "/certy-ia",
            label: certyIa.value.name || "Certy IA",
            icon: "chat",
        });
    }
    return items;
});

const digitalOpen = ref(false);
const digitalItems = computed(() => {
    const items = [
        {
            href: "/digital/attestations",
            label: "Attestations",
            icon: "credit",
        },
    ];
    if (isRoot.value) {
        items.push(
            { href: "/digital/bureaux", label: "Bureaux", icon: "building" },
            {
                href: "/digital/rattachements",
                label: "Rattachements",
                icon: "building",
            },
            { href: "/digital/stock", label: "Stock", icon: "info" },
            {
                href: "/digital/utilisateurs",
                label: "Utilisateurs",
                icon: "users",
            },
        );
    }
    return items;
});

const referentialOpen = ref(false);
const referentialItems = [
    { href: "/referential/brands", label: "Marques", icon: "menuAlt" },
    { href: "/referential/models", label: "Modèles", icon: "menuAlt" },
];

const settingsItems = computed(() => {
    const items = [
        { href: "/settings/profile", label: "Profil", icon: "user" },
        { href: "/settings/config", label: "Config courtier", icon: "cog" },
    ];
    if (certyIa.value?.enabled && isRoot.value) {
        items.push({
            href: "/settings/certy-ia",
            label: certyIa.value.name || "Certy IA",
            icon: "chat",
        });
    }
    return items;
});

const isDigitalActive = () =>
    digitalItems.value.some((item) => page.url.startsWith(item.href));
const isReferentialActive = () =>
    referentialItems.some((item) => page.url.startsWith(item.href));
const isSettingsActive = () =>
    settingsItems.value.some((item) => page.url.startsWith(item.href));

const isActive = (href) => {
    if (href === "/dashboard") return page.url === "/dashboard";
    return page.url.startsWith(href);
};

const iconPaths = {
    home: "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",
    folder: "M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z",
    sparkles:
        "M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z",
    cog: "M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z",
    building:
        "M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4",
    users: "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z",
    credit: "M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z",
    user: "M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z",
    external:
        "M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14",
    chat: "M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z",
    info: "M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z",
    chevronDown: "M19 9l-7 7-7-7",
    chevronRight: "M9 5l7 7-7 7",
    menuAlt: "M4 6h16M4 12h16M4 18h16",
    documentText:
        "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
    chart: "M4 20h16M7 20V10m5 10V6m5 14V4",
};
</script>

<template>
    <div class="min-h-screen bg-white">
        <FlashNotifications />
        <SearchModal :open="searchOpen" @close="searchOpen = false" />
        <ConfirmModal
            :open="confirmState.open"
            :title="confirmState.title"
            :message="confirmState.message"
            :confirm-label="confirmState.confirmLabel"
            :cancel-label="confirmState.cancelLabel"
            :variant="confirmState.variant"
            @confirm="confirmState.resolve?.(true)"
            @cancel="confirmState.resolve?.(false)"
        />
        <!-- Sidebar verticale à gauche (contexte Nuxt UI) -->
        <aside
            class="fixed inset-y-0 left-0 z-40 w-56 border-r border-slate-200 bg-slate-50/80 hidden lg:flex lg:flex-col"
        >
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div
                    class="h-14 px-4 flex items-center border-b border-slate-200 bg-white/80 shrink-0"
                >
                    <Link
                        href="/dashboard"
                        class="flex items-center min-w-0"
                        :aria-label="`${appName}, accueil`"
                    >
                        <img
                            v-if="page.props.app?.logo && !logoError"
                            :src="page.props.app.logo"
                            :alt="appName"
                            class="h-11 w-auto max-w-[180px] object-contain object-left shrink-0"
                            @error="onLogoError"
                        />
                        <span
                            v-else
                            class="text-base font-semibold text-slate-900 truncate"
                            >{{ appName }}</span
                        >
                    </Link>
                </div>
                <!-- Recherche -->
                <div class="px-3 py-3 shrink-0">
                    <div class="relative">
                        <svg
                            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                        <button
                            type="button"
                            class="w-full pl-9 pr-8 py-2 text-sm text-left bg-white border border-slate-200 rounded-lg placeholder:text-slate-400 hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-brand-primary/20 focus:border-brand-primary"
                            @click="searchOpen = true"
                        >
                            <span class="text-slate-400">Rechercher...</span>
                        </button>
                        <kbd
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 hidden sm:inline-flex items-center gap-0.5 px-1.5 py-0.5 text-xs font-medium text-slate-400 bg-slate-100 rounded"
                            >⌘K</kbd
                        >
                    </div>
                </div>
                <!-- Nav principale -->
                <nav
                    class="px-3 py-2 space-y-0.5 overflow-y-auto flex-1 min-h-0"
                >
                    <template v-for="item in navItems" :key="item.href">
                        <Link
                            v-if="!item.comingSoon"
                            :href="item.href"
                            class="flex items-center gap-2.5 px-3 py-2.5 text-sm transition-colors rounded-lg"
                            :class="
                                isActive(item.href)
                                    ? 'text-brand-primary bg-brand-primary/10 font-medium'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            "
                        >
                            <svg
                                class="w-4 h-4 shrink-0"
                                :class="
                                    isActive(item.href)
                                        ? 'text-brand-primary'
                                        : 'opacity-70'
                                "
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="iconPaths[item.icon] || iconPaths.home"
                                />
                            </svg>
                            {{ item.label }}
                        </Link>
                        <span
                            v-else
                            class="flex items-center gap-2.5 px-3 py-2.5 text-sm text-slate-400 rounded-lg"
                        >
                            <svg
                                class="w-4 h-4 shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="iconPaths[item.icon] || iconPaths.home"
                                />
                            </svg>
                            {{ item.label }}
                        </span>
                    </template>
                    <!-- Digital (service externe ASACI) -->
                    <div class="pt-1">
                        <button
                            type="button"
                            class="flex items-center gap-2.5 px-3 py-2.5 text-sm w-full text-left rounded-lg transition-colors"
                            :class="
                                digitalOpen || isDigitalActive()
                                    ? 'text-slate-900 bg-slate-100 font-medium'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            "
                            @click="digitalOpen = !digitalOpen"
                        >
                            <svg
                                class="w-4 h-4 shrink-0 opacity-70"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="iconPaths.external"
                                />
                            </svg>
                            Digital
                            <svg
                                class="w-4 h-4 ml-auto shrink-0 transition-transform"
                                :class="digitalOpen ? 'rotate-90' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="iconPaths.chevronRight"
                                />
                            </svg>
                        </button>
                        <Transition
                            enter-active-class="transition ease-out duration-100"
                            enter-from-class="opacity-0 -translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-1"
                        >
                            <div
                                v-show="digitalOpen || isDigitalActive()"
                                class="pl-6 pr-2 pb-1 space-y-0.5"
                            >
                                <Link
                                    v-for="d in digitalItems"
                                    :key="d.href"
                                    :href="d.href"
                                    class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors"
                                    :class="
                                        page.url.startsWith(d.href)
                                            ? 'text-brand-primary bg-brand-primary/10 font-medium'
                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                    "
                                >
                                    <svg
                                        class="w-4 h-4 shrink-0 opacity-70"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            :d="
                                                iconPaths[d.icon] ||
                                                iconPaths.external
                                            "
                                        />
                                    </svg>
                                    {{ d.label }}
                                </Link>
                            </div>
                        </Transition>
                    </div>
                    <!-- Référentiel (Marques & Modèles) -->
                    <div class="pt-1">
                        <button
                            type="button"
                            class="flex items-center gap-2.5 px-3 py-2.5 text-sm w-full text-left rounded-lg transition-colors"
                            :class="
                                referentialOpen || isReferentialActive()
                                    ? 'text-slate-900 bg-slate-100 font-medium'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            "
                            @click="referentialOpen = !referentialOpen"
                        >
                            <svg
                                class="w-4 h-4 shrink-0 opacity-70"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="iconPaths.menuAlt"
                                />
                            </svg>
                            Référentiel
                            <svg
                                class="w-4 h-4 ml-auto shrink-0 transition-transform"
                                :class="referentialOpen ? 'rotate-90' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="iconPaths.chevronRight"
                                />
                            </svg>
                        </button>
                        <Transition
                            enter-active-class="transition ease-out duration-100"
                            enter-from-class="opacity-0 -translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-1"
                        >
                            <div
                                v-show="
                                    referentialOpen || isReferentialActive()
                                "
                                class="pl-6 pr-2 pb-1 space-y-0.5"
                            >
                                <Link
                                    v-for="r in referentialItems"
                                    :key="r.href"
                                    :href="r.href"
                                    class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors"
                                    :class="
                                        page.url.startsWith(r.href)
                                            ? 'text-brand-primary bg-brand-primary/10 font-medium'
                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                    "
                                >
                                    <svg
                                        class="w-4 h-4 shrink-0 opacity-70"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            :d="
                                                iconPaths[r.icon] ||
                                                iconPaths.menuAlt
                                            "
                                        />
                                    </svg>
                                    {{ r.label }}
                                </Link>
                            </div>
                        </Transition>
                    </div>
                    <!-- Paramètres (dépliable) — réservé aux utilisateurs root -->
                    <div v-if="isRoot" class="pt-1">
                        <button
                            type="button"
                            class="flex items-center gap-2.5 px-3 py-2.5 text-sm w-full text-left rounded-lg transition-colors"
                            :class="
                                settingsOpen || isSettingsActive()
                                    ? 'text-slate-900 bg-slate-100 font-medium'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                            "
                            @click="settingsOpen = !settingsOpen"
                        >
                            <svg
                                class="w-4 h-4 shrink-0 opacity-70"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="iconPaths.cog"
                                />
                            </svg>
                            Paramètres
                            <svg
                                class="w-4 h-4 ml-auto shrink-0 transition-transform"
                                :class="settingsOpen ? 'rotate-90' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="iconPaths.chevronRight"
                                />
                            </svg>
                        </button>
                        <Transition
                            enter-active-class="transition ease-out duration-100"
                            enter-from-class="opacity-0 -translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-1"
                        >
                            <div
                                v-show="settingsOpen || isSettingsActive()"
                                class="pl-6 pr-2 pb-1 space-y-0.5"
                            >
                                <Link
                                    v-for="s in settingsItems"
                                    :key="s.href"
                                    :href="s.href"
                                    class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors"
                                    :class="
                                        page.url.startsWith(s.href)
                                            ? 'text-brand-primary bg-brand-primary/10 font-medium'
                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                    "
                                >
                                    <svg
                                        class="w-4 h-4 shrink-0 opacity-70"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            :d="
                                                iconPaths[s.icon] ||
                                                iconPaths.user
                                            "
                                        />
                                    </svg>
                                    {{ s.label }}
                                </Link>
                            </div>
                        </Transition>
                    </div>
                </nav>
                <!-- Bas de sidebar: Aide, Utilisateur -->
                <div
                    class="p-3 border-t border-slate-200 bg-white/50 space-y-0.5 shrink-0"
                >
                    <a
                        href="#"
                        class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900 rounded-lg"
                    >
                        <svg
                            class="w-4 h-4 shrink-0 opacity-70"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                :d="iconPaths.info"
                            />
                        </svg>
                        Aide & support
                        <svg
                            class="w-3.5 h-3.5 ml-auto opacity-50"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                :d="iconPaths.external"
                            />
                        </svg>
                    </a>
                    <div
                        ref="sidebarUserRef"
                        class="relative pt-2 mt-1 border-t border-slate-200"
                    >
                        <button
                            type="button"
                            class="flex items-center gap-3 w-full px-3 py-2 rounded-lg hover:bg-slate-100 transition-colors text-left"
                            @click="sidebarUserMenuOpen = !sidebarUserMenuOpen"
                        >
                            <span
                                class="w-8 h-8 rounded-full bg-brand-primary/15 text-brand-primary text-sm font-semibold flex items-center justify-center shrink-0"
                            >
                                {{ getInitials(auth?.user?.name) }}
                            </span>
                            <span
                                class="text-sm text-slate-700 truncate flex-1 min-w-0"
                                >{{ auth?.user?.name }}</span
                            >
                            <svg
                                class="w-4 h-4 text-slate-500 shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="iconPaths.chevronDown"
                                />
                            </svg>
                        </button>
                        <Transition
                            enter-active-class="transition ease-out duration-100"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div
                                v-show="sidebarUserMenuOpen"
                                class="absolute bottom-full left-0 right-0 mb-1 py-1 bg-white rounded-lg border border-slate-200 z-50"
                            >
                                <div
                                    class="px-4 py-2 border-b border-slate-100"
                                >
                                    <p class="text-xs text-slate-500">
                                        Connecté en tant que
                                    </p>
                                    <p
                                        class="text-sm font-medium text-slate-900 truncate"
                                    >
                                        {{ auth?.user?.name }}
                                    </p>
                                    <p class="text-xs text-slate-500 truncate">
                                        {{
                                            auth?.user?.current_organization
                                                ?.name || "—"
                                        }}
                                    </p>
                                </div>
                                <Link
                                    v-if="isRoot"
                                    href="/settings/profile"
                                    class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                    @click="sidebarUserMenuOpen = false"
                                    >Profil</Link
                                >
                                <button
                                    @click="
                                        sidebarUserMenuOpen = false;
                                        router.post('/logout');
                                    "
                                    class="flex w-full items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 text-left"
                                >
                                    Déconnexion
                                </button>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile header : hamburger + cloche + avatar sur la même ligne -->
        <header
            class="lg:hidden fixed top-0 left-0 right-0 z-40 h-14 border-b border-slate-200 flex items-center justify-between gap-2 px-3 sm:px-4 bg-white"
        >
            <button
                type="button"
                class="p-2 -ml-1 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg shrink-0"
                aria-label="Ouvrir le menu"
                @click="mobileMenuOpen = true"
            >
                <svg
                    class="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 6h16M4 12h16M4 18h16"
                    />
                </svg>
            </button>
            <div class="flex items-center gap-0.5 shrink-0">
                <div ref="notifMenuRefMobile" class="relative">
                    <button
                        @click.stop="openNotifMenu"
                        class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                        aria-label="Notifications"
                    >
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
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                            />
                        </svg>
                    </button>
                    <Transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-show="notifMenuOpen"
                            class="absolute right-0 mt-1 w-72 max-w-[calc(100vw-2rem)] py-1 bg-white rounded-lg border border-slate-200 z-50"
                        >
                            <div class="px-4 py-3 border-b border-slate-100">
                                <p class="text-sm font-medium text-slate-900">
                                    Notifications
                                </p>
                            </div>
                            <div class="py-8 px-4 text-center">
                                <svg
                                    class="w-10 h-10 text-slate-300 mx-auto mb-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                    />
                                </svg>
                                <p class="text-sm text-slate-500">
                                    Aucune notification
                                </p>
                            </div>
                        </div>
                    </Transition>
                </div>
                <div ref="userMenuRefMobile" class="relative">
                    <button
                        @click.stop="openUserMenu"
                        class="flex items-center gap-1.5 p-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                    >
                        <span
                            class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 text-sm font-medium flex items-center justify-center shrink-0"
                        >
                            {{ getInitials(auth?.user?.name) }}
                        </span>
                        <svg
                            class="w-4 h-4 text-slate-500 shrink-0 hidden sm:block"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            />
                        </svg>
                    </button>
                    <Transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-show="userMenuOpen"
                            class="absolute right-0 mt-1 w-56 max-w-[calc(100vw-2rem)] py-1 bg-white rounded-lg border border-slate-200 z-50"
                        >
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-xs text-slate-500">
                                    Connecté en tant que
                                </p>
                                <p
                                    class="text-sm font-medium text-slate-900 truncate"
                                >
                                    {{ auth?.user?.name }}
                                </p>
                            </div>
                            <Link
                                v-if="isRoot"
                                href="/settings/profile"
                                class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                @click="userMenuOpen = false"
                            >
                                <svg
                                    class="w-4 h-4 text-slate-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    />
                                </svg>
                                Profil
                            </Link>
                            <button
                                @click="
                                    userMenuOpen = false;
                                    router.post('/logout');
                                "
                                class="flex w-full items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 text-left"
                            >
                                <svg
                                    class="w-4 h-4 text-slate-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                    />
                                </svg>
                                Déconnexion
                            </button>
                        </div>
                    </Transition>
                </div>
            </div>
        </header>

        <!-- Mobile menu drawer -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="mobileMenuOpen"
                    class="fixed inset-0 z-50 lg:hidden"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Menu de navigation"
                >
                    <div
                        class="absolute inset-0 bg-slate-900/50"
                        @click="mobileMenuOpen = false"
                    />
                    <div
                        class="absolute inset-y-0 left-0 w-72 max-w-[85vw] bg-white border-r border-slate-200 flex flex-col"
                    >
                        <div
                            class="h-14 px-4 flex items-center justify-between border-b border-slate-200 shrink-0"
                        >
                            <Link
                                href="/dashboard"
                                class="flex items-center gap-2 min-w-0"
                                @click="mobileMenuOpen = false"
                            >
                                <img
                                    v-if="page.props.app?.logo && !logoError"
                                    :src="page.props.app.logo"
                                    :alt="appName"
                                    class="h-16 w-auto max-w-[140px] object-contain"
                                />
                                <span
                                    v-else
                                    class="text-base font-semibold text-slate-900"
                                    >{{ appName }}</span
                                >
                            </Link>
                            <button
                                type="button"
                                class="p-2 text-slate-500 hover:bg-slate-100 rounded-lg"
                                aria-label="Fermer le menu"
                                @click="mobileMenuOpen = false"
                            >
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
                        <nav class="flex-1 overflow-y-auto p-3 space-y-1">
                            <template v-for="item in navItems" :key="item.href">
                                <Link
                                    v-if="!item.comingSoon"
                                    :href="item.href"
                                    class="flex items-center gap-2.5 px-3 py-2.5 text-sm rounded-lg"
                                    :class="
                                        isActive(item.href)
                                            ? 'text-brand-primary bg-brand-primary/10 font-medium'
                                            : 'text-slate-600 hover:bg-slate-100'
                                    "
                                    @click="mobileMenuOpen = false"
                                >
                                    <svg
                                        class="w-4 h-4 shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            :d="
                                                iconPaths[item.icon] ||
                                                iconPaths.home
                                            "
                                        />
                                    </svg>
                                    {{ item.label }}
                                </Link>
                            </template>
                            <div
                                class="pt-3 mt-3 border-t border-slate-200 space-y-0.5"
                            >
                                <p
                                    class="px-3 py-1.5 text-xs font-medium text-slate-400 uppercase tracking-wider"
                                >
                                    Digital
                                </p>
                                <Link
                                    v-for="d in digitalItems"
                                    :key="d.href"
                                    :href="d.href"
                                    class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg"
                                    :class="
                                        page.url.startsWith(d.href)
                                            ? 'text-brand-primary bg-brand-primary/10'
                                            : 'text-slate-600 hover:bg-slate-100'
                                    "
                                    @click="mobileMenuOpen = false"
                                >
                                    <svg
                                        class="w-4 h-4 shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            :d="
                                                iconPaths[d.icon] ||
                                                iconPaths.external
                                            "
                                        />
                                    </svg>
                                    {{ d.label }}
                                </Link>
                            </div>
                            <div class="pt-2 space-y-0.5">
                                <p
                                    class="px-3 py-1.5 text-xs font-medium text-slate-400 uppercase tracking-wider"
                                >
                                    Référentiel
                                </p>
                                <Link
                                    v-for="r in referentialItems"
                                    :key="r.href"
                                    :href="r.href"
                                    class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg"
                                    :class="
                                        page.url.startsWith(r.href)
                                            ? 'text-brand-primary bg-brand-primary/10'
                                            : 'text-slate-600 hover:bg-slate-100'
                                    "
                                    @click="mobileMenuOpen = false"
                                >
                                    <svg
                                        class="w-4 h-4 shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            :d="
                                                iconPaths[r.icon] ||
                                                iconPaths.menuAlt
                                            "
                                        />
                                    </svg>
                                    {{ r.label }}
                                </Link>
                            </div>
                            <div v-if="isRoot" class="pt-2 space-y-0.5">
                                <p
                                    class="px-3 py-1.5 text-xs font-medium text-slate-400 uppercase tracking-wider"
                                >
                                    Paramètres
                                </p>
                                <Link
                                    v-for="s in settingsItems"
                                    :key="s.href"
                                    :href="s.href"
                                    class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg"
                                    :class="
                                        page.url.startsWith(s.href)
                                            ? 'text-brand-primary bg-brand-primary/10'
                                            : 'text-slate-600 hover:bg-slate-100'
                                    "
                                    @click="mobileMenuOpen = false"
                                >
                                    <svg
                                        class="w-4 h-4 shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            :d="
                                                iconPaths[s.icon] ||
                                                iconPaths.user
                                            "
                                        />
                                    </svg>
                                    {{ s.label }}
                                </Link>
                            </div>
                        </nav>
                        <div class="p-3 border-t border-slate-200">
                            <div class="flex items-center gap-3 px-3 py-2">
                                <span
                                    class="w-9 h-9 rounded-full bg-brand-primary/15 text-brand-primary text-sm font-semibold flex items-center justify-center shrink-0"
                                >
                                    {{ getInitials(auth?.user?.name) }}
                                </span>
                                <span class="text-sm text-slate-700 truncate">{{
                                    auth?.user?.name
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Main -->
        <main class="lg:pl-56 pt-14 lg:pt-0 min-h-screen flex flex-col min-w-0">
            <header
                class="hidden lg:flex sticky top-0 z-30 h-14 shrink-0 border-b border-slate-200 items-center justify-end px-3 sm:px-4 lg:px-6 xl:px-8 bg-white"
            >
                <div class="flex items-center gap-2 sm:gap-3">
                    <div ref="notifMenuRef" class="relative">
                        <button
                            @click.stop="openNotifMenu"
                            class="p-2 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                            title="Notifications"
                        >
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
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                />
                            </svg>
                        </button>
                        <Transition
                            enter-active-class="transition ease-out duration-100"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div
                                v-show="notifMenuOpen"
                                class="absolute right-0 mt-1 w-72 sm:w-80 max-w-[calc(100vw-2rem)] py-1 bg-white rounded-lg border border-slate-200 z-50"
                            >
                                <div
                                    class="px-4 py-3 border-b border-slate-100"
                                >
                                    <p
                                        class="text-sm font-medium text-slate-900"
                                    >
                                        Notifications
                                    </p>
                                </div>
                                <div class="py-8 px-4 text-center">
                                    <svg
                                        class="w-10 h-10 text-slate-300 mx-auto mb-2"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                        />
                                    </svg>
                                    <p class="text-sm text-slate-500">
                                        Aucune notification
                                    </p>
                                </div>
                            </div>
                        </Transition>
                    </div>
                    <div ref="userMenuRef" class="relative">
                        <button
                            @click.stop="openUserMenu"
                            class="flex items-center gap-2 p-1 pr-2 rounded-lg hover:bg-slate-100 transition-colors"
                        >
                            <span
                                class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 text-sm font-medium flex items-center justify-center shrink-0"
                            >
                                {{ getInitials(auth?.user?.name) }}
                            </span>
                            <span
                                class="text-sm text-slate-700 hidden sm:block max-w-[120px] truncate"
                                >{{ auth?.user?.name }}</span
                            >
                            <svg
                                class="w-4 h-4 text-slate-500 shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>
                        <Transition
                            enter-active-class="transition ease-out duration-100"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div
                                v-show="userMenuOpen"
                                class="absolute right-0 mt-1 w-56 max-w-[calc(100vw-2rem)] py-1 bg-white rounded-lg border border-slate-200 z-50"
                            >
                                <div
                                    class="px-4 py-2 border-b border-slate-100"
                                >
                                    <p class="text-xs text-slate-500">
                                        Connecté en tant que
                                    </p>
                                    <p
                                        class="text-sm font-medium text-slate-900 truncate"
                                    >
                                        {{ auth?.user?.name }}
                                    </p>
                                </div>
                                <Link
                                    v-if="isRoot"
                                    href="/settings/profile"
                                    class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                                    @click="userMenuOpen = false"
                                >
                                    <svg
                                        class="w-4 h-4 text-slate-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        />
                                    </svg>
                                    Profil
                                </Link>
                                <button
                                    @click="
                                        userMenuOpen = false;
                                        router.post('/logout');
                                    "
                                    class="flex w-full items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 text-left"
                                >
                                    <svg
                                        class="w-4 h-4 text-slate-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                        />
                                    </svg>
                                    Déconnexion
                                </button>
                            </div>
                        </Transition>
                    </div>
                </div>
            </header>
            <div
                v-if="$slots.header"
                class="shrink-0 border-b border-slate-200 px-3 sm:px-4 lg:px-6 xl:px-8 py-4 sm:py-5 bg-white"
            >
                <slot name="header" />
            </div>
            <div
                class="flex-1 min-h-0 flex flex-col p-3 sm:p-4 lg:p-6 xl:p-8 min-w-0 overflow-x-hidden"
            >
                <slot />
            </div>
        </main>
    </div>
</template>
