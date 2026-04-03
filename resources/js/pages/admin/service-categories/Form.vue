<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Kategori Layanan', href: '/service-categories' },
            { title: 'Form Kategori' },
        ],
    },
});

const props = defineProps<{
    category?: {
        id: number;
        name: string;
        description: string | null;
        is_active: boolean;
    };
}>();

const isEdit = !!props.category;

const form = useForm({
    name: props.category?.name ?? '',
    description: props.category?.description ?? '',
    is_active: props.category?.is_active ?? true,
});

function submit() {
    if (isEdit) {
        form.put(`/service-categories/${props.category!.id}`);
    } else {
        form.post('/service-categories');
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Kategori' : 'Tambah Kategori'" />

    <div class="flex flex-1 flex-col gap-6 p-4 max-w-xl">
        <div class="flex items-center gap-3">
            <Link href="/service-categories" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors">
                <ArrowLeft class="h-4 w-4" />
                Kembali
            </Link>
        </div>

        <div>
            <h1 class="text-2xl font-bold">{{ isEdit ? 'Edit Kategori' : 'Tambah Kategori' }}</h1>
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium" for="name">Nama Kategori</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                    placeholder="Misal: Tiket Masuk"
                    required
                />
                <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
            </div>

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium" for="description">Deskripsi (opsional)</label>
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                    placeholder="Keterangan singkat..."
                />
                <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
            </div>

            <div class="flex items-center gap-2">
                <input id="is_active" v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-input accent-primary" />
                <label class="text-sm font-medium cursor-pointer" for="is_active">Kategori Aktif</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors disabled:opacity-50"
                >
                    {{ form.processing ? 'Menyimpan...' : (isEdit ? 'Perbarui' : 'Simpan') }}
                </button>
                <Link href="/service-categories" class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors">
                    Batal
                </Link>
            </div>
        </form>
    </div>
</template>
