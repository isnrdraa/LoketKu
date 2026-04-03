/**
 * useThermalPrint — Composable untuk cetak struk thermal.
 *
 * Strategi dual-mode:
 *  • Di browser / desktop  → window.print() (CSS print seperti biasa)
 *  • Di Android Capacitor  → kirim ESC/POS bytes ke printer Bluetooth SPP
 *
 * Printer yang didukung: semua printer thermal yang menggunakan
 * protokol ESC/POS via Classic Bluetooth (SPP), seperti:
 *   - Xprinter XP-58 / XP-80 series
 *   - EPPOS, Goojprt, HOIN, dll.
 */

import { Capacitor } from '@capacitor/core';
import { BluetoothSerial } from '@e-is/capacitor-bluetooth-serial';
import { ref } from 'vue';

// ─── Tipe ─────────────────────────────────────────────────────────────────────

export type BluetoothDevice = {
    id: string;      // MAC address
    name: string;
    class?: number;
};

export type ReceiptData = {
    storeName: string;
    storeAddress?: string;
    storePhone?: string;
    storeFooter?: string;
    transactionCode: string;
    transactionDate: string;    // "DD/MM/YYYY"
    transactionTime: string;    // "HH:MM"
    cashierName: string;
    paymentMethod: string;
    items: {
        service_name: string;
        qty: number;
        unit_price: number;
        subtotal: number;
    }[];
    grandTotal: number;
    notes?: string | null;
};

// ─── ESC/POS Encoder ──────────────────────────────────────────────────────────

/**
 * Kumpulan konstanta ESC/POS yang umum dipakai.
 */
const ESC = '\x1B';
const GS  = '\x1D';

const CMD = {
    INIT:           `${ESC}@`,          // Reset printer
    ALIGN_LEFT:     `${ESC}a\x00`,
    ALIGN_CENTER:   `${ESC}a\x01`,
    ALIGN_RIGHT:    `${ESC}a\x02`,
    BOLD_ON:        `${ESC}E\x01`,
    BOLD_OFF:       `${ESC}E\x00`,
    DOUBLE_HEIGHT:  `${ESC}!\x10`,      // Double height text
    NORMAL_TEXT:    `${ESC}!\x00`,      // Kembali ke normal
    FONT_LARGE:     `${GS}!\x11`,       // 2x width + 2x height
    FONT_NORMAL:    `${GS}!\x00`,
    LINE_FEED:      '\n',
    FEED_LINES:     (n: number) => `${ESC}d${String.fromCharCode(n)}`,
    CUT:            `${GS}V\x42\x00`,   // Partial cut
};

/**
 * Pad / truncate string ke lebar tertentu.
 */
function padEnd(str: string, width: number): string {
    return str.substring(0, width).padEnd(width, ' ');
}

function padStart(str: string, width: number): string {
    return str.substring(0, width).padStart(width, ' ');
}

/**
 * Buat baris dua kolom: kiri + kanan (total width karakter).
 * Default width 32 karakter (58mm ~32 char, 80mm ~42 char).
 */
function twoCol(left: string, right: string, width = 32): string {
    const rightLen = right.length;
    const leftMax  = width - rightLen - 1;
    return padEnd(left, leftMax) + ' ' + right + '\n';
}

/**
 * Garis pemisah.
 */
function divider(width = 32, char = '-'): string {
    return char.repeat(width) + '\n';
}

/**
 * Format rupiah sederhana (tanpa library).
 */
function rupiah(v: number): string {
    return 'Rp ' + v.toLocaleString('id-ID');
}

/**
 * Konversi ReceiptData → string ESC/POS siap kirim.
 *
 * @param receipt  Data struk
 * @param width    Lebar kertas dalam karakter (32 untuk 58mm, 42 untuk 80mm)
 */
