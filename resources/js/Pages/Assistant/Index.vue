<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const page = usePage();
const subscription = computed(() => page.props.auth?.subscription);

const props = defineProps({
    documentCount: Number,
    messages: {
        type: Array,
        default: () => [],
    },
});

const question = ref('');
const loading = ref(false);
const error = ref('');
const localMessages = ref([...props.messages]);
const messagesEndRef = ref(null);

const displayMessages = computed(() => localMessages.value);

const scrollToBottom = () => {
    nextTick(() => {
        messagesEndRef.value?.scrollIntoView({ behavior: 'smooth' });
    });
};

watch(() => props.messages, (val) => {
    localMessages.value = [...val];
    scrollToBottom();
}, { immediate: true });

const ask = async () => {
    const q = question.value?.trim();
    if (!q) return;
    loading.value = true;
    error.value = '';

    // Ajouter le message utilisateur immédiatement
    const userMsg = {
        role: 'user',
        content: q,
        created_at: new Date().toISOString(),
    };
    localMessages.value.push(userMsg);
    question.value = '';
    scrollToBottom();

    try {
        const { data } = await axios.post('/assistant/ask', { question: q }, {
            headers: { Accept: 'application/json' },
            timeout: 120000,
        });
        localMessages.value.push(data.message);
    } catch (e) {
        error.value = e.response?.data?.error ?? 'Une erreur est survenue.';
    } finally {
        loading.value = false;
        scrollToBottom();
    }
};
</script>

<template>
    <DashboardLayout>
        <template #header>
            <h1 class="text-sm font-medium text-slate-900">Assistant IA</h1>
        </template>

        <div class="flex flex-col min-h-[calc(100vh-3.5rem)] -mx-4 -mt-4 lg:-mx-8 lg:-mt-8">
            <div class="flex-1 overflow-y-auto">
                <div class="max-w-3xl mx-auto px-4 py-8">
                    <div v-if="displayMessages.length === 0" class="text-center py-16">
                        <div class="w-14 h-14 rounded-xl border border-slate-100 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                        <h2 class="text-base font-medium text-slate-900 mb-2">Archives intelligentes</h2>
                        <p class="text-sm text-slate-500 mb-1">Posez des questions sur vos documents.</p>
                        <p v-if="documentCount > 0" class="text-xs text-slate-400">{{ documentCount }} document(s) dans le contexte</p>
                        <p v-if="subscription" class="text-xs text-slate-500 mt-2">
                            {{ subscription.assistant_calls_remaining }} / {{ subscription.assistant_calls_limit }} appels ce mois
                        </p>
                    </div>

                    <div v-else class="space-y-8">
                        <template v-for="(msg, i) in displayMessages" :key="i">
                            <div
                                :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'"
                            >
                                <div
                                    :class="[
                                        'max-w-[85%] rounded-2xl px-4 py-3 text-sm',
                                        msg.role === 'user'
                                            ? 'bg-slate-900 text-white'
                                            : 'bg-slate-100 text-slate-800',
                                    ]"
                                >
                                    <p class="whitespace-pre-wrap">{{ msg.content }}</p>
                                </div>
                            </div>
                        </template>
                        <div v-if="loading" class="flex justify-start">
                            <div class="max-w-[85%] rounded-2xl px-4 py-3 bg-slate-100 text-slate-500 text-sm">
                                <span class="animate-pulse">Réflexion…</span>
                            </div>
                        </div>
                    </div>
                    <div ref="messagesEndRef" />
                </div>
            </div>

            <div class="border-t border-slate-100 bg-white px-4 py-4">
                <div v-if="subscription && subscription.assistant_calls_remaining === 0" class="max-w-3xl mx-auto mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">
                    Quota d'appels atteint ce mois. Passez à un plan supérieur.
                </div>
                <div v-else-if="subscription" class="max-w-3xl mx-auto mb-2 text-xs text-slate-500">
                    {{ subscription.assistant_calls_remaining }} appels restants ce mois
                </div>
                <div v-if="error" class="max-w-3xl mx-auto mb-4 rounded-lg border border-red-100 bg-red-50 px-4 py-2 text-sm text-red-700">
                    {{ error }}
                </div>
                <form @submit.prevent="ask" class="max-w-3xl mx-auto">
                    <div class="flex gap-2 rounded-xl border border-slate-200 bg-white p-2 focus-within:border-teal-500 focus-within:ring-1 focus-within:ring-teal-500">
                        <input
                            v-model="question"
                            type="text"
                            placeholder="Posez une question..."
                            :disabled="loading || (subscription && subscription.assistant_calls_remaining === 0)"
                            class="flex-1 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 bg-transparent outline-none rounded-lg"
                        />
                        <button
                            type="submit"
                            :disabled="loading || !question?.trim() || (subscription && subscription.assistant_calls_remaining === 0)"
                            class="rounded-lg bg-slate-900 text-white px-4 py-2.5 text-sm font-medium hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed shrink-0"
                        >
                            {{ loading ? '…' : 'Envoyer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
