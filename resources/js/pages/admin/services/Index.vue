<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PencilLine, Plus, Trash2 } from 'lucide-vue-next';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Layanan', href: '/services' },
        ],
    },
});

defineProps<{
    services: Array<{
        id: number;
        name: string;
        price: number;
        is_active: boolean;
        category: { id: number; name: string } | null;
    }>;
}>();

function formatRupiah(amount: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
}

function confirmDelete(id: number, name: string) {
    if (confirm(`Hapus layanan "${name}"?`)) {
        router.delete(`/services/${id}`);
    }
}
</script>

<template>
    <Head title="Layanan" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Layanan</h1>
                <p class="text-sm text-muted-foreground">Kelola daftar layanan dan harga tiket</p>
            </div>
            <Link
                href="/services/create"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
            >
                <Plus class="h-4 w-4" />
                Tambah Layanan
            </Link>
        </div>

        <div class="rounded-xl border border-border overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Nama Layanan</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Kategori</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Harga</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="services.length === 0" class="border-t border-border">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada layanan.
                        </td>
                    </tr>
                    <tr
                        v-for="service in services"
                        :key="service.id"
                        class="border-t border-border hover:bg-muted/30 transition-colors"
                    >
                        <td class="px-4 py-3 font-medium">{{ service.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ service.category?.name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ formatRupiah(service.price) }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="service.is_active
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                            >
                                {{ service.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Link
                                    :href="`/services/${service.id}/edit`"
                                    class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium border border-border hover:bg-muted transition-colors"
                                >
                                    <PencilLine class="h-3.5 w-3.5" />
                                    Edit
                                </Link>
                                <button
                                    class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium border border-destructive/30 text-destructive hover:bg-destructive/10 transition-colors"
                                    @click="confirmDelete(service.id, service.name)"
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
