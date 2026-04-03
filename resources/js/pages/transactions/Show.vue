<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Bluetooth, Printer, ShoppingBag } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import BluetoothPrinterModal from '@/components/BluetoothPrinterModal.vue';
import type { BluetoothDevice } from '@/composables/useThermalPrint';
import { useThermalPrint } from '@/composables/useThermalPrint';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Transaksi', href: '/transactions' },
            { title: 'Struk', href: '#' },
        ],
    },
});

type TransactionItem = {
    id: number;
    service_name: string;
    qty: number;
    unit_price: number;
    subtotal: number;
};

type Transaction = {
    id: number;
    transaction_code: string;
    transaction_date: string;
    transaction_time: string;
    cashier_name: string;
    payment_method: 'cash' | 'qris';
    subtotal: number;
    grand_total: number;
    notes: string | null;
    items: TransactionItem[];
};

type Store = {
    name: string;
    address: string;
    phone: string;
    footer: string;
};

const props = defineProps<{
    transaction: Transaction;
}>();

const page  = usePage<{ store: Store }>();
const store = computed(() => page.props.store);

// ── Thermal print composable ──────────────────────────────────────────────────
const {
    isNative,
    devices,
    selectedDevice,
    printing,
    error: printError,
    loadPairedDevices,
    printReceipt: doprint,
} = useThermalPrint();

// ── Modal Bluetooth ───────────────────────────────────────────────────────────
const showBtModal  = ref(false);
const btLoading    = ref(false);

async function reloadBtDevices() {
    btLoading.value = true;

    try {
        await loadPairedDevices();
    } finally {
        btLoading.value = false;
    }
}

async function openBtModal() {
    showBtModal.value = true;
    await reloadBtDevices();
}

async function handleBtPrint(device: BluetoothDevice, paperWidth: 32 | 42) {
    showBtModal.value = false;
    selectedDevice.value = device;

    await doprint(
        {
            storeName:    store.value.name,
            storeAddress: store.value.address,
            storePhone:   store.value.phone,
            storeFooter:  store.value.footer,
            transactionCode: props.transaction.transaction_code,
            transactionDate: props.transaction.transaction_date,
            transactionTime: props.transaction.transaction_time,
            cashierName:   props.transaction.cashier_name,
            paymentMethod: props.transaction.payment_method,
            items: props.transaction.items,
            grandTotal: props.transaction.grand_total,
            notes: props.transaction.notes,
        },
        device.id,
        paperWidth,
    );
}

// ── Web / fallback print (window.print) ──────────────────────────────────────
function handleWebPrint() {
    window.print();
}

/** Tombol utama: Android → buka modal BT, Web → window.print() */
function handlePrintClick() {
    if (isNative) {
        openBtModal();
    } else {
        handleWebPrint();
    }
}

// ── Auto-print dari redirect ?print=1 ────────────────────────────────────────
const autoPrint = ref(false);

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('print') === '1') {
        autoPrint.value = true;

        if (isNative) {
            // Di Android: buka modal pilih printer
            setTimeout(() => openBtModal(), 600);
        } else {
            setTimeout(() => window.print(), 600);
        }
    }
});

function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}
</script>

