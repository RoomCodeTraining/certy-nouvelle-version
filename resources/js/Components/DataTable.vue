<script setup>
import { Link } from '@inertiajs/vue3';

/**
 * data: Array - rows
 * columns: Array<{ key, label, type?, getValue?, href?, class?, cellClass? }>
 * rowKey: string | (row)=>string - default 'id'
 * emptyMessage: string
 * Slots: toolbar-left, toolbar-right, actions { row }, empty
 */
const props = defineProps({
    data: {
        type: Array,
        default: () => [],
    },
    columns: {
        type: Array,
        required: true,
    },
    sortKey: { type: String, default: null },
    sortOrder: { type: String, default: 'desc' },
    sortBaseUrl: { type: String, default: '' },
    sortQueryParams: { type: Object, default: () => ({}) },
    rowKey: {
        type: [String, Function],
        default: 'id',
    },
    emptyMessage: {
        type: String,
        default: 'Aucune donnée.',
    },
});

function getRowKey(row) {
    if (typeof props.rowKey === 'function') return props.rowKey(row);
    return row[props.rowKey] ?? row.id ?? Math.random();
}

function getCellValue(row, col) {
    if (col.getValue && typeof col.getValue === 'function') return col.getValue(row);
    const val = row[col.key];
    return val != null ? val : '—';
}

function getBadgeClass(row, col) {
    if (col.getBadgeClass && typeof col.getBadgeClass === 'function') return col.getBadgeClass(row);
    return col.badgeClass ?? 'bg-slate-100 text-slate-800';
}

function getImageAlt(row, col) {
    if (col.getAlt && typeof col.getAlt === 'function') return col.getAlt(row);
    return '';
}

function buildSortUrl(col) {
    if (!props.sortBaseUrl || !col.sortKey) return null;
    const nextOrder = (props.sortKey === col.sortKey && props.sortOrder === 'desc') ? 'asc' : 'desc';
    const params = { ...props.sortQueryParams, sort: col.sortKey, order: nextOrder };
    const qs = new URLSearchParams();
    Object.entries(params).forEach(([k, v]) => { if (v != null && v !== '') qs.set(k, String(v)); });
    return `${props.sortBaseUrl}?${qs.toString()}`;
}

function isSorted(col) {
    return props.sortKey && col.sortKey === props.sortKey;
}
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white text-sm overflow-hidden">
        <!-- Toolbar (Nuxt UI style): optionnel -->
        <div
            v-if="$slots['toolbar-left'] || $slots['toolbar-right']"
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 border-b border-slate-200 bg-slate-50/80"
        >
            <div class="flex items-center gap-3 min-w-0">
                <slot name="toolbar-left" />
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <slot name="toolbar-right" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full caption-bottom">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/50">
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            scope="col"
                            class="h-11 px-4 text-left align-middle font-medium text-slate-600"
                            :class="[col.class ?? '', col.sortKey && sortBaseUrl ? 'cursor-pointer select-none hover:text-slate-900 hover:bg-slate-100/80 rounded' : '']"
                        >
                            <Link
                                v-if="col.sortKey && sortBaseUrl"
                                :href="buildSortUrl(col)"
                                class="flex items-center gap-1"
                                preserve-scroll
                            >
                                {{ col.label }}
                                <span v-if="isSorted(col)" class="text-slate-400" aria-hidden="true">
                                    {{ sortOrder === 'asc' ? '↑' : '↓' }}
                                </span>
                            </Link>
                            <span v-else>{{ col.label }}</span>
                        </th>
                        <th v-if="$slots.actions" scope="col" class="h-11 w-[1%] min-w-[7rem] px-4 text-right align-middle font-medium text-slate-600 whitespace-nowrap">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80">
                    <tr
                        v-for="row in data"
                        :key="getRowKey(row)"
                        class="border-b border-slate-200/80 transition-colors hover:bg-slate-50/50"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            class="p-4 align-middle"
                            :class="col.cellClass ?? ''"
                        >
                            <template v-if="col.type === 'link' && col.href && col.href(row)">
                                <Link
                                    :href="col.href(row)"
                                    class="font-medium text-slate-900 hover:underline underline-offset-4"
                                >
                                    {{ getCellValue(row, col) }}
                                </Link>
                            </template>
                            <template v-else-if="col.type === 'badge'">
                                <span
                                    class="inline-flex items-center shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap"
                                    :class="getBadgeClass(row, col)"
                                >
                                    {{ getCellValue(row, col) }}
                                </span>
                            </template>
                            <template v-else-if="col.type === 'image'">
                                <img
                                    v-if="getCellValue(row, col)"
                                    :src="getCellValue(row, col)"
                                    :alt="getImageAlt(row, col)"
                                    class="h-8 w-8 rounded object-contain bg-slate-50 border border-slate-100"
                                />
                                <span v-else class="text-slate-400">—</span>
                            </template>
                            <template v-else>
                                <span class="text-slate-700">{{ getCellValue(row, col) }}</span>
                            </template>
                        </td>
                        <td v-if="$slots.actions" class="p-4 align-middle text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-0.5">
                                <slot name="actions" :row="row" />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="!data.length"
            class="flex flex-col items-center justify-center px-4 py-12 text-center text-sm text-slate-500"
        >
            <slot name="empty">
                {{ emptyMessage }}
            </slot>
        </div>
    </div>
</template>
