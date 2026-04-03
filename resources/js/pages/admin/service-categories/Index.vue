<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PencilLine, Plus, Trash2 } from 'lucide-vue-next';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Kategori Layanan', href: '/service-categories' },
        ],
    },
});

defineProps<{
    categories: Array<{
        id: number;
        name: string;
        description: string | null;
        is_active: boolean;
        services_count: number;
    }>;
}>();

function confirmDelete(id: number, name: string) {
    if (confirm(`Hapus kategori "${name}"?`)) {
        router.delete(`/service-categories/${id}`);
    }
}
</script>

<template>
    <Head title="Kategori Layanan" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Kategori Layanan</h1>
                <p class="text-sm text-muted-foreground">Kelola kategori layanan wisata</p>
            </div>
            <Link
                href="/service-categories/create"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
            >
                <Plus class="h-4 w-4" />
                Tambah Kategori
            </Link>
        </div>

        <div class="rounded-xl border border-border overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Nama</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Deskripsi</th>
                        <th class="px-4 py-3 text-center font-medium text-muted-foreground">Layanan</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="categories.length === 0" class="border-t border-border">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada kategori.
                        </td>
                    </tr>
                    <tr
                        v-for="cat in categories"
                        :key="cat.id"
                        class="border-t border-border hover:bg-muted/30 transition-colors"
                    >
                        <td class="px-4 py-3 font-medium">{{ cat.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ cat.description ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">{{ cat.services_count }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="cat.is_active
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                            >
                                {{ cat.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Link
                                    :href="`/service-categories/${cat.id}/edit`"
                                    class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium border border-border hover:bg-muted transition-colors"
                                >
                                    <PencilLine class="h-3.5 w-3.5" />
                                    Edit
                                </Link>
                                <button
                                    class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium border border-destructive/30 text-destructive hover:bg-destructive/10 transition-colors"
                                    @click="confirmDelete(cat.id, cat.name)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
