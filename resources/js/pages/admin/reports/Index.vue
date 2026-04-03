<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { BarChart3, Receipt, TrendingDown, TrendingUp, Wallet } from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Laporan', href: '/reports' },
        ],
    },
});

type DailyRow = {
    date: string;
    tx_count: number;
    revenue: number;
    cash: number;
    qris: number;
    expenses: number;
    net: number;
};

type ServiceRow = {
    service_name: string;
    total_qty: number;
    total_revenue: number;
};

type ExpenseCategoryRow = {
    category: string;
    total: number;
    count: number;
};

const props = defineProps<{
    summary: {
        total_revenue: number;
        total_transactions: number;
        total_cash: number;
        total_qris: number;
        total_expenses: number;
        net: number;
    };
    daily: DailyRow[];
    top_services: ServiceRow[];
    expense_by_category: ExpenseCategoryRow[];
    filters: { from: string; to: string };
}>();

const filterFrom = ref(props.filters.from);
const filterTo   = ref(props.filters.to);

function applyFilter() {
    router.get('/reports', { from: filterFrom.value, to: filterTo.value }, {
        preserveScroll: true,
        replace: true,
    });
}

function setPreset(preset: 'today' | 'week' | 'month' | 'last_month') {
    const now = new Date();
    let from: Date, to: Date;

    if (preset === 'today') {
        from = to = now;
    } else if (preset === 'week') {
        const day = now.getDay() || 7;
        from = new Date(now); from.setDate(now.getDate() - day + 1);
        to = now;
    } else if (preset === 'month') {
        from = new Date(now.getFullYear(), now.getMonth(), 1);
        to = now;
    } else {
        from = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        to = new Date(now.getFullYear(), now.getMonth(), 0);
    }

    filterFrom.value = from.toISOString().split('T')[0];
    filterTo.value   = to.toISOString().split('T')[0];
    applyFilter();
}

function formatRupiah(v: number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v);
}

const page = usePage<{ store: { name: string; address: string } }>();
const storeName = computed(() => page.props.store?.name ?? 'Laporan Keuangan');
const storeAddress = computed(() => page.props.store?.address ?? '');

function printReport() {
    window.print();
}

function formatDate(d: string) {
    return new Date(d + 'T00:00:00').toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
}
</script>

