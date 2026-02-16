<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Sélectionner une date' },
    error: Boolean,
    inputClass: { type: String, default: '' },
    /** Min date YYYY-MM-DD */
    min: { type: String, default: '' },
    /** Max date YYYY-MM-DD */
    max: { type: String, default: '' },
    /** Year range for dropdown [start, end] e.g. [1990, 2030] */
    yearRange: { type: Array, default: () => [1950, 2030] },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const containerRef = ref(null);

const displayValue = computed(() => {
    const v = props.modelValue;
    if (!v || typeof v !== 'string') return '';
    const d = parseDate(v);
    if (!d) return v;
    return formatDisplay(d);
});

function parseDate(str) {
    if (!str || str.length < 10) return null;
    const [y, m, day] = str.slice(0, 10).split('-').map(Number);
    if (!y || !m || !day) return null;
    const date = new Date(y, m - 1, day);
    if (isNaN(date.getTime())) return null;
    return date;
}

function formatDisplay(d) {
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
}

function toYMD(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

const years = computed(() => {
    const [start, end] = props.yearRange;
    const arr = [];
    for (let y = start; y <= end; y++) arr.push(y);
    return arr;
});

const currentView = ref(null);
const viewDate = computed({
    get() {
        if (currentView.value) return currentView.value;
        const d = parseDate(props.modelValue);
        return d || new Date();
    },
    set(v) {
        currentView.value = v;
    },
});

const viewYear = computed({
    get: () => viewDate.value.getFullYear(),
    set(y) {
        const d = new Date(viewDate.value);
        d.setFullYear(y);
        currentView.value = d;
    },
});

const viewMonth = computed({
    get: () => viewDate.value.getMonth(),
    set(m) {
        const d = new Date(viewDate.value);
        d.setMonth(m);
        currentView.value = d;
    },
});

const monthLabel = computed(() =>
    viewDate.value.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
);

const monthNames = computed(() => {
    const names = [];
    for (let i = 0; i < 12; i++) {
        names.push(new Date(2000, i, 1).toLocaleDateString('fr-FR', { month: 'long' }));
    }
    return names;
});

const daysInView = computed(() => {
    const y = viewYear.value;
    const m = viewMonth.value;
    const first = new Date(y, m, 1);
    const last = new Date(y, m + 1, 0);
    const startPad = (first.getDay() + 6) % 7;
    const days = [];
    for (let i = 0; i < startPad; i++) days.push({ day: null, date: null });
    for (let d = 1; d <= last.getDate(); d++) {
        const date = new Date(y, m, d);
        days.push({ day: d, date, ymd: toYMD(date) });
    }
    return days;
});

const minYMD = computed(() => props.min && props.min.length >= 10 ? props.min.slice(0, 10) : null);
const maxYMD = computed(() => props.max && props.max.length >= 10 ? props.max.slice(0, 10) : null);

function isDisabled(ymd) {
    if (minYMD.value && ymd < minYMD.value) return true;
    if (maxYMD.value && ymd > maxYMD.value) return true;
    return false;
}

function selectDate(ymd) {
    if (isDisabled(ymd)) return;
    emit('update:modelValue', ymd);
    isOpen.value = false;
}

function isSelected(ymd) {
    return props.modelValue && props.modelValue.slice(0, 10) === ymd;
}

function open() {
    const d = parseDate(props.modelValue);
    currentView.value = d ? new Date(d) : new Date();
    isOpen.value = true;
}

function handleClickOutside(e) {
    if (containerRef.value && !containerRef.value.contains(e.target)) {
        isOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

watch(() => props.modelValue, (val) => {
    if (!val) currentView.value = null;
});
</script>

<template>
    <div ref="containerRef" class="relative inline-block w-full">
        <button
            type="button"
            :class="[inputClass, error && 'border-red-400 focus:border-red-400 focus:ring-red-400', 'w-full text-left']"
            @click="open"
        >
            <span :class="displayValue ? 'text-slate-900' : 'text-slate-400'">
                {{ displayValue || placeholder }}
            </span>
        </button>

        <div
            v-show="isOpen"
            class="absolute left-0 top-full z-50 mt-1 min-w-[280px] rounded-xl border border-slate-200 bg-white p-4 shadow-lg"
        >
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <select
                        v-model.number="viewYear"
                        class="rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none"
                        aria-label="Année"
                    >
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <select
                        v-model.number="viewMonth"
                        class="rounded-lg border border-slate-200 px-2 py-1.5 text-sm focus:border-slate-400 focus:ring-1 focus:ring-slate-400 focus:outline-none min-w-[140px]"
                        aria-label="Mois"
                    >
                        <option v-for="(name, i) in monthNames" :key="i" :value="i">{{ name }}</option>
                    </select>
                </div>
                <div class="grid grid-cols-7 gap-0.5 text-center text-sm">
                    <div v-for="w in ['L', 'M', 'M', 'J', 'V', 'S', 'D']" :key="w" class="py-1 text-slate-500 font-medium">
                        {{ w }}
                    </div>
                    <template v-for="(cell, idx) in daysInView" :key="idx">
                        <button
                            v-if="cell.date"
                            type="button"
                            :disabled="isDisabled(cell.ymd)"
                            :class="[
                                'py-1.5 rounded transition',
                                isSelected(cell.ymd)
                                    ? 'bg-slate-900 text-white font-medium'
                                    : isDisabled(cell.ymd)
                                      ? 'text-slate-300 cursor-not-allowed'
                                      : 'hover:bg-slate-100 text-slate-700',
                            ]"
                            @click="selectDate(cell.ymd)"
                        >
                            {{ cell.day }}
                        </button>
                        <div v-else class="py-1.5" />
                    </template>
                </div>
        </div>
    </div>
</template>

<script>
// Position popover under trigger (computed in mounted when open)
export default {
    inheritAttrs: false,
};
</script>
