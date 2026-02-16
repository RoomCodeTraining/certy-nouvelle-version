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
    models: Object,
    filters: Object,
});

const breadcrumbs = [
    { label: 'Tableau de bord', href: '/dashboard' },
    { label: 'Référentiel', href: route('referential.models.index') },
    { label: 'Modèles' },
];

const columns = [
    { key: 'created_at', label: 'Date de création', getValue: (row) => formatDate(row.created_at) },
    { key: 'name', label: 'Nom', getValue: (row) => row.name ?? '—' },
    { key: 'slug', label: 'Slug', getValue: (row) => row.slug ?? '—' },
    { key: 'brand', label: 'Marque', getValue: (row) => row.brand?.name ?? '—' },
];

const queryParams = computed(() => ({
    per_page: props.models?.per_page ?? 25,
}));

const { confirm: confirmDialog } = useConfirm();
function destroy(model, name) {
    confirmDialog({
        title: 'Supprimer le modèle',
        message: `Voulez-vous vraiment supprimer le modèle « ${name} » ?`,
        confirmLabel: 'Supprimer',
        variant: 'danger',
    }).then((ok) => {
        if (ok) router.delete(route('referential.models.destroy', model.id));
    });
}
</script>

<template>
    <DashboardLayout>
        <template #header>
            <PageHeader :breadcrumbs="breadcrumbs" title="Modèles">
                <template #actions>
                    <Link
                        :href="route('referential.models.create')"
                        class="inline-flex items-center justify-center px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800"
                    >
                        Nouveau modèle
                    </Link>
                </template>
            </PageHeader>
        </template>

        <ReferentialTabs active="models" />

        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
            <DataTable
                :data="models.data ?? []"
                :columns="columns"
                row-key="id"
                empty-message="Aucun modèle."
            >
                <template #actions="{ row }">
                    <DataTableAction label="Modifier" :to="route('referential.models.edit', row.id)" icon="edit" />
                    <DataTableAction label="Supprimer" variant="danger" icon="trash" @click="destroy(row, row.name)" />
                </template>
            </DataTable>
        </div>

        <Paginator
            v-if="models.data?.length"
            :paginator="models"
            :query-params="queryParams"
            class="mt-4"
        />
    </DashboardLayout>
</template>
