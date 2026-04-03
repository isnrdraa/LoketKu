<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { Minus, Plus, ShoppingCart, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Transaksi Baru', href: '/transactions/create' },
        ],
    },
});

type Service = { id: number; name: string; price: number };
type Category = { id: number; name: string; services: Service[] };
type CartItem = { service_id: number; service_name: string; qty: number; unit_price: number };

defineProps<{ categories: Category[] }>();

// State keranjang
const cart = ref<CartItem[]>([]);
const paymentMethod = ref<'cash' | 'qris'>('cash');
const notes = ref('');
const processing = ref(false);
const errors = ref<Record<string, string>>({});

// Cari layanan di keranjang
function cartItem(serviceId: number) {
    return cart.value.find((c) => c.service_id === serviceId);
}

function qtyOf(serviceId: number): number {
    return cartItem(serviceId)?.qty ?? 0;
}

function addToCart(service: Service) {
    const existing = cartItem(service.id);

    if (existing) {
        existing.qty++;
    } else {
        cart.value.push({
            service_id: service.id,
            service_name: service.name,
            qty: 1,
            unit_price: service.price,
        });
    }
}

function decreaseQty(service: Service) {
    const existing = cartItem(service.id);

    if (!existing) {
return;
}

    if (existing.qty <= 1) {
        cart.value = cart.value.filter((c) => c.service_id !== service.id);
    } else {
        existing.qty--;
    }
}

function removeFromCart(serviceId: number) {
    cart.value = cart.value.filter((c) => c.service_id !== serviceId);
}

function setQty(serviceId: number, value: number) {
    const existing = cartItem(serviceId);

    if (!existing) {
return;
}

    if (value < 1) {
        cart.value = cart.value.filter((c) => c.service_id !== serviceId);
    } else {
        existing.qty = value;
    }
}

const subtotal = computed(() =>
    cart.value.reduce((sum, item) => sum + item.qty * item.unit_price, 0),
);