<template>
    <Head title="Laporan" />

    <div class="flex flex-1 flex-col gap-6 p-4 print-area">

        <!-- Header khusus cetak (tersembunyi di layar) -->
        <div class="print-only hidden border-b border-gray-300 pb-3 mb-2 text-center">
            <h1 class="text-xl font-bold uppercase tracking-wide">{{ storeName }}</h1>
            <p v-if="storeAddress" class="text-xs text-gray-500">{{ storeAddress }}</p>
            <h2 class="text-base font-semibold mt-2">Laporan Keuangan</h2>
            <p class="text-sm text-gray-600">
                Periode: {{ formatDate(filters.from) }} — {{ formatDate(filters.to) }}
            </p>
        </div>

        <!-- Header layar (tersembunyi saat cetak) -->
        <div class="no-print flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">Laporan Keuangan</h1>
                <p class="text-sm text-muted-foreground">Rekap pendapatan, pengeluaran, dan transaksi</p>
            </div>
            <button
                class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors"
                @click="printReport"
            >
                🖨️ Cetak Laporan
            </button>
        </div>

        <!-- Filter -->
        <div class="no-print flex flex-wrap items-end gap-3 rounded-xl border border-border bg-muted/30 p-4">
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-medium text-muted-foreground">Dari Tanggal</label>
                <input
                    v-model="filterFrom"
                    type="date"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                />
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-medium text-muted-foreground">Sampai Tanggal</label>
                <input
                    v-model="filterTo"
                    type="date"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                />
            </div>
            <button
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                @click="applyFilter"
            >
                Tampilkan
            </button>
            <div class="flex gap-2">
                <button
                    v-for="preset in [
                        { key: 'today', label: 'Hari Ini' },
                        { key: 'week',  label: 'Minggu Ini' },
                        { key: 'month', label: 'Bulan Ini' },
                        { key: 'last_month', label: 'Bulan Lalu' },
                    ]"
                    :key="preset.key"
                    class="rounded-md border border-border px-3 py-1.5 text-xs font-medium hover:bg-muted transition-colors"
                    @click="setPreset(preset.key as any)"
                >
                    {{ preset.label }}
                </button>
            </div>
        </div>

        <!-- Kartu Ringkasan -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="flex flex-col gap-1 rounded-xl border border-border p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Total Pendapatan</span>
                    <BarChart3 class="h-4 w-4 text-muted-foreground" />
                </div>
                <span class="text-2xl font-bold text-green-600">{{ formatRupiah(summary.total_revenue) }}</span>
                <span class="text-xs text-muted-foreground">{{ summary.total_transactions }} transaksi</span>
            </div>

            <div class="flex flex-col gap-1 rounded-xl border border-border p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Cash</span>
                    <Wallet class="h-4 w-4 text-muted-foreground" />
                </div>
                <span class="text-2xl font-bold">{{ formatRupiah(summary.total_cash) }}</span>
                <span class="text-xs text-muted-foreground">Pembayaran tunai</span>
            </div>

            <div class="flex flex-col gap-1 rounded-xl border border-border p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">QRIS</span>
                    <Receipt class="h-4 w-4 text-muted-foreground" />
                </div>
                <span class="text-2xl font-bold">{{ formatRupiah(summary.total_qris) }}</span>
                <span class="text-xs text-muted-foreground">Pembayaran QRIS</span>
            </div>

            <div class="flex flex-col gap-1 rounded-xl border border-border p-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Total Pengeluaran</span>
                    <TrendingDown class="h-4 w-4 text-muted-foreground" />
                </div>
                <span class="text-2xl font-bold text-red-500">{{ formatRupiah(summary.total_expenses) }}</span>
            </div>

            <div class="flex flex-col gap-1 rounded-xl border border-border p-4 lg:col-span-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">Net (Pendapatan − Pengeluaran)</span>
                    <TrendingUp class="h-4 w-4 text-muted-foreground" />
                </div>
                <span
                    class="text-2xl font-bold"
                    :class="summary.net >= 0 ? 'text-green-600' : 'text-red-500'"
                >
                    {{ formatRupiah(summary.net) }}
                </span>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2 print-single-col">
            <!-- Rekap Harian -->
            <div class="flex flex-col gap-3">
                <h2 class="font-semibold">Rekap Per Hari</h2>
                <div class="rounded-xl border border-border overflow-hidden">
                    <table class="w-full text-xs">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-muted-foreground">Tanggal</th>
                                <th class="px-3 py-2 text-right font-medium text-muted-foreground">Trx</th>
                                <th class="px-3 py-2 text-right font-medium text-muted-foreground">Cash</th>
                                <th class="px-3 py-2 text-right font-medium text-muted-foreground">QRIS</th>
                                <th class="px-3 py-2 text-right font-medium text-muted-foreground">Keluar</th>
                                <th class="px-3 py-2 text-right font-medium text-muted-foreground">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="daily.length === 0" class="border-t border-border">
                                <td colspan="6" class="px-3 py-6 text-center text-muted-foreground">Tidak ada data.</td>
                            </tr>
                            <tr
                                v-for="row in daily"
                                :key="row.date"
                                class="border-t border-border hover:bg-muted/30"
                            >
                                <td class="px-3 py-2 font-medium">{{ row.date }}</td>
                                <td class="px-3 py-2 text-right">{{ row.tx_count }}</td>
                                <td class="px-3 py-2 text-right font-mono">{{ formatRupiah(row.cash) }}</td>
                                <td class="px-3 py-2 text-right font-mono">{{ formatRupiah(row.qris) }}</td>
                                <td class="px-3 py-2 text-right font-mono text-red-500">{{ formatRupiah(row.expenses) }}</td>
                                <td
                                    class="px-3 py-2 text-right font-mono font-semibold"
                                    :class="row.net >= 0 ? 'text-green-600' : 'text-red-500'"
                                >
                                    {{ formatRupiah(row.net) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-col gap-6">
                <!-- Top Layanan -->
                <div class="flex flex-col gap-3">
                    <h2 class="font-semibold">Top 10 Layanan</h2>
                    <div class="rounded-xl border border-border overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-muted-foreground">Layanan</th>
                                    <th class="px-3 py-2 text-right font-medium text-muted-foreground">Qty</th>
                                    <th class="px-3 py-2 text-right font-medium text-muted-foreground">Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="top_services.length === 0" class="border-t border-border">
                                    <td colspan="3" class="px-3 py-6 text-center text-muted-foreground">Tidak ada data.</td>
                                </tr>
                                <tr
                                    v-for="(svc, i) in top_services"
                                    :key="svc.service_name"
                                    class="border-t border-border hover:bg-muted/30"
                                >
                                    <td class="px-3 py-2">
                                        <span class="mr-1.5 text-muted-foreground">{{ i + 1 }}.</span>
                                        {{ svc.service_name }}
                                    </td>
                                    <td class="px-3 py-2 text-right">{{ svc.total_qty }}</td>
                                    <td class="px-3 py-2 text-right font-mono font-semibold text-green-600">{{ formatRupiah(svc.total_revenue) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pengeluaran per Kategori -->
                <div class="flex flex-col gap-3">
                    <h2 class="font-semibold">Pengeluaran per Kategori</h2>
                    <div class="rounded-xl border border-border overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-muted-foreground">Kategori</th>
                                    <th class="px-3 py-2 text-right font-medium text-muted-foreground">Kali</th>
                                    <th class="px-3 py-2 text-right font-medium text-muted-foreground">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="expense_by_category.length === 0" class="border-t border-border">
                                    <td colspan="3" class="px-3 py-6 text-center text-muted-foreground">Tidak ada pengeluaran.</td>
                                </tr>
                                <tr
                                    v-for="exp in expense_by_category"
                                    :key="exp.category"
                                    class="border-t border-border hover:bg-muted/30"
                                >
                                    <td class="px-3 py-2">{{ exp.category }}</td>
                                    <td class="px-3 py-2 text-right text-muted-foreground">{{ exp.count }}×</td>
                                    <td class="px-3 py-2 text-right font-mono font-semibold text-red-500">{{ formatRupiah(exp.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* ─── Print Laporan A4 ────────────────────────────────────────── */
@media print {

    /* margin: 0 menghilangkan header/footer bawaan browser (tanggal, URL)
       lalu padding ditambahkan manual di .print-area                      */
    @page {
        size: A4 portrait;
        margin: 0;
    }

    /* Sembunyikan semua, lalu tampilkan hanya area laporan */
    body {
        visibility: hidden !important;
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .print-area {
        visibility: visible !important;
        position: absolute !important;
        inset: 0 !important;
        width: 100% !important;
        padding: 1.5cm !important;
        margin: 0 !important;
        background: white !important;
        font-size: 10pt !important;
        color: #000 !important;
    }

    .print-area * {
        visibility: visible !important;
        color: #000 !important;           /* Hapus semua warna — hitam semua */
    }

    /* Sembunyikan kontrol layar */
    .no-print { display: none !important; }

    /* Tampilkan header print */
    .print-only { display: block !important; }

    /* Kartu ringkasan: single column 3-per-row */
    .grid { display: block !important; }
    .grid > * {
        display: inline-block !important;
        width: 30% !important;
        margin: 0.2cm 0.5% 0.3cm !important;
        vertical-align: top;
    }

    /* Tabel rekap: full width, single column */
    .print-single-col { display: block !important; }
    .print-single-col > * {
        display: block !important;
        width: 100% !important;
        margin-bottom: 0.6cm !important;
    }

    /* Kartu & tabel: hapus warna latar, border hitam */
    .rounded-xl {
        border: 1px solid #bbb !important;
        box-shadow: none !important;
        background: white !important;
        break-inside: avoid;
        padding: 0.3cm !important;
    }

    table { width: 100% !important; border-collapse: collapse !important; }
    th, td {
        border-bottom: 1px solid #ddd !important;
        padding: 4px 6px !important;
        font-size: 9pt !important;
    }
    thead { background: #f5f5f5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

    /* Hindari baris terpotong di tengah halaman */
    tr { break-inside: avoid !important; }
    h2 { font-size: 11pt !important; margin: 0.3cm 0 0.15cm !important; }
}
</style>
