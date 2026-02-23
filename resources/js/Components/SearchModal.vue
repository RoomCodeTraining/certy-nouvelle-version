<script setup>
import { ref, watch, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";

const props = defineProps({
    open: { type: Boolean, default: false },
});

const emit = defineEmits(["close"]);

const query = ref("");
const results = ref({ clients: [], vehicles: [], contracts: [] });
const loading = ref(false);
const debounceTimer = ref(null);

async function doSearch() {
    const q = query.value.trim();
    if (q.length < 2) {
        results.value = { clients: [], vehicles: [], contracts: [] };
        return;
    }
    loading.value = true;
    try {
        const res = await fetch(`/api/search?q=${encodeURIComponent(q)}`, { credentials: "include" });
        const data = await res.json();
        results.value = {
            clients: data.clients ?? [],
            vehicles: data.vehicles ?? [],
            contracts: data.contracts ?? [],
        };
    } catch {
        results.value = { clients: [], vehicles: [], contracts: [] };
    } finally {
        loading.value = false;
    }
}

watch(query, () => {
    clearTimeout(debounceTimer.value);
    debounceTimer.value = setTimeout(doSearch, 200);
});

watch(() => props.open, (open) => {
    if (open) {
        query.value = "";
        results.value = { clients: [], vehicles: [], contracts: [] };
        setTimeout(() => document.querySelector(".search-modal-input")?.focus(), 50);
    }
});

function goTo(href) {
    emit("close");
    router.visit(href);
}

function onKeydown(e) {
    if (e.key === "Escape") {
        emit("close");
    }
}

const hasResults = computed(
    () =>
        (results.value.clients?.length ?? 0) > 0 ||
        (results.value.vehicles?.length ?? 0) > 0 ||
        (results.value.contracts?.length ?? 0) > 0,
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[100] flex items-start justify-center pt-[15vh] px-4 bg-slate-900/50"
                role="dialog"
                aria-modal="true"
                aria-label="Recherche globale"
                @keydown.esc="emit('close')"
                @click.self="emit('close')"
            >
                <div
                    class="w-full max-w-xl bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden"
                    @keydown="onKeydown"
                >
                    <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-200">
                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="query"
                            type="search"
                            class="search-modal-input flex-1 text-base placeholder:text-slate-400 focus:outline-none py-1"
                            placeholder="Rechercher clients, véhicules, contrats..."
                            autocomplete="off"
                        />
                        <kbd class="hidden sm:inline-flex px-2 py-0.5 text-xs text-slate-400 bg-slate-100 rounded">Échap</kbd>
                    </div>
                    <div class="max-h-[60vh] overflow-y-auto py-2">
                        <div v-if="loading" class="px-4 py-8 text-center text-slate-500 text-sm">Recherche…</div>
                        <template v-else-if="query.trim().length >= 2">
                            <div v-if="!hasResults" class="px-4 py-8 text-center text-slate-500 text-sm">Aucun résultat pour « {{ query }} »</div>
                            <template v-else>
                                <div v-if="results.clients?.length" class="px-2">
                                    <p class="px-2 py-1.5 text-xs font-medium text-slate-400 uppercase">Clients</p>
                                    <button
                                        v-for="r in results.clients"
                                        :key="'c-' + r.id"
                                        type="button"
                                        class="w-full text-left px-4 py-2.5 rounded-lg hover:bg-slate-50 text-sm text-slate-900 flex items-center gap-2"
                                        @click="goTo(r.href)"
                                    >
                                        <span class="truncate">{{ r.label }}</span>
                                    </button>
                                </div>
                                <div v-if="results.vehicles?.length" class="px-2">
                                    <p class="px-2 py-1.5 text-xs font-medium text-slate-400 uppercase">Véhicules</p>
                                    <button
                                        v-for="r in results.vehicles"
                                        :key="'v-' + r.id"
                                        type="button"
                                        class="w-full text-left px-4 py-2.5 rounded-lg hover:bg-slate-50 text-sm text-slate-900 flex items-center gap-2"
                                        @click="goTo(r.href)"
                                    >
                                        <span class="truncate">{{ r.label }}</span>
                                    </button>
                                </div>
                                <div v-if="results.contracts?.length" class="px-2">
                                    <p class="px-2 py-1.5 text-xs font-medium text-slate-400 uppercase">Contrats</p>
                                    <button
                                        v-for="r in results.contracts"
                                        :key="'t-' + r.id"
                                        type="button"
                                        class="w-full text-left px-4 py-2.5 rounded-lg hover:bg-slate-50 text-sm text-slate-900 flex items-center gap-2"
                                        @click="goTo(r.href)"
                                    >
                                        <span class="truncate">{{ r.label }}</span>
                                    </button>
                                </div>
                            </template>
                        </template>
                        <div v-else-if="query.trim().length > 0" class="px-4 py-6 text-center text-slate-500 text-sm">Tapez au moins 2 caractères</div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
