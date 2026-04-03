<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { BarChart3, Receipt, ShoppingBag, TrendingDown, TrendingUp, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import type { Auth } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const page = usePage<{
    auth: Auth;
    stats: {
        today_revenue: number;
        today_transactions: number;
        today_cash: number;
        today_qris: number;
        today_expenses: number;
        today_net: number;
    };
    last_7_days: Array<{
        date: string;
        revenue: number;
        expenses: number;
        net: number;
    }>;
    top_services_today: Array<{
        name: string;
        qty: number;
        revenue: number;
    }>;
}>();

const stats             = computed(() => page.props.stats);
const last7Days         = computed(() => page.props.last_7_days ?? []);
const topServicesToday  = computed(() => page.props.top_services_today ?? []);
const user              = computed(() => page.props.auth.user);
const isAdmin           = computed(() => user.value.role === 'admin');

function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

function formatK(value: number): string {
    if (value >= 1_000_000) return (value / 1_000_000).toFixed(1) + 'jt';
    if (value >= 1_000) return (value / 1_000).toFixed(0) + 'rb';
    return String(value);
}

// Untuk bar chart sederhana — hitung % tinggi bar relatif terhadap nilai max
const maxRevenue = computed(() =>
    Math.max(...last7Days.value.map((d) => d.revenue), 1)
);
const maxExpenses = computed(() =>
    Math.max(...last7Days.value.map((d) => d.expenses), 1)
);
const maxTopRevenue = computed(() =>
    Math.max(...topServicesToday.value.map((s) => s.revenue), 1)
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <!-- Sapaan -->
        <div>
            <h1 class="text-2xl font-bold">
                Selamat datang, {{ user.name }} 👋
            </h1>
            <p class="text-muted-foreground text-sm">
                {{ user.role === 'admin' ? 'Administrator' : 'Kasir' }} · Ringkasan hari ini
            </p>
        </div>

        <!-- Kartu statistik -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Pendapatan Hari Ini</CardTitle>
                    <BarChart3 class="text-muted-foreground h-4 w-4" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-green-600">{{ formatRupiah(stats.today_revenue) }}</div>
                    <p class="text-muted-foreground text-xs">Total dari semua transaksi</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Jumlah Transaksi</CardTitle>
                    <Receipt class="text-muted-foreground h-4 w-4" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.today_transactions }}</div>
                    <p class="text-muted-foreground text-xs">Transaksi hari ini</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Tunai (Cash)</CardTitle>
                    <ShoppingBag class="text-muted-foreground h-4 w-4" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ formatRupiah(stats.today_cash) }}</div>
                    <p class="text-muted-foreground text-xs">Pembayaran cash</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">QRIS</CardTitle>
                    <ShoppingBag class="text-muted-foreground h-4 w-4" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ formatRupiah(stats.today_qris) }}</div>
                    <p class="text-muted-foreground text-xs">Pembayaran QRIS</p>
                </CardContent>
            </Card>

            <Card v-if="isAdmin">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Pengeluaran</CardTitle>
                    <TrendingDown class="text-muted-foreground h-4 w-4" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-red-500">{{ formatRupiah(stats.today_expenses) }}</div>
                    <p class="text-muted-foreground text-xs">Total pengeluaran hari ini</p>
                </CardContent>
            </Card>

            <Card v-if="isAdmin">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Net Hari Ini</CardTitle>
                    <TrendingUp class="text-muted-foreground h-4 w-4" />
                </CardHeader>
                <CardContent>
                    <div
                        class="text-2xl font-bold"
                        :class="stats.today_net >= 0 ? 'text-green-600' : 'text-red-500'"
                    >
                        {{ formatRupiah(stats.today_net) }}
                    </div>
                    <p class="text-muted-foreground text-xs">Pendapatan - Pengeluaran</p>
                </CardContent>
            </Card>
        </div>

        <!-- ─── Section bawah: Chart 7 hari + Top Layanan (admin only) ─── -->
        <div v-if="isAdmin" class="grid gap-6 lg:grid-cols-2">

            <!-- Bar chart 7 hari — pure CSS -->
            <Card class="overflow-hidden">
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Pendapatan 7 Hari Terakhir</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="last7Days.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                        Belum ada data.
                    </div>
                    <div v-else class="flex h-40 items-end gap-1.5">
                        <div
                            v-for="day in last7Days"
                            :key="day.date"
                            class="group relative flex flex-1 flex-col items-center gap-1"
                        >
                            <!-- Tooltip -->
                            <div class="pointer-events-none absolute -top-10 left-1/2 -translate-x-1/2 z-10 hidden rounded bg-foreground px-2 py-1 text-xs text-background whitespace-nowrap group-hover:block">
                                {{ formatK(day.revenue) }}
                            </div>
                            <!-- Bar pendapatan -->
                            <div
                                class="w-full rounded-t bg-green-500 transition-all"
                                :style="{ height: Math.max((day.revenue / maxRevenue) * 120, day.revenue > 0 ? 4 : 0) + 'px' }"
                            />
                            <!-- Bar pengeluaran (overlay) -->
                            <div
                                class="w-full rounded-t bg-red-400 opacity-80 transition-all"
                                :style="{ height: Math.max((day.expenses / maxRevenue) * 120, day.expenses > 0 ? 2 : 0) + 'px' }"
                            />
                            <span class="text-[10px] text-muted-foreground mt-1">{{ day.date }}</span>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center gap-4 text-xs text-muted-foreground">
                        <span class="flex items-center gap-1"><span class="inline-block h-2 w-3 rounded bg-green-500" /> Pendapatan</span>
                        <span class="flex items-center gap-1"><span class="inline-block h-2 w-3 rounded bg-red-400" /> Pengeluaran</span>
                    </div>
                </CardContent>
            </Card>

            <!-- Top layanan hari ini -->
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium">Top Layanan Hari Ini</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="topServicesToday.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                        Belum ada transaksi hari ini.
                    </div>
                    <div v-else class="flex flex-col gap-2">
                        <div
                            v-for="(svc, i) in topServicesToday"
                            :key="svc.name"
                            class="flex flex-col gap-1"
                        >
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium">
                                    <span class="mr-1.5 text-xs text-muted-foreground">{{ i + 1 }}.</span>{{ svc.name }}
                                </span>
                                <span class="text-xs text-muted-foreground">{{ svc.qty }}× · {{ formatRupiah(svc.revenue) }}</span>
                            </div>
                            <!-- Progress bar -->
                            <div class="h-1.5 w-full rounded-full bg-muted overflow-hidden">
                                <div
                                    class="h-full rounded-full bg-primary transition-all"
                                    :style="{ width: (svc.revenue / maxTopRevenue * 100) + '%' }"
                                />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Shortcut kasir -->
        <div v-if="!isAdmin" class="flex gap-3">
            <a
                href="/transactions/create"
                class="flex-1 flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-primary/30 bg-primary/5 py-6 text-primary hover:bg-primary/10 transition-colors"
            >
                <ShoppingBag class="h-8 w-8" />
                <span class="font-semibold">Transaksi Baru</span>
            </a>
            <a
                href="/transactions"
                class="flex-1 flex flex-col items-center justify-center gap-2 rounded-xl border border-border bg-muted/30 py-6 hover:bg-muted/60 transition-colors"
            >
                <Receipt class="h-8 w-8 text-muted-foreground" />
                <span class="font-semibold text-sm">Riwayat</span>
            </a>
        </div>
    </div>
</template>
