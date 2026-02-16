<script setup>
import { Link } from '@inertiajs/vue3';
import { route } from '@/route';

defineProps({
    active: { type: String, required: true }, // 'brands' | 'models'
});
</script>

<template>
    <nav class="flex gap-1 border-b border-slate-200 mb-6" aria-label="Onglets référentiel">
        <Link
            :href="route('referential.brands.index')"
            class="px-4 py-2.5 text-sm font-medium rounded-t-lg border-b-2 -mb-px transition-colors"
            :class="active === 'brands' ? 'border-slate-900 text-slate-900 bg-white' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
        >
            Marques
        </Link>
        <Link
            :href="route('referential.models.index')"
            class="px-4 py-2.5 text-sm font-medium rounded-t-lg border-b-2 -mb-px transition-colors"
            :class="active === 'models' ? 'border-slate-900 text-slate-900 bg-white' : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
        >
            Modèles
        </Link>
    </nav>
</template>
