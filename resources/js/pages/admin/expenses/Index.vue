<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PencilLine, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Pengeluaran', href: '/expenses' },
        ],
    },
});

const props = defineProps<{
    expenses: Array<{
        id: number;
        expense_date: string;
        category: string;
        description: string | null;
        amount: number;
        creator: { name: string } | null;
    }>;
    categories: string[];
    filters: { date?: string };
}>();

const filterDate = ref(props.filters.date ?? '');

function applyFilter() {
    router.get('/expenses', { date: filterDate.value }, { preserveScroll: true, replace: true });
}

function resetFilter() {
    filterDate.value = '';
    router.get('/expenses', {}, { preserveScroll: true, replace: true });
}

const totalAmount = () =>
    props.expenses.reduce((sum, e) => sum + Number(e.amount), 0);

function formatRupiah(amount: number) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(amount);
}

function confirmDelete(id: number) {
    if (confirm('Hapus catatan pengeluaran ini?')) {
        router.delete(`/expenses/${id}`);
    }
}
</script>

<template>
    <Head title="Pengeluaran" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Pengeluaran</h1>
                <p class="text-sm text-muted-foreground">Catat dan kelola pengeluaran operasional</p>
            </div>
            <Link
                href="/expenses/create"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
            >
                <Plus class="h-4 w-4" />
                Catat Pengeluaran
            </Link>
        </div>

        <!-- Filter -->
        <div class="flex items-center gap-3">
            <input
                v-model="filterDate"
                type="date"
                class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                @change="applyFilter"
            />
            <button
                v-if="filterDate"
                class="text-xs text-muted-foreground hover:text-foreground underline"
                @click="resetFilter"
            >
                Reset
            </button>
        </div>

        <!-- Summary -->
        <div
            v-if="expenses.length > 0"
            class="flex items-center justify-between rounded-lg bg-muted/50 px-4 py-2 text-sm"
        >
            <span class="text-muted-foreground">Total: <strong>{{ expenses.length }}</strong> transaksi</span>
            <span class="font-semibold text-destructive">{{ formatRupiah(totalAmount()) }}</span>
        </div>

        <!-- Table -->
        <div class="rounded-xl border border-border overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Tanggal</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Kategori</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Keterangan</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Jumlah</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Dicatat oleh</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="expenses.length === 0" class="border-t border-border">
                        <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada pengeluaran.
                        </td>
                    </tr>
                    <tr
                        v-for="expense in expenses"
                        :key="expense.id"
                        class="border-t border-border hover:bg-muted/30 transition-colors"
                    >
                        <td class="px-4 py-3 whitespace-nowrap">{{ expense.expense_date }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">
                                {{ expense.category }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">{{ expense.description ?? '-' }}</td>
                        <td class="px-4 py-3 text-right font-mono font-medium text-destructive">{{ formatRupiah(Number(expense.amount)) }}</td>
                        <td class="px-4 py-3 text-muted-foreground text-xs">{{ expense.creator?.name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Link
                                    :href="`/expenses/${expense.id}/edit`"
                                    class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium border border-border hover:bg-muted transition-colors"
                                >
                                    <PencilLine class="h-3.5 w-3.5" />
                                    Edit
                                </Link>
                                <button
                                    class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium border border-destructive/30 text-destructive hover:bg-destructive/10 transition-colors"
                                    @click="confirmDelete(expense.id)"
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