function formatRupiah(value: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

function clearCart() {
    cart.value = [];
    paymentMethod.value = 'cash';
    notes.value = '';
    errors.value = {};
}

function submitTransaction() {
    if (cart.value.length === 0) {
        errors.value = { items: 'Pilih minimal satu layanan.' };

        return;
    }

    processing.value = true;
    errors.value = {};

    router.post(
        '/transactions',
        {
            payment_method: paymentMethod.value,
            notes: notes.value || null,
            items: cart.value,
        },
        {
            onError: (e) => {
                errors.value = e;
                processing.value = false;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Transaksi Baru" />

    <div class="flex h-full gap-4 p-4 lg:flex-row flex-col">
        <!-- Kiri: Daftar Layanan -->
        <div class="flex-1 overflow-y-auto space-y-4">
            <div v-if="categories.length === 0" class="text-muted-foreground text-center py-16">
                Belum ada layanan aktif. Tambahkan melalui panel Admin.
            </div>

            <div v-for="cat in categories" :key="cat.id">
                <h2 class="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-2 px-1">
                    {{ cat.name }}
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                    <button
                        v-for="service in cat.services"
                        :key="service.id"
                        type="button"
                        class="relative flex flex-col items-start rounded-xl border p-4 text-left transition-all hover:border-primary hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-primary"
                        :class="qtyOf(service.id) > 0 ? 'border-primary bg-primary/5' : 'border-border bg-card'"
                        @click="addToCart(service)"
                    >
                        <!-- Badge qty -->
                        <span
                            v-if="qtyOf(service.id) > 0"
                            class="absolute top-2 right-2 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[11px] font-bold text-primary-foreground"
                        >
                            {{ qtyOf(service.id) }}
                        </span>
                        <span class="font-medium leading-tight">{{ service.name }}</span>
                        <span class="mt-1 text-sm text-muted-foreground">
                            {{ formatRupiah(service.price) }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Kanan: Keranjang -->
        <div class="w-full lg:w-80 shrink-0">
            <Card class="sticky top-4">
                <CardHeader class="pb-2">
                    <CardTitle class="flex items-center gap-2 text-base">
                        <ShoppingCart class="h-5 w-5" />
                        Keranjang
                        <span
                            v-if="cart.length"
                            class="ml-auto text-sm font-normal text-muted-foreground"
                        >
                            {{ cart.length }} item
                        </span>
                    </CardTitle>
                </CardHeader>

                <CardContent class="space-y-4">
                    <!-- Empty state -->
                    <div
                        v-if="cart.length === 0"
                        class="py-8 text-center text-sm text-muted-foreground"
                    >
                        Belum ada layanan dipilih
                    </div>

                    <!-- Item keranjang -->
                    <div v-else class="space-y-3 max-h-64 overflow-y-auto pr-1">
                        <div
                            v-for="item in cart"
                            :key="item.service_id"
                            class="flex items-center gap-2"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="truncate text-sm font-medium">{{ item.service_name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatRupiah(item.unit_price) }}
                                </p>
                            </div>
                            <!-- Kontrol qty -->
                            <div class="flex items-center gap-1 shrink-0">
                                <button
                                    type="button"
                                    class="flex h-6 w-6 items-center justify-center rounded-md border hover:bg-muted"
                                    @click="decreaseQty({ id: item.service_id, name: item.service_name, price: item.unit_price })"
                                >
                                    <Minus class="h-3 w-3" />
                                </button>
                                <input
                                    type="number"
                                    :value="item.qty"
                                    min="1"
                                    class="w-10 rounded-md border text-center text-sm py-0.5 focus:outline-none focus:ring-1 focus:ring-primary"
                                    @change="setQty(item.service_id, Number(($event.target as HTMLInputElement).value))"
                                />
                                <button
                                    type="button"
                                    class="flex h-6 w-6 items-center justify-center rounded-md border hover:bg-muted"
                                    @click="addToCart({ id: item.service_id, name: item.service_name, price: item.unit_price })"
                                >
                                    <Plus class="h-3 w-3" />
                                </button>
                                <button
                                    type="button"
                                    class="flex h-6 w-6 items-center justify-center rounded-md text-destructive hover:bg-destructive/10"
                                    @click="removeFromCart(item.service_id)"
                                >
                                    <Trash2 class="h-3 w-3" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <Separator v-if="cart.length" />

                    <!-- Subtotal -->
                    <div v-if="cart.length" class="flex items-center justify-between text-sm font-semibold">
                        <span>Total</span>
                        <span class="text-base">{{ formatRupiah(subtotal) }}</span>
                    </div>

                    <!-- Metode Pembayaran -->
                    <div class="space-y-2">
                        <Label class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                            Metode Bayar
                        </Label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                class="rounded-lg border px-3 py-2 text-sm font-medium transition-all"
                                :class="paymentMethod === 'cash' ? 'border-primary bg-primary text-primary-foreground' : 'border-border hover:border-primary/50'"
                                @click="paymentMethod = 'cash'"
                            >
                                💵 Cash
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border px-3 py-2 text-sm font-medium transition-all"
                                :class="paymentMethod === 'qris' ? 'border-primary bg-primary text-primary-foreground' : 'border-border hover:border-primary/50'"
                                @click="paymentMethod = 'qris'"
                            >
                                📲 QRIS
                            </button>
                        </div>
                    </div>

                    <!-- Catatan opsional -->
                    <div class="space-y-1">
                        <Label for="notes" class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                            Catatan (opsional)
                        </Label>
                        <textarea
                            id="notes"
                            v-model="notes"
                            rows="2"
                            class="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary resize-none"
                            placeholder="Catatan transaksi..."
                        />
                    </div>

                    <!-- Error -->
                    <p v-if="errors.items" class="text-sm text-destructive">{{ errors.items }}</p>

                    <!-- Tombol aksi -->
                    <div class="flex gap-2 pt-1">
                        <Button
                            variant="outline"
                            class="flex-1"
                            type="button"
                            :disabled="processing || cart.length === 0"
                            @click="clearCart"
                        >
                            Kosongkan
                        </Button>
                        <Button
                            class="flex-1"
                            type="button"
                            :disabled="processing || cart.length === 0"
                            @click="submitTransaction"
                        >
                            {{ processing ? 'Menyimpan...' : 'Simpan' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
