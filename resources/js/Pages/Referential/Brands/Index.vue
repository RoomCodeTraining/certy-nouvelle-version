<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/DataTable.vue';
import DataTableAction from '@/Components/DataTableAction.vue';
import ReferentialTabs from '@/Components/ReferentialTabs.vue';
import Paginator from '@/Components/Paginator.vue';
import { route } from '@/route';
import { formatDate } from '@/utils/formatDate';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    brands: Object,
    filters: Object,
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Référentiel', href: route('referential.brands.index') },
    { label: 'Marques' },
];

const columns = [
    { key: 'created_at', label: 'Date de création', getValue: (row) => formatDate(row.created_at) },
    { key: 'name', label: 'Nom', getValue: (row) => row.name ?? '—' },
    { key: 'slug', label: 'Slug', getValue: (row) => row.slug ?? '—' },
];

const queryParams = computed(() => ({
    per_page: props.brands?.per_page ?? 25,
}));

const { confirm: confirmDialog } = useConfirm();
function destroy(brand, name) {
    confirmDialog({
        title: 'Supprimer la marque',
        message: `Supprimer la marque « ${name} » ? Les modèles associés seront également supprimés.`,
        confirmLabel: 'Supprimer',
        variant: 'danger',
    }).then((ok) => {
        if (ok) router.delete(route('referential.brands.destroy', brand.id));
    });
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Marques">
                <template #actions>
                    <Link
                        :href="route('referential.brands.create')"
                        class="inline-flex items-center justify-center px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800"
                    >
                        Nouvelle marque
                    </Link>
                </template>
            </PageHeader>
        </template>

        <ReferentialTabs active="brands" />

        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <DataTable
                :data="brands.data ?? []"
                :columns="columns"
                row-key="id"
                empty-message="Aucune marque."
            >
                <template #actions="{ row }">
                    <DataTableAction label="Modifier" :to="route('referential.brands.edit', row.id)" icon="edit" />
                    <DataTableAction label="Supprimer" variant="danger" icon="trash" @click="destroy(row, row.name)" />
                </template>
            </DataTable>
        </div>

        <Paginator
            v-if="brands.data?.length"
            :paginator="brands"
            :query-params="queryParams"
            class="mt-4"
        />
    </DashboardLayout>
</template>
