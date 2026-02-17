<script setup>
import { ref, computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { route } from '@/route';

const props = defineProps({
    available: Boolean,
    message: { type: String, default: '' },
    name: { type: String, default: 'Certy IA' },
    agentLabel: { type: String, default: '' },
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: props.name },
];

const defaultQuestions = [
    'Combien ai-je de contrats au total ?',
    'Quel est le montant total des revenus générés ?',
    'Liste les clients avec leur véhicule',
    'Combien de véhicules sont assurés ?',
    'Quels sont mes contrats actifs ?',
    'Résume l\'activité des derniers mois',
];

const question = ref('');
const loading = ref(false);
const error = ref(null);
const messages = ref([]);

const canSend = computed(() => props.available && question.value.trim().length > 0 && !loading.value);
const showSuggestions = computed(() => props.available && messages.value.length === 0 && !loading.value);

async function send(q = question.value?.trim()) {
    if (!props.available || !q) return;
    question.value = '';
    messages.value.push({ role: 'user', content: q });
    loading.value = true;
    error.value = null;
    try {
        const res = await fetch(route('certy-ia.ask'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ question: q }),
            credentials: 'same-origin',
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            messages.value.push({
                role: 'assistant',
                content: data.answer || 'Une erreur est survenue.',
            });
            return;
        }
        messages.value.push({ role: 'assistant', content: data.answer || '—' });
    } catch (e) {
        error.value = 'Erreur de connexion. Réessayez.';
        messages.value.push({ role: 'assistant', content: error.value });
    } finally {
        loading.value = false;
    }
}

function useSuggestion(text) {
    send(text);
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" :title="name" />
        </template>

        <div class="flex-1 min-h-full flex flex-col w-full max-w-3xl mx-auto px-0 sm:px-4">
            <!-- Non disponible : message chic -->
            <div
                v-if="!available"
                class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden"
            >
                <div class="bg-gradient-to-br from-slate-50 to-slate-100/80 px-6 sm:px-10 py-12 text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-slate-200/60 text-slate-500 mb-6">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-slate-800 mb-2">
                        {{ name }} — Bientôt disponible
                    </h2>
                    <p class="text-slate-600 text-sm max-w-md mx-auto leading-relaxed mb-4">
                        {{ name }} est l'assistant intelligent qui vous permettra d'interroger vos données en langage naturel : clients, véhicules, contrats et bordereaux. Posez une question, obtenez une réponse synthétique et factuelle.
                    </p>
                    <p class="text-slate-500 text-xs">
                        Le développement de cette fonctionnalité est en cours. Elle sera activée prochainement.
                    </p>
                </div>
            </div>

            <!-- Interface Certy IA -->
            <template v-else>
                <div class="flex flex-col flex-1 min-h-0 rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                    <!-- Rappel de l'agent utilisé -->
                    <div v-if="agentLabel" class="px-4 py-2 border-b border-slate-100 bg-slate-50/50 flex items-center justify-center gap-1.5 text-xs text-slate-500">
                        <span class="font-medium text-slate-600">Agent :</span>
                        <span>{{ agentLabel }}</span>
                    </div>
                    <!-- Zone de conversation -->
                    <div class="flex-1 overflow-y-auto min-h-[320px]">
                        <!-- État vide : suggestions -->
                        <div
                            v-if="showSuggestions"
                            class="p-6 sm:p-8"
                        >
                            <p class="text-sm text-slate-500 text-center mb-6">
                                Posez une question sur vos clients, véhicules, contrats ou bordereaux. Cliquez sur une suggestion ou tapez votre question.
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <button
                                    v-for="(suggestion, idx) in defaultQuestions"
                                    :key="idx"
                                    type="button"
                                    class="text-left rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-100 hover:border-slate-300 px-4 py-3 text-sm text-slate-700 transition-colors"
                                    :disabled="loading"
                                    @click="useSuggestion(suggestion)"
                                >
                                    {{ suggestion }}
                                </button>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div
                            v-else
                            class="p-4 sm:p-6 space-y-4"
                        >
                            <div
                                v-for="(msg, i) in messages"
                                :key="i"
                                :class="[
                                    'flex',
                                    msg.role === 'user' ? 'justify-end' : 'justify-start'
                                ]"
                            >
                                <div
                                    :class="[
                                        'max-w-[88%] rounded-2xl px-4 py-3 text-sm leading-relaxed',
                                        msg.role === 'user'
                                            ? 'bg-slate-900 text-white rounded-br-md'
                                            : 'bg-slate-100 text-slate-800 whitespace-pre-wrap rounded-bl-md'
                                    ]"
                                >
                                    {{ msg.content }}
                                </div>
                            </div>
                            <div v-if="loading" class="flex justify-start">
                                <div class="rounded-2xl rounded-bl-md bg-slate-100 text-slate-500 px-4 py-3 text-sm flex items-center gap-2">
                                    <span class="inline-block w-2 h-2 rounded-full bg-slate-400 animate-pulse" />
                                    <span class="inline-block w-2 h-2 rounded-full bg-slate-400 animate-pulse" style="animation-delay: 0.2s" />
                                    <span class="inline-block w-2 h-2 rounded-full bg-slate-400 animate-pulse" style="animation-delay: 0.4s" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Erreur globale -->
                    <div v-if="error" class="mx-4 mb-2 rounded-lg bg-red-50 text-red-700 text-sm px-3 py-2">
                        {{ error }}
                    </div>

                    <!-- Saisie -->
                    <div class="p-4 border-t border-slate-100 bg-slate-50/30">
                        <form @submit.prevent="send()" class="flex gap-3">
                            <input
                                v-model="question"
                                type="text"
                                class="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-400/20 focus:outline-none transition-shadow"
                                placeholder="Posez votre question…"
                                :disabled="loading"
                            />
                            <button
                                type="submit"
                                :disabled="!canSend"
                                class="shrink-0 px-5 py-3 bg-slate-900 text-white text-sm font-medium rounded-xl hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                            >
                                Envoyer
                            </button>
                        </form>
                    </div>
                </div>
            </template>
        </div>
    </DashboardLayout>
</template>
