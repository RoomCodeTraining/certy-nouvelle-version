<script setup>
import { ref, computed } from "vue";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { router, usePage } from "@inertiajs/vue3";

const page = usePage();
const subscription = computed(() => page.props.auth?.subscription);

const props = defineProps({
    documents: Array,
});

const fileInput = ref(null);
const importModalOpen = ref(false);
const selectedFile = ref(null);
const uploading = ref(false);

const openImportModal = () => {
    selectedFile.value = null;
    importModalOpen.value = true;
};

const closeImportModal = () => {
    if (!uploading.value) {
        importModalOpen.value = false;
        selectedFile.value = null;
        if (fileInput.value) fileInput.value.value = "";
    }
};

const onFileSelect = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    selectedFile.value = file;
};

const confirmImport = () => {
    if (!selectedFile.value) return;
    uploading.value = true;
    const formData = new FormData();
    formData.append("file", selectedFile.value);
    router.post("/documents", formData, {
        forceFormData: true,
        onFinish: () => {
            uploading.value = false;
            closeImportModal();
        },
    });
};

const remove = (doc) => {
    if (confirm("Supprimer ce document ?")) {
        router.delete(`/documents/${doc.id}`);
    }
};

const formatSize = (bytes) => {
    if (!bytes) return "—";
    if (bytes < 1024) return bytes + " o";
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " Ko";
    return (bytes / (1024 * 1024)).toFixed(1) + " Mo";
};

const formatDate = (dateStr) => {
    if (!dateStr) return "—";
    const d = new Date(dateStr);
    return d.toLocaleDateString("fr-FR", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });
};

const handleClickOutside = (e) => {
    if (e.target === e.currentTarget) closeImportModal();
};

const getFileType = (mime) => {
    if (!mime) return { label: "Fichier", icon: "doc", color: "slate" };
    if (mime.includes("pdf")) return { label: "PDF", icon: "pdf", color: "red" };
    if (mime.includes("word") || mime.includes("document")) return { label: "Word", icon: "doc", color: "blue" };
    if (mime.includes("sheet") || mime.includes("excel")) return { label: "Excel", icon: "sheet", color: "emerald" };
    if (mime.includes("image")) return { label: "Image", icon: "image", color: "violet" };
    return { label: "Fichier", icon: "doc", color: "slate" };
};

const typeColors = {
    red: "bg-red-500/10 text-red-600 border-red-200",
    blue: "bg-blue-500/10 text-blue-600 border-blue-200",
    emerald: "bg-emerald-500/10 text-emerald-600 border-emerald-200",
    violet: "bg-violet-500/10 text-violet-600 border-violet-200",
    slate: "bg-slate-100 text-slate-600 border-slate-200",
};
</script>

