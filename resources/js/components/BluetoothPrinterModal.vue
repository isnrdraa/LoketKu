<script setup lang="ts">
/**
 * BluetoothPrinterModal — Modal untuk memilih printer Bluetooth SPP yang sudah di-pair.
 *
 * Hanya ditampilkan di Android (isNative = true).
 * Di browser, tombol "Cetak Struk" langsung memanggil window.print().
 */
import { Bluetooth, RefreshCw, X } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import type { BluetoothDevice } from '@/composables/useThermalPrint';

const props = defineProps<{
    open: boolean;
    devices: BluetoothDevice[];
    selectedDevice: BluetoothDevice | null;
    loading?: boolean;
    error?: string | null;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'refresh'): void;
    (e: 'select', device: BluetoothDevice): void;
    (e: 'print', device: BluetoothDevice, width: 32 | 42): void;
}>();

const paperWidth = ref<32 | 42>(32);
const picked = ref<BluetoothDevice | null>(props.selectedDevice ?? null);

onMounted(() => {
    if (props.devices.length === 0) emit('refresh');
});

function confirm() {
    if (!picked.value) return;
    emit('print', picked.value, paperWidth.value);
}
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4"
                @click.self="emit('close')"
            >
                <div class="w-full max-w-sm rounded-2xl bg-background shadow-xl">
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-border px-4 py-3">
                        <div class="flex items-center gap-2">
                            <Bluetooth class="h-4 w-4 text-primary" />
                            <span class="font-semibold text-sm">Pilih Printer Bluetooth</span>
                        </div>
                        <button
                            class="rounded-md p-1 hover:bg-muted transition-colors"
                            @click="emit('close')"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-4 flex flex-col gap-4">

                        <!-- Daftar perangkat yang di-pair -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-medium text-muted-foreground">
                                    Perangkat yang sudah di-pair:
                                </p>
                                <button
                                    class="inline-flex items-center gap-1 text-xs text-primary hover:underline"
                                    :disabled="loading"
                                    @click="emit('refresh')"
                                >
                                    <RefreshCw class="h-3 w-3" :class="{ 'animate-spin': loading }" />
                                    Refresh
                                </button>
                            </div>

                            <!-- Loading -->
                            <div v-if="loading" class="text-center py-4 text-sm text-muted-foreground">
                                Memuat daftar perangkat…
                            </div>

                            <!-- Tidak ada perangkat -->
                            <div
                                v-else-if="devices.length === 0"
                                class="rounded-lg border border-dashed border-border p-4 text-center text-sm text-muted-foreground"
                            >
                                Tidak ada perangkat Bluetooth yang di-pair.<br/>
                                <span class="text-xs">Pair printer di Pengaturan Bluetooth Android terlebih dahulu.</span>
                            </div>

                            <!-- List perangkat -->
                            <div v-else class="flex flex-col gap-1.5">
                                <button
                                    v-for="device in devices"
                                    :key="device.id"
                                    class="flex items-center gap-3 rounded-lg border px-3 py-2.5 text-left text-sm transition-colors"
                                    :class="picked?.id === device.id
                                        ? 'border-primary bg-primary/10 font-semibold'
                                        : 'border-border hover:bg-muted'"
                                    @click="picked = device"
                                >
                                    <Bluetooth
                                        class="h-4 w-4 shrink-0"
                                        :class="picked?.id === device.id ? 'text-primary' : 'text-muted-foreground'"
                                    />
                                    <div class="overflow-hidden">
                                        <div class="truncate">{{ device.name || 'Unknown Device' }}</div>
                                        <div class="text-[10px] font-mono text-muted-foreground">{{ device.id }}</div>
                                    </div>
                                    <div
                                        v-if="picked?.id === device.id"
                                        class="ml-auto h-2 w-2 rounded-full bg-primary shrink-0"
                                    />
                                </button>
                            </div>
                        </div>

                        <!-- Pilihan lebar kertas -->
                        <div>
                            <p class="text-xs font-medium text-muted-foreground mb-2">Lebar kertas printer:</p>
                            <div class="flex gap-2">
                                <button
                                    class="flex-1 rounded-lg border py-2 text-sm transition-colors"
                                    :class="paperWidth === 32
                                        ? 'border-primary bg-primary/10 font-semibold text-primary'
                                        : 'border-border hover:bg-muted'"
                                    @click="paperWidth = 32"
                                >
                                    58mm
                                </button>
                                <button
                                    class="flex-1 rounded-lg border py-2 text-sm transition-colors"
                                    :class="paperWidth === 42
                                        ? 'border-primary bg-primary/10 font-semibold text-primary'
                                        : 'border-border hover:bg-muted'"
                                    @click="paperWidth = 42"
                                >
                                    80mm
                                </button>
                            </div>
                        </div>

                        <!-- Error -->
                        <p v-if="error" class="text-xs text-destructive bg-destructive/10 rounded-lg px-3 py-2">
                            {{ error }}
                        </p>

                        <!-- Tombol aksi -->
                        <div class="flex gap-2 pt-1">
                            <button
                                class="flex-1 rounded-lg border border-border py-2 text-sm hover:bg-muted transition-colors"
                                @click="emit('close')"
                            >
                                Batal
                            </button>
                            <button
                                class="flex-1 rounded-lg bg-primary py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors disabled:opacity-50"
                                :disabled="!picked || loading"
                                @click="confirm"
                            >
                                🖨️ Cetak
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
