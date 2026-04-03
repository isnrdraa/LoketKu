<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Store } from 'lucide-vue-next';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Pengaturan Toko', href: '/store-settings' },
        ],
    },
});

const props = defineProps<{
    settings: {
        store_name: string;
        store_address: string;
        store_phone: string;
        store_footer: string;
        timezone: string;
    };
}>();

const form = useForm({
    store_name:    props.settings.store_name    ?? '',
    store_address: props.settings.store_address ?? '',
    store_phone:   props.settings.store_phone   ?? '',
    store_footer:  props.settings.store_footer  ?? '',
    timezone:      props.settings.timezone      ?? 'Asia/Jakarta',
});

// Daftar timezone umum dengan label ramah pengguna
const timezoneOptions = [
    { label: '🇮🇩 WIB — Waktu Indonesia Barat (GMT+7)',   value: 'Asia/Jakarta' },
    { label: '🇮🇩 WITA — Waktu Indonesia Tengah (GMT+8)', value: 'Asia/Makassar' },
    { label: '🇮🇩 WIT — Waktu Indonesia Timur (GMT+9)',   value: 'Asia/Jayapura' },
    { label: '─────────────────', value: '', disabled: true },
    { label: '🌍 UTC (GMT+0)',                            value: 'UTC' },
    { label: '🌏 Singapura / Malaysia (GMT+8)',           value: 'Asia/Singapore' },
    { label: '🌏 Bangkok (GMT+7)',                        value: 'Asia/Bangkok' },
    { label: '🌏 Tokyo (GMT+9)',                          value: 'Asia/Tokyo' },
];

function submit() {
    form.put('/store-settings');
}
</script>

<template>
    <Head title="Pengaturan Toko" />

    <div class="flex flex-1 flex-col gap-6 p-4 max-w-2xl">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <Store class="h-5 w-5" />
            </div>
            <div>
                <h1 class="text-xl font-bold">Pengaturan Toko</h1>
                <p class="text-sm text-muted-foreground">
                    Nama, alamat, dan informasi yang tampil di struk
                </p>
            </div>
        </div>

        <!-- Form -->
        <form class="flex flex-col gap-5" @submit.prevent="submit">

            <!-- Nama Usaha -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold" for="store_name">
                    Nama Usaha / Toko <span class="text-destructive">*</span>
                </label>
                <input
                    id="store_name"
                    v-model="form.store_name"
                    type="text"
                    maxlength="100"
                    placeholder="Contoh: Wisata Tirtomarto"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                    required
                />
                <p v-if="form.errors.store_name" class="text-xs text-destructive">{{ form.errors.store_name }}</p>
                <p class="text-xs text-muted-foreground">Tampil di bagian atas struk dan sidebar navigasi.</p>
            </div>

            <!-- Alamat -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold" for="store_address">Alamat</label>
                <textarea
                    id="store_address"
                    v-model="form.store_address"
                    rows="2"
                    maxlength="255"
                    placeholder="Contoh: Jl. Wisata No.1, Desa Tirtomarto"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                />
                <p v-if="form.errors.store_address" class="text-xs text-destructive">{{ form.errors.store_address }}</p>
            </div>

            <!-- Nomor Telepon -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold" for="store_phone">Nomor Telepon / WhatsApp</label>
                <input
                    id="store_phone"
                    v-model="form.store_phone"
                    type="text"
                    maxlength="50"
                    placeholder="Contoh: 0812-3456-7890"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                />
                <p v-if="form.errors.store_phone" class="text-xs text-destructive">{{ form.errors.store_phone }}</p>
            </div>

            <!-- Timezone -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold" for="timezone">
                    Zona Waktu <span class="text-destructive">*</span>
                </label>
                <select
                    id="timezone"
                    v-model="form.timezone"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                >
                    <option
                        v-for="tz in timezoneOptions"
                        :key="tz.value"
                        :value="tz.value"
                        :disabled="tz.disabled"
                    >{{ tz.label }}</option>
                </select>
                <p v-if="form.errors.timezone" class="text-xs text-destructive">{{ form.errors.timezone }}</p>
                <p class="text-xs text-muted-foreground">
                    Mempengaruhi semua tampilan tanggal &amp; jam di struk dan laporan.
                    Sekarang: <strong>{{ new Date().toLocaleString('id-ID', { timeZone: form.timezone, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }) }}</strong>
                    ({{ form.timezone }})
                </p>
            </div>

            <!-- Footer / Slogan -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold" for="store_footer">Slogan / Pesan di Struk</label>
                <input
                    id="store_footer"
                    v-model="form.store_footer"
                    type="text"
                    maxlength="255"
                    placeholder="Contoh: Terima kasih telah berkunjung! Sampai jumpa lagi."
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                />
                <p v-if="form.errors.store_footer" class="text-xs text-destructive">{{ form.errors.store_footer }}</p>
                <p class="text-xs text-muted-foreground">Tampil di bagian bawah struk.</p>
            </div>

            <!-- Preview Struk Mini -->
            <div class="rounded-xl border border-dashed border-border bg-muted/30 p-4">
                <p class="text-xs font-semibold text-muted-foreground mb-3 uppercase tracking-wide">Preview Struk</p>
                <div class="mx-auto max-w-[240px] rounded-lg border bg-white p-4 text-center shadow-sm text-[#1a1a1a]">
                    <p class="text-sm font-bold tracking-widest uppercase leading-tight">{{ form.store_name || 'Nama Toko' }}</p>
                    <p v-if="form.store_address" class="text-[10px] text-gray-500 mt-0.5 leading-tight">{{ form.store_address }}</p>
                    <p v-if="form.store_phone" class="text-[10px] text-gray-500 mt-0.5">{{ form.store_phone }}</p>
                    <p v-if="form.store_footer" class="text-[10px] text-gray-400 italic mt-1 leading-snug">{{ form.store_footer }}</p>
                    <div class="border-t border-dashed border-gray-300 my-3" />
                    <p class="text-[10px] text-gray-400">No. Transaksi</p>
                    <p class="text-[10px] font-mono font-semibold">TRX-YYYYMMDD-XXXX</p>
                    <div class="border-t border-dashed border-gray-300 my-2" />
                    <p class="text-[10px] font-bold">TOTAL &nbsp; Rp X.XXX</p>
                    <div class="border-t border-dashed border-gray-300 my-2" />
                    <p class="text-[10px] text-gray-400 tracking-widest">* * *</p>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex gap-3 pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors disabled:opacity-50"
                >
                    <Save class="h-4 w-4" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                </button>
            </div>
        </form>
    </div>
</template>