export function buildEscPos(receipt: ReceiptData, width = 32): string {
    let doc = '';

    // ── Init ─────────────────────────────────────────────────────────────────
    doc += CMD.INIT;

    // ── Header toko ──────────────────────────────────────────────────────────
    doc += CMD.ALIGN_CENTER;
    doc += CMD.BOLD_ON;
    doc += CMD.DOUBLE_HEIGHT;
    doc += receipt.storeName.toUpperCase() + '\n';
    doc += CMD.NORMAL_TEXT;
    doc += CMD.BOLD_OFF;

    if (receipt.storeAddress) {
        doc += receipt.storeAddress + '\n';
    }
    if (receipt.storePhone) {
        doc += receipt.storePhone + '\n';
    }
    if (receipt.storeFooter) {
        doc += receipt.storeFooter + '\n';
    }

    // ── Divider ───────────────────────────────────────────────────────────────
    doc += CMD.ALIGN_LEFT;
    doc += divider(width, '-');

    // ── Info transaksi ────────────────────────────────────────────────────────
    doc += 'No. Transaksi:\n';
    doc += CMD.BOLD_ON;
    doc += receipt.transactionCode + '\n';
    doc += CMD.BOLD_OFF;

    doc += twoCol('Tanggal', `${receipt.transactionDate} ${receipt.transactionTime}`, width);
    doc += twoCol('Kasir',   receipt.cashierName, width);
    doc += twoCol('Metode',  receipt.paymentMethod.toUpperCase(), width);

    // ── Divider ───────────────────────────────────────────────────────────────
    doc += divider(width, '-');

    // ── Item ──────────────────────────────────────────────────────────────────
    for (const item of receipt.items) {
        // Nama item (baris sendiri jika panjang)
        doc += item.service_name + '\n';
        // Qty × harga = subtotal
        const detail = `${item.qty} x ${rupiah(item.unit_price)}`;
        doc += twoCol(detail, rupiah(item.subtotal), width);
    }

    // ── Divider ───────────────────────────────────────────────────────────────
    doc += divider(width, '=');

    // ── Total ─────────────────────────────────────────────────────────────────
    doc += CMD.BOLD_ON;
    doc += twoCol('TOTAL', rupiah(receipt.grandTotal), width);
    doc += CMD.BOLD_OFF;

    // ── Catatan ───────────────────────────────────────────────────────────────
    if (receipt.notes) {
        doc += divider(width, '-');
        doc += 'Catatan: ' + receipt.notes + '\n';
    }

    // ── Footer ────────────────────────────────────────────────────────────────
    doc += divider(width, '-');
    doc += CMD.ALIGN_CENTER;
    doc += '* * *' + '\n';

    // ── Feed & Cut ────────────────────────────────────────────────────────────
    doc += CMD.FEED_LINES(4);
    doc += CMD.CUT;

    return doc;
}

// ─── Composable ───────────────────────────────────────────────────────────────

export function useThermalPrint() {
    const isNative      = Capacitor.isNativePlatform();
    const devices       = ref<BluetoothDevice[]>([]);
    const selectedDevice = ref<BluetoothDevice | null>(null);
    const printing      = ref(false);
    const error         = ref<string | null>(null);

    /**
     * Muat daftar perangkat Bluetooth yang sudah di-pair.
     * Hanya tersedia di mode native (Android).
     */
    async function loadPairedDevices(): Promise<BluetoothDevice[]> {
        if (!isNative) return [];

        try {
            const result = await BluetoothSerial.list();
            devices.value = (result.devices ?? []) as BluetoothDevice[];
            return devices.value;
        } catch (e: unknown) {
            error.value = 'Gagal memuat daftar perangkat: ' + String(e);
            return [];
        }
    }

    /**
     * Cetak struk.
     *
     * • Native (Android) : kirim ESC/POS ke printer Bluetooth yang dipilih.
     * • Browser          : fallback ke window.print().
     *
     * @param receipt      Data struk
     * @param deviceId     MAC address printer (opsional; pakai selectedDevice jika kosong)
     * @param paperWidth   32 (58mm) atau 42 (80mm)
     */
    async function printReceipt(
        receipt: ReceiptData,
        deviceId?: string,
        paperWidth: 32 | 42 = 32,
    ): Promise<void> {
        error.value  = null;
        printing.value = true;

        try {
            if (!isNative) {
                // ── Web / desktop: pakai CSS print ──────────────────────────
                window.print();
                return;
            }

            // ── Android: kirim via Bluetooth SPP ────────────────────────────
            const targetId = deviceId ?? selectedDevice.value?.id;

            if (!targetId) {
                throw new Error('Pilih printer Bluetooth terlebih dahulu.');
            }

            // Pastikan Bluetooth aktif
            const { isEnabled } = await BluetoothSerial.isEnabled();
            if (!isEnabled) {
                await BluetoothSerial.enable();
            }

            // Koneksi ke printer
            await BluetoothSerial.connect({ address: targetId });

            // Bangun dokumen ESC/POS dan kirim
            const escposData = buildEscPos(receipt, paperWidth);
            await BluetoothSerial.write({ value: escposData });

            // Disconnect setelah print
            await BluetoothSerial.disconnect();

        } catch (e: unknown) {
            error.value = String(e);
            // Pastikan disconnect meski error
            try { await BluetoothSerial.disconnect(); } catch { /* ignore */ }
        } finally {
            printing.value = false;
        }
    }

    return {
        isNative,
        devices,
        selectedDevice,
        printing,
        error,
        loadPairedDevices,
        printReceipt,
    };
}
