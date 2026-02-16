<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
    valueKey: { type: String, default: 'id' },
    labelKey: { type: String, default: 'name' },
    placeholder: { type: String, default: '— Choisir —' },
    searchPlaceholder: { type: String, default: 'Rechercher…' },
    required: Boolean,
    disabled: Boolean,
    error: Boolean,
    inputClass: { type: String, default: '' },
    /** If true, allow empty value (placeholder option) */
    allowEmpty: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const searchQuery = ref('');
const containerRef = ref(null);

const normalizedOptions = computed(() => {
    return Array.isArray(props.options) ? props.options : [];
});

const getOptionValue = (opt) => {
    if (opt == null) return '';
    return typeof opt === 'object' ? opt[props.valueKey] : opt;
};

const getOptionLabel = (opt) => {
    if (opt == null) return '';
    if (typeof opt === 'object' && props.labelKey in opt) return String(opt[props.labelKey]);
    return String(opt);
};

const filteredOptions = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return normalizedOptions.value;
    return normalizedOptions.value.filter((opt) =>
        getOptionLabel(opt).toLowerCase().includes(q)
    );
});

const selectedOption = computed(() => {
    if (props.modelValue === '' || props.modelValue == null) return null;
    return normalizedOptions.value.find((opt) => String(getOptionValue(opt)) === String(props.modelValue)) ?? null;
});

const displayLabel = computed(() => {
    if (selectedOption.value) return getOptionLabel(selectedOption.value);
    return props.placeholder;
});

function select(opt) {
    const val = opt == null ? '' : getOptionValue(opt);
    emit('update:modelValue', val);
    emit('change', val);
    isOpen.value = false;
    searchQuery.value = '';
}

function handleClickOutside(e) {
    if (containerRef.value && !containerRef.value.contains(e.target)) {
        isOpen.value = false;
    }
}

function onTriggerKeydown(e) {
    if (e.key === 'Escape') {
        isOpen.value = false;
    }
    if (e.key === 'ArrowDown' && !isOpen.value) {
        isOpen.value = true;
        e.preventDefault();
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

watch(isOpen, (open) => {
    if (open) searchQuery.value = '';
});
</script>

<template>
    <div ref="containerRef" class="relative w-full">
        <button
            type="button"
            :disabled="disabled"
            :class="[
                'w-full rounded-lg border px-3 py-2 text-left text-sm focus:ring-1 focus:outline-none flex items-center justify-between gap-2',
                error ? 'border-red-400 focus:ring-red-400 focus:border-red-400' : 'border-slate-200 focus:border-slate-400 focus:ring-slate-400',
                inputClass,
            ]"
            @click="isOpen = !isOpen"
            @keydown="onTriggerKeydown"
        >
            <span class="truncate" :class="!selectedOption && placeholder ? 'text-slate-400' : ''">
                {{ displayLabel }}
            </span>
            <svg class="w-4 h-4 shrink-0 text-slate-400" :class="isOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div
            v-show="isOpen"
            class="absolute z-50 mt-1 w-full rounded-lg border border-slate-200 bg-white py-1 min-w-[12rem] max-h-72 flex flex-col"
        >
            <div class="p-2 border-b border-slate-100 sticky top-0 bg-white">
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="searchPlaceholder"
                    class="w-full rounded border border-slate-200 px-2.5 py-1.5 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
                    @click.stop
                />
            </div>
            <div class="overflow-y-auto py-1">
                <button
                    v-if="allowEmpty"
                    type="button"
                    class="w-full px-3 py-2 text-left text-sm hover:bg-slate-50"
                    :class="(modelValue === '' || modelValue == null) ? 'bg-slate-100 text-slate-900 font-medium' : 'text-slate-600'"
                    @click="select(null)"
                >
                    {{ placeholder }}
                </button>
                <button
                    v-for="opt in filteredOptions"
                    :key="getOptionValue(opt)"
                    type="button"
                    class="w-full px-3 py-2 text-left text-sm hover:bg-slate-50 truncate"
                    :class="String(getOptionValue(opt)) === String(modelValue) ? 'bg-slate-100 text-slate-900 font-medium' : 'text-slate-700'"
                    @click="select(opt)"
                >
                    {{ getOptionLabel(opt) }}
                </button>
                <p v-if="filteredOptions.length === 0 && normalizedOptions.length > 0" class="px-3 py-2 text-sm text-slate-500">
                    Aucun résultat
                </p>
            </div>
        </div>
    </div>
</template>
