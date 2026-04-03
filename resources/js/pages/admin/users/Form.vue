<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Manajemen User', href: '/users' },
            { title: 'Form User' },
        ],
    },
});

const props = defineProps<{
    user?: {
        id: number;
        name: string;
        username: string;
        role: 'admin' | 'cashier';
        is_active: boolean;
    };
}>();

const isEdit = !!props.user;

const form = useForm({
    name: props.user?.name ?? '',
    username: props.user?.username ?? '',
    password: '',
    password_confirmation: '',
    role: props.user?.role ?? 'cashier',
    is_active: props.user?.is_active ?? true,
});

function submit() {
    if (isEdit) {
        form.put(`/users/${props.user!.id}`);
    } else {
        form.post('/users');
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit User' : 'Tambah User'" />

    <div class="flex flex-1 flex-col gap-6 p-4 max-w-xl">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Link
                href="/users"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors"
            >
                <ArrowLeft class="h-4 w-4" />
                Kembali
            </Link>
        </div>

        <div>
            <h1 class="text-2xl font-bold">{{ isEdit ? 'Edit User' : 'Tambah User' }}</h1>
            <p class="text-sm text-muted-foreground">
                {{ isEdit ? 'Perbarui informasi user' : 'Buat akun admin atau kasir baru' }}
            </p>
        </div>

        <!-- Form -->
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <!-- Nama -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium" for="name">Nama Lengkap</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                    placeholder="Nama lengkap"
                    required
                />
                <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
            </div>

            <!-- Username -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium" for="username">Username</label>
                <input
                    id="username"
                    v-model="form.username"
                    type="text"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                    placeholder="username"
                    required
                />
                <p v-if="form.errors.username" class="text-xs text-destructive">{{ form.errors.username }}</p>
            </div>

            <!-- Password -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium" for="password">
                    Password
                    <span v-if="isEdit" class="text-muted-foreground font-normal">(kosongkan jika tidak diubah)</span>
                </label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                    placeholder="••••••••"
                    :required="!isEdit"
                />
                <p v-if="form.errors.password" class="text-xs text-destructive">{{ form.errors.password }}</p>
            </div>

            <!-- Password Confirmation -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium" for="password_confirmation">Konfirmasi Password</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                    placeholder="••••••••"
                    :required="!isEdit"
                />
            </div>

            <!-- Role -->
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium">Role</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.role"
                            type="radio"
                            value="cashier"
                            class="accent-primary"
                        />
                        <span class="text-sm">Kasir</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.role"
                            type="radio"
                            value="admin"
                            class="accent-primary"
                        />
                        <span class="text-sm">Administrator</span>
                    </label>
                </div>
                <p v-if="form.errors.role" class="text-xs text-destructive">{{ form.errors.role }}</p>
            </div>

            <!-- Status -->
            <div class="flex items-center gap-2">
                <input
                    id="is_active"
                    v-model="form.is_active"
                    type="checkbox"
                    class="h-4 w-4 rounded border-input accent-primary"
                />
                <label class="text-sm font-medium cursor-pointer" for="is_active">Akun Aktif</label>
            </div>

            <!-- Submit -->
            <div class="flex gap-3 pt-2">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors disabled:opacity-50"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Menyimpan...' : (isEdit ? 'Perbarui' : 'Simpan') }}
                </button>
                <Link
                    href="/users"
                    class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors"
                >
                    Batal
                </Link>
            </div>
        </form>
    </div>
</template>
