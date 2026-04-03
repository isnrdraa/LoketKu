<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Pengeluaran', href: '/expenses' },
            { title: 'Form Pengeluaran' },
        ],
    },
});

const props = defineProps<{
    categories: string[];
    expense?: {
        id: number;
        expense_date: string;
        category: string;
        description: string | null;
        amount: number;
    };
}>();

const isEdit = !!props.expense;

// Format tanggal hari ini untuk default
const today = new Date().toISOString().split('T')[0];

const form = useForm({
    expense_date: props.expense?.expense_date ?? today,
    category: props.expense?.category ?? '',
    description: props.expense?.description ?? '',
    amount: props.expense?.amount ?? '',
});

function submit() {
    if (isEdit) {
        form.put(`/expenses/${props.expense!.id}`);
    } else {
        form.post('/expenses');
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Pengeluaran' : 'Catat Pengeluaran'" />

    <div class="flex flex-1 flex-col gap-6 p-4 max-w-xl">
        <div class="flex items-center gap-3">
            <Link href="/expenses" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors">
                <ArrowLeft class="h-4 w-4" />
                Kembali
            </Link>
        </div>

        <div>
            <h1 class="text-2xl font-bold">{{ isEdit ? 'Edit Pengeluaran' : 'Catat Pengeluaran' }}</h1>
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <!-- Tanggal -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium" for="expense_date">Tanggal</label>
                <input
                    id="expense_date"
                    v-model="form.expense_date"
                    type="date"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                    required
                />
                <p v-if="form.errors.expense_date" class="text-xs text-destructive">{{ form.errors.expense_date }}</p>
            </div>

            <!-- Kategori -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium" for="category">Kategori</label>
                <select
                    id="category"
                    v-model="form.category"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                    required
                >
                    <option value="" disabled>-- Pilih Kategori --</option>
                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                </select>
                <p v-if="form.errors.category" class="text-xs text-destructive">{{ form.errors.category }}</p>
            </div>

            <!-- Keterangan -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium" for="description">Keterangan (opsional)</label>
                <input
                    id="description"
                    v-model="form.description"
                    type="text"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                    placeholder="Detail pengeluaran..."
                />
                <p v-if="form.errors.description" class="text-xs text-destructive">{{ form.errors.description }}</p>
            </div>

            <!-- Jumlah -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium" for="amount">Jumlah (Rp)</label>
                <input
                    id="amount"
                    v-model="form.amount"
                    type="number"
                    min="0"
                    step="1"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                    placeholder="0"
                    required
                />
                <p v-if="form.errors.amount" class="text-xs text-destructive">{{ form.errors.amount }}</p>
            </div>

            <div class="flex gap-3 pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors disabled:opacity-50"
                >
                    {{ form.processing ? 'Menyimpan...' : (isEdit ? 'Perbarui' : 'Simpan') }}
                </button>
                <Link href="/expenses" class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors">
                    Batal
                </Link>
            </div>
        </form>
    </div>
</template>
