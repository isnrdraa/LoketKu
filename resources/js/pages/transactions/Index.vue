<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Plus, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import type { Auth } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Riwayat Transaksi', href: '/transactions' },
        ],
    },
});

type TransactionRow = {
    id: number;
    transaction_code: string;
    transaction_date: string;
    cashier_name: string;
    payment_method: 'cash' | 'qris';
    grand_total: number;
    items_count: number;
};

type PaginatedTransactions = {
    data: TransactionRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

const props = defineProps<{
    transactions: PaginatedTransactions;
    cashiers: { id: number; name: string }[];
    filters: {
        from?: string;
        to?: string;
        method?: string;
        cashier_id?: string;
    };
}>();

const page = usePage<{ auth: Auth }>();
const isAdmin = computed(() => page.props.auth.user.role === 'admin');

// Filter state
const filterFrom      = ref(props.filters.from ?? '');
const filterTo        = ref(props.filters.to ?? '');
const filterMethod    = ref(props.filters.method ?? '');
const filterCashierId = ref(props.filters.cashier_id ?? '');

const hasFilter = computed(() =>
    filterFrom.value || filterTo.value || filterMethod.value || filterCashierId.value
);

function applyFilter() {
    router.get('/transactions', {
        from:       filterFrom.value || undefined,
        to:         filterTo.value || undefined,
        method:     filterMethod.value || undefined,
        cashier_id: filterCashierId.value || undefined,
    }, { preserveScroll: true, replace: true });
}

function resetFilter() {
    filterFrom.value      = '';
    filterTo.value        = '';
    filterMethod.value    = '';
    filterCashierId.value = '';
    router.get('/transactions', {}, { preserveScroll: true, replace: true });
}

function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}
</script>

<template>
    <Head title="Riwayat Transaksi" />

    <div class="flex flex-col gap-4 p-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold">Riwayat Transaksi</h1>
                <p class="text-sm text-muted-foreground">Total: {{ transactions.total }} transaksi</p>
            </div>
            <Button as-child>
                <Link href="/transactions/create">
                    <Plus class="mr-2 h-4 w-4" />
                    Transaksi Baru
                </Link>
            </Button>
        </div>

        <!-- Filter -->
        <div class="flex flex-wrap items-end gap-3 rounded-xl border border-border bg-muted/30 p-3">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-muted-foreground">Dari</label>
                <input
                    v-model="filterFrom"
                    type="date"
                    class="h-8 rounded-md border border-input bg-background px-2 py-1 text-xs shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                />
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-muted-foreground">Sampai</label>
                <input
                    v-model="filterTo"
                    type="date"
                    class="h-8 rounded-md border border-input bg-background px-2 py-1 text-xs shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                />
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-muted-foreground">Metode</label>
                <select
                    v-model="filterMethod"
                    class="h-8 rounded-md border border-input bg-background px-2 py-1 text-xs shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                >
                    <option value="">Semua</option>
                    <option value="cash">Cash</option>
                    <option value="qris">QRIS</option>
                </select>
            </div>
            <div v-if="isAdmin" class="flex flex-col gap-1">
                <label class="text-xs font-medium text-muted-foreground">Kasir</label>
                <select
                    v-model="filterCashierId"
                    class="h-8 rounded-md border border-input bg-background px-2 py-1 text-xs shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                >
                    <option value="">Semua</option>
                    <option v-for="c in cashiers" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button
                    class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                    @click="applyFilter"
                >
                    <Search class="h-3.5 w-3.5" />
                    Cari
                </button>
                <button
                    v-if="hasFilter"
                    class="inline-flex items-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-xs font-medium hover:bg-muted transition-colors"
                    @click="resetFilter"
                >
                    <X class="h-3.5 w-3.5" />
                    Reset
                </button>
            </div>
        </div>

        <!-- Tabel -->
        <div class="rounded-xl border border-border overflow-hidden">
            <div v-if="transactions.data.length === 0" class="py-12 text-center text-muted-foreground">
                Tidak ada transaksi ditemukan.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Kode</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Tanggal</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground hidden md:table-cell">Kasir</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground hidden sm:table-cell">Bayar</th>
                            <th class="px-4 py-3 text-right font-medium text-muted-foreground">Total</th>
                            <th class="px-4 py-3 text-center font-medium text-muted-foreground">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="trx in transactions.data"
                            :key="trx.id"
                            class="border-t border-border hover:bg-muted/30 transition-colors"
                        >
                            <td class="px-4 py-3 font-mono text-xs">{{ trx.transaction_code }}</td>
                            <td class="px-4 py-3">{{ trx.transaction_date }}</td>
                            <td class="px-4 py-3 hidden md:table-cell text-muted-foreground">{{ trx.cashier_name }}</td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                <Badge :variant="trx.payment_method === 'qris' ? 'secondary' : 'outline'">
                                    {{ trx.payment_method.toUpperCase() }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold">{{ formatRupiah(trx.grand_total) }}</td>
                            <td class="px-4 py-3 text-center">
                                <Button variant="ghost" size="sm" as-child>
                                    <Link :href="`/transactions/${trx.id}`">
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="transactions.last_page > 1" class="flex flex-wrap justify-center gap-1 border-t border-border p-3">
                <template v-for="link in transactions.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm transition-colors"
                        :class="link.active ? 'border-primary bg-primary text-primary-foreground' : 'hover:bg-muted'"
                    >
                        <span v-html="link.label" />
                    </Link>
                    <span
                        v-else
                        class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm text-muted-foreground opacity-50"
                    >
                        <span v-html="link.label" />
                    </span>
                </template>
            </div>
        </div>
    </div>
</template>