<template>
    <DashboardLayout>
        <template #header>
            <h1 class="text-sm font-medium text-slate-900">Documents</h1>
        </template>

        <div class="flex-1 min-h-full flex flex-col w-full">
            <input
                ref="fileInput"
                type="file"
                class="hidden"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.gif"
                @change="onFileSelect"
            />

            <!-- En-tête -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Vos documents</h2>
                    <p class="text-sm text-slate-500 mt-0.5">
                        Importez des PDF, Word, Excel ou images pour les interroger avec l’assistant.
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span
                        v-if="subscription"
                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-600"
                    >
                        {{ (subscription.documents_limit ?? 0) - (subscription.documents_remaining ?? subscription.documents_limit ?? 0) }} / {{ subscription.documents_limit }} documents
                    </span>
                    <button
                        type="button"
                        :disabled="subscription && subscription.documents_remaining === 0"
                        @click="openImportModal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors shadow-sm shadow-emerald-500/20 disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Importer un document
                    </button>
                </div>
            </div>
            <p v-if="subscription && subscription.documents_remaining === 0" class="mb-4 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2.5">
                Quota atteint. <a href="/settings/subscription" class="font-medium underline hover:no-underline">Passez à un plan supérieur</a> pour ajouter des documents.
            </p>

            <!-- Liste documents (cartes) -->
            <div v-if="documents.length > 0" class="flex-1 min-h-0">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="doc in documents"
                        :key="doc.id"
                        class="group flex items-start gap-4 p-4 rounded-xl border border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm transition-all duration-200"
                    >
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 border"
                            :class="typeColors[getFileType(doc.mime_type).color]"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <a
                                :href="`/documents/${doc.id}/download`"
                                target="_blank"
                                rel="noopener"
                                class="block"
                            >
                                <p class="text-sm font-medium text-slate-900 truncate group-hover:text-emerald-600 transition-colors">
                                    {{ doc.title || doc.filename }}
                                </p>
                            </a>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ getFileType(doc.mime_type).label }} · {{ formatSize(doc.size) }} · {{ formatDate(doc.created_at) }}
                            </p>
                            <div class="flex items-center gap-1 mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a
                                    :href="`/documents/${doc.id}/download`"
                                    class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
                                    title="Télécharger"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                </a>
                                <button
                                    type="button"
                                    @click="remove(doc)"
                                    class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Supprimer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div
                v-else
                class="flex-1 min-h-[280px] flex items-center justify-center"
            >
                <div
                    class="text-center max-w-sm mx-auto p-8 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50"
                >
                    <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-200 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-slate-900 mb-1">Aucun document</h3>
                    <p class="text-sm text-slate-500 mb-5">
                        Importez des fichiers pour les retrouver et les interroger avec l’assistant IA.
                    </p>
                    <button
                        type="button"
                        :disabled="subscription && subscription.documents_remaining === 0"
                        @click="openImportModal"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 transition-colors disabled:opacity-50"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Importer mon premier document
                    </button>
                </div>
            </div>
        </div>

        <!-- Import modal -->
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
                    v-show="importModalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/30 backdrop-blur-sm"
                    @click.self="handleClickOutside"
                >
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-show="importModalOpen"
                            class="w-full max-w-md bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden"
                        >
                            <div class="px-6 pt-6 pb-4">
                                <h2 class="text-lg font-semibold text-slate-900">
                                    Importer un document
                                </h2>
                                <p class="text-sm text-slate-500 mt-1">
                                    PDF, Word, Excel ou image — max 10 Mo
                                </p>
                            </div>

                            <div class="px-6 pb-6">
                                <div
                                    v-if="!selectedFile"
                                    class="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center cursor-pointer bg-slate-50/50 hover:border-emerald-300 hover:bg-emerald-50/30 transition-colors"
                                    @click="fileInput?.click()"
                                >
                                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-slate-700">
                                        Cliquez ou glissez un fichier
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1">
                                        .pdf, .doc, .docx, .xls, .xlsx, .png, .jpg
                                    </p>
                                </div>

                                <div v-else class="space-y-4">
                                    <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                                        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-200 flex items-center justify-center shrink-0">
                                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-slate-900 truncate">
                                                {{ selectedFile.name }}
                                            </p>
                                            <p class="text-xs text-slate-500 mt-0.5">
                                                {{ formatSize(selectedFile.size) }}
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            @click="selectedFile = null; fileInput?.click()"
                                            class="text-sm font-medium text-emerald-600 hover:text-emerald-700"
                                        >
                                            Changer
                                        </button>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-slate-100">
                                    <button
                                        type="button"
                                        @click="closeImportModal"
                                        :disabled="uploading"
                                        class="px-4 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-100 transition-colors disabled:opacity-50"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="button"
                                        @click="confirmImport"
                                        :disabled="!selectedFile || uploading"
                                        class="px-4 py-2.5 rounded-xl bg-emerald-500 text-white text-sm font-medium hover:bg-emerald-600 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 shadow-sm shadow-emerald-500/20"
                                    >
                                        <span
                                            v-if="uploading"
                                            class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
                                        />
                                        {{ uploading ? "Import en cours…" : "Importer" }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </DashboardLayout>
</template>
