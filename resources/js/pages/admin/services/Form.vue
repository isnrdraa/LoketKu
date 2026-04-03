<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Layanan', href: '/services' },
            { title: 'Form Layanan' },
        ],
    },
});

const props = defineProps<{
    categories: Array<{ id: number; name: string }>;
    service?: {
        id: number;
        service_category_id: number;
        name: string;
        price: number;
        is_active: boolean;
    };
}>();

const isEdit = !!props.service;

const form = useForm({
    service_category_id: props.service?.service_category_id ?? '',
    name: props.service?.name ?? '',
    price: props.service?.price ?? '',
    is_active: props.service?.is_active ?? true,
});

function submit() {
    if (isEdit) {
        form.put(`/services/${props.service!.id}`);
    } else {
        form.post('/services');
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Layanan' : 'Tambah Layanan'" />

    <div class="flex flex-1 flex-col gap-6 p-4 max-w-xl">
        <div class="flex items-center gap-3">
            <Link href="/services" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors">
                <ArrowLeft class="h-4 w-4" />
                Kembali
            </Link>
        </div>

        <div>
            <h1 class="text-2xl font-bold">{{ isEdit ? 'Edit Layanan' : 'Tambah Layanan' }}</h1>
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <!-- Kategori -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium" for="service_category_id">Kategori</label>
                <select
                    id="service_category_id"
                    v-model="form.service_category_id"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                    required
                >
                    <option value="" disabled>-- Pilih Kategori --</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
                <p v-if="form.errors.service_category_id" class="text-xs text-destructive">{{ form.errors.service_category_id }}</p>
            </div>

            <!-- Nama Layanan -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium" for="name">Nama Layanan</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                    placeholder="Misal: Tiket Dewasa"
                    required
                />
                <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
            </div>

            <!-- Harga -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium" for="price">Harga (Rp)</label>
                <input
                    id="price"
                    v-model="form.price"
                    type="number"
                    min="0"
                    step="1"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                    placeholder="0"
                    required
                />
                <p v-if="form.errors.price" class="text-xs text-destructive">{{ form.errors.price }}</p>
            </div>

            <!-- Status -->
            <div class="flex items-center gap-2">
                <input id="is_active" v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-input accent-primary" />
                <label class="text-sm font-medium cursor-pointer" for="is_active">Layanan Aktif</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors disabled:opacity-50"
                >
                    {{ form.processing ? 'Menyimpan...' : (isEdit ? 'Perbarui' : 'Simpan') }}
                </button>
                <Link href="/services" class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors">
                    Batal
                </Link>
            </div>
        </form>
    </div>
</template>
