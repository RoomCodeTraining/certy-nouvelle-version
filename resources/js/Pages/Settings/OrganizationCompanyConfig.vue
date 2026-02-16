<script setup>
import { router, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { route } from '@/route';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const csrfToken = computed(() => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '');

const props = defineProps({
    items: Array,
    rattachements: { type: Array, default: () => [] },
    canEdit: Boolean,
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Paramètres', href: '/settings/profile' },
    { label: 'Config courtier' },
];

const inputClass = 'w-full rounded-lg border border-slate-200 px-2.5 py-2 text-slate-900 placeholder-slate-400 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none';

function mapItemsToRows(items) {
    return (items || []).map((i) => ({
        id: i.id,
        _name: i.name ?? '',
        _code: i.code ?? '',
        _commission: i.commission != null ? i.commission : '',
        _policy_number_identifier: i.policy_number_identifier ?? '',
    }));
}

const rows = ref(mapItemsToRows(props.items));

watch(() => props.items, (newItems) => {
    rows.value = mapItemsToRows(newItems);
}, { deep: true });

const savingRowId = ref(null);
const deletingRowId = ref(null);
const modalOpen = ref(false);
const deleteModalOpen = ref(false);
const rowToDelete = ref(null);
const modalForm = ref({
    _name: '',
    _code: '',
    _commission: '',
    _policy_number_identifier: '',
});

function submitRow(row, index) {
    savingRowId.value = row.id ?? `new-${index}`;
    const formData = new FormData();
    if (row.id) formData.append('id', String(row.id));
    formData.append('name', row._name ?? '');
    formData.append('code', row._code ?? '');
    formData.append('commission', row._commission === '' ? '' : String(row._commission));
    formData.append('policy_number_identifier', row._policy_number_identifier ?? '');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) formData.append('_token', token);
    router.post(route('settings.config.update'), formData, {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => { savingRowId.value = null; },
    });
}

function openAddModal() {
    modalForm.value = { _name: '', _code: '', _commission: '', _policy_number_identifier: '' };
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
}

function onSelectRattachement(ratt) {
    if (!ratt) return;
    modalForm.value._name = ratt.owner_name ?? '';
    modalForm.value._code = ratt.owner_code ?? '';
}

function rowKey(row, index) {
    return row.id ?? `new-${index}`;
}

function openDeleteModal(row) {
    if (!row?.id) return;
    rowToDelete.value = row;
    deleteModalOpen.value = true;
}

function closeDeleteModal() {
    deleteModalOpen.value = false;
    rowToDelete.value = null;
}

function deleteModalLabel() {
    const row = rowToDelete.value;
    if (!row) return 'cette config';
    return [row._name || row._code || 'cette config'].filter(Boolean).join(' ') || 'cette config';
}

function executeDelete() {
    const row = rowToDelete.value;
    if (!row?.id) return;
    deletingRowId.value = row.id;
    closeDeleteModal();
    router.delete(route('settings.config.destroy', row.id), {
        preserveScroll: true,
        onFinish: () => { deletingRowId.value = null; },
    });
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Config courtier" />
        </template>

        <div class="min-h-[60vh] flex flex-col">
            <p class="text-slate-600 text-sm mb-4">
                Config interne : name, code, commission, identifiant numéro de police.
            </p>
            <p v-if="!canEdit" class="text-amber-700 text-sm mb-4 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                Seul l'admin principal peut modifier ces informations.
            </p>

            <p v-if="flashSuccess" class="mb-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                {{ flashSuccess }}
            </p>
            <p v-if="flashError" class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                {{ flashError }}
            </p>

            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                                Name
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                                Code
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                                Commission (%)
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">
                                Identifiant n° police
                            </th>
                            <th v-if="canEdit" scope="col" class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase tracking-wider">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <tr v-for="(row, index) in rows" :key="rowKey(row, index)" class="hover:bg-slate-50/50">
                            <td class="px-4 py-3">
                                <template v-if="canEdit">
                                    <input
                                        v-model="row._name"
                                        type="text"
                                        :class="[inputClass, 'max-w-48']"
                                        placeholder="—"
                                    />
                                </template>
                                <span v-else class="text-sm text-slate-700">{{ row._name || '—' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <template v-if="canEdit">
                                    <input
                                        v-model="row._code"
                                        type="text"
                                        :class="[inputClass, 'max-w-40']"
                                        placeholder="—"
                                    />
                                </template>
                                <span v-else class="text-sm text-slate-700">{{ row._code || '—' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <template v-if="canEdit">
                                    <input
                                        v-model="row._commission"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        :class="[inputClass, 'max-w-24']"
                                        placeholder="—"
                                    />
                                </template>
                                <span v-else class="text-sm text-slate-700">{{ row._commission != null && row._commission !== '' ? row._commission : '—' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <template v-if="canEdit">
                                    <input
                                        v-model="row._policy_number_identifier"
                                        type="text"
                                        :class="[inputClass, 'max-w-48']"
                                        placeholder="—"
                                    />
                                </template>
                                <span v-else class="text-sm text-slate-700">{{ row._policy_number_identifier || '—' }}</span>
                            </td>
                            <td v-if="canEdit" class="px-4 py-3 text-right whitespace-nowrap">
                                <button
                                    type="button"
                                    @click="submitRow(row, index)"
                                    :disabled="savingRowId !== null || deletingRowId !== null"
                                    class="text-sm font-medium text-slate-700 hover:text-slate-900 px-2 py-1 rounded hover:bg-slate-100 disabled:opacity-50"
                                >
                                    {{ savingRowId === (row.id ?? `new-${index}`) ? 'Enregistrement…' : 'Enregistrer' }}
                                </button>
                                <button
                                    v-if="row.id"
                                    type="button"
                                    @click="openDeleteModal(row)"
                                    :disabled="savingRowId !== null || deletingRowId !== null"
                                    class="ml-2 text-sm font-medium text-red-600 hover:text-red-800 px-2 py-1 rounded hover:bg-red-50 disabled:opacity-50"
                                >
                                    {{ deletingRowId === row.id ? 'Suppression…' : 'Supprimer' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="canEdit" class="mt-4">
                <button
                    type="button"
                    @click="openAddModal"
                    class="inline-flex items-center px-3 py-2 rounded-lg border border-dashed border-slate-300 text-slate-600 text-sm font-medium hover:bg-slate-50"
                >
                    + Ajouter une ligne
                </button>
            </div>

            <!-- Modal Ajouter une ligne -->
            <Teleport to="body">
                <div
                    v-show="modalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="modal-title"
                    @click.self="closeModal"
                >
                    <div class="w-full max-w-md rounded-xl bg-white shadow-xl" @click.stop>
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h2 id="modal-title" class="text-lg font-semibold text-slate-900">Ajouter une ligne</h2>
                        </div>
                        <form
                            id="modal-config-form"
                            class="contents"
                            method="post"
                            :action="route('settings.config.update')"
                        >
                            <input type="hidden" name="_token" :value="csrfToken">
                            <div class="px-6 py-4 space-y-4">
                                <div v-if="rattachements.length">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Choisir un rattachement</label>
                                    <select
                                        :class="inputClass"
                                        @change="onSelectRattachement(rattachements.find(r => String(r.id) === $event.target.value) ?? null)"
                                    >
                                        <option value="">— Sélectionner —</option>
                                        <option
                                            v-for="r in rattachements"
                                            :key="r.id"
                                            :value="r.id"
                                        >
                                            {{ r.owner_name || '—' }}{{ r.owner_code ? ` (${r.owner_code})` : '' }}
                                        </option>
                                    </select>
                                    <p class="mt-1 text-xs text-slate-500">La sélection pré-remplit Name et Code.</p>
                                </div>
                                <p v-else class="text-sm text-slate-500">Aucun rattachement disponible (connectez-vous au service ASACI pour en charger).</p>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                                    <input
                                        v-model="modalForm._name"
                                        type="text"
                                        name="name"
                                        :class="inputClass"
                                        placeholder="—"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Code</label>
                                    <input
                                        v-model="modalForm._code"
                                        type="text"
                                        name="code"
                                        :class="inputClass"
                                        placeholder="—"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Commission (%)</label>
                                    <input
                                        v-model="modalForm._commission"
                                        type="number"
                                        name="commission"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        :class="inputClass"
                                        placeholder="—"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Identifiant n° police</label>
                                    <input
                                        v-model="modalForm._policy_number_identifier"
                                        type="text"
                                        name="policy_number_identifier"
                                        :class="inputClass"
                                        placeholder="—"
                                    />
                                </div>
                            </div>
                            <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="px-3 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    class="px-3 py-2 rounded-lg bg-slate-900 text-white text-sm font-medium hover:bg-slate-800"
                                >
                                    Enregistrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>

            <!-- Modal Confirmation suppression -->
            <Teleport to="body">
                <div
                    v-show="deleteModalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="delete-modal-title"
                    @click.self="closeDeleteModal"
                >
                    <div class="w-full max-w-md rounded-xl bg-white shadow-xl" @click.stop>
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h2 id="delete-modal-title" class="text-lg font-semibold text-slate-900">Supprimer la config</h2>
                        </div>
                        <div class="px-6 py-4">
                            <p class="text-slate-600 text-sm">
                                Êtes-vous sûr de vouloir supprimer la config « <strong>{{ deleteModalLabel() }}</strong> » ?
                            </p>
                        </div>
                        <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2">
                            <button
                                type="button"
                                @click="closeDeleteModal"
                                class="px-3 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50"
                            >
                                Annuler
                            </button>
                            <button
                                type="button"
                                @click="executeDelete"
                                class="px-3 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700"
                            >
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <div class="mt-4">
                <Link
                    href="/dashboard"
                    class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition-colors"
                >
                    Retour
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>