<template>
    <Head :title="`Struk - ${transaction.transaction_code}`" />

    <!-- ─── Toolbar (tersembunyi saat print) ──────────────────────────── -->
    <div class="no-print flex flex-wrap items-center gap-2 p-4 pb-0">
        <Link
            href="/transactions"
            class="inline-flex items-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-sm font-medium hover:bg-muted transition-colors"
        >
            <ArrowLeft class="h-4 w-4" />
            Kembali
        </Link>

        <!-- Tombol cetak: di Android tampil icon Bluetooth, di web tampil Printer -->
        <button
            class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors disabled:opacity-60"
            :disabled="printing"
            @click="handlePrintClick"
        >
            <Bluetooth v-if="isNative" class="h-4 w-4" />
            <Printer v-else class="h-4 w-4" />
            {{ printing ? 'Mencetak…' : (isNative ? 'Cetak via Bluetooth' : 'Cetak Struk') }}
        </button>

        <Link
            href="/transactions/create"
            class="inline-flex items-center gap-1.5 rounded-md border border-border px-3 py-1.5 text-sm font-medium hover:bg-muted transition-colors"
        >
            <ShoppingBag class="h-4 w-4" />
            Transaksi Baru
        </Link>

        <span
            v-if="autoPrint && !isNative"
            class="ml-2 text-xs text-muted-foreground animate-pulse"
        >
            🖨️ Sedang mencetak...
        </span>

        <!-- Error print Bluetooth -->
        <p v-if="printError" class="w-full text-xs text-destructive mt-1">
            ⚠️ {{ printError }}
        </p>
    </div>

    <!-- ─── Modal pilih printer Bluetooth (Android only) ─────────────── -->
    <BluetoothPrinterModal
        :open="showBtModal"
        :devices="devices"
        :selected-device="selectedDevice"
        :loading="btLoading"
        :error="printError"
        @close="showBtModal = false"
        @refresh="reloadBtDevices"
        @print="handleBtPrint"
    />

    <!-- ─── Area preview struk ────────────────────────────────────────── -->
    <div class="receipt-wrapper flex justify-center p-4 pt-6">
        <div
            id="receipt"
            class="receipt-paper w-full max-w-xs rounded-xl border border-border bg-white p-5 shadow-sm text-[#1a1a1a]"
        >
            <!-- Header toko — sama persis dengan preview di Pengaturan Toko -->
            <div class="text-center mb-3">
                <h1 class="text-base font-bold tracking-widest uppercase leading-tight">{{ store.name }}</h1>
                <p v-if="store.address" class="text-[10px] text-gray-500 leading-tight mt-0.5">{{ store.address }}</p>
                <p v-if="store.phone" class="text-[10px] text-gray-500 mt-0.5">{{ store.phone }}</p>
                <p v-if="store.footer" class="text-[10px] text-gray-400 italic mt-1 leading-snug">{{ store.footer }}</p>
            </div>

            <!-- Divider -->
            <div class="border-t border-dashed border-gray-400 my-2" />

            <!-- Info transaksi -->
            <div class="space-y-1 text-[11px]">
                <!-- No Transaksi: label di atas, kode di bawah (menghindari wrap) -->
                <div>
                    <div class="text-gray-500">No. Transaksi</div>
                    <div class="font-mono font-semibold tracking-wide">{{ transaction.transaction_code }}</div>
                </div>
                <div class="flex justify-between gap-2">
                    <span class="text-gray-500 shrink-0">Tanggal</span>
                    <span class="text-right">{{ transaction.transaction_date }} {{ transaction.transaction_time }}</span>
                </div>
                <div class="flex justify-between gap-2">
                    <span class="text-gray-500 shrink-0">Kasir</span>
                    <span class="text-right">{{ transaction.cashier_name }}</span>
                </div>
                <div class="flex justify-between gap-2">
                    <span class="text-gray-500 shrink-0">Metode</span>
                    <span class="font-semibold uppercase">{{ transaction.payment_method }}</span>
                </div>
            </div>

            <div class="border-t border-dashed border-gray-400 my-2" />

            <!-- Daftar item -->
            <div class="space-y-1.5">
                <div
                    v-for="item in transaction.items"
                    :key="item.id"
                    class="text-[11px]"
                >
                    <div class="flex justify-between items-start">
                        <span class="font-medium leading-tight pr-2">{{ item.service_name }}</span>
                        <span class="font-semibold whitespace-nowrap">{{ formatRupiah(item.subtotal) }}</span>
                    </div>
                    <div class="text-gray-500 text-[10px]">
                        {{ item.qty }} × {{ formatRupiah(item.unit_price) }}
                    </div>
                </div>
            </div>

            <div class="border-t border-dashed border-gray-400 my-2" />

            <!-- Total -->
            <div class="flex items-center justify-between text-sm font-bold">
                <span class="tracking-wide">TOTAL</span>
                <span class="text-base">{{ formatRupiah(transaction.grand_total) }}</span>
            </div>

            <!-- Catatan -->
            <div v-if="transaction.notes" class="mt-2 text-[10px] text-gray-500 leading-snug">
                <span class="font-medium">Catatan:</span> {{ transaction.notes }}
            </div>

            <!-- Penutup struk -->
            <div class="border-t border-dashed border-gray-400 my-2" />
            <p class="text-center text-[10px] text-gray-400 tracking-widest">* * *</p>
        </div>
    </div>
</template>

<style>
/* ─── Thermal print — adaptif untuk 58mm & 80mm ──────────────── */
@media print {
    @page {
        /*
         * "auto" pada lebar membuat browser menggunakan ukuran kertas
         * dari driver printer secara otomatis (58mm / 80mm / dll).
         * Jangan hardcode lebar di sini agar kompatibel semua printer.
         */
        size: auto;
        margin: 0;
    }

    /*
     * visibility:hidden pada body memungkinkan children di-override
     * dengan visibility:visible — berbeda dengan display:none.
     */
    body {
        visibility: hidden !important;
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }

    /* Wrapper struk: isi seluruh halaman cetak */
    .receipt-wrapper {
        visibility: visible !important;
        position: fixed !important;
        inset: 0 !important;
        display: block !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        background: white !important;
    }

    .receipt-wrapper * {
        visibility: visible !important;
    }

    /* Konten struk: ikuti lebar halaman sepenuhnya */
    .receipt-paper {
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 3mm 4mm !important;
        margin: 0 !important;
        font-size: 10px !important;
        line-height: 1.3 !important;
        color: #000 !important;
        background: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Sesuaikan ukuran font per elemen agar pas di kertas sempit */
    .receipt-paper .text-base  { font-size: 12px !important; }
    .receipt-paper .text-sm    { font-size: 10px !important; }
    .receipt-paper .text-\[11px\] { font-size: 10px !important; }
    .receipt-paper .text-\[10px\] { font-size: 9px !important; }
    .receipt-paper .font-bold  { font-weight: 700 !important; }
}
</style>
