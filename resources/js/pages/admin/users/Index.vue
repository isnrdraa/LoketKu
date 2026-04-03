<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PencilLine, Plus, Trash2 } from 'lucide-vue-next';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Manajemen User', href: '/users' },
        ],
    },
});

defineProps<{
    users: Array<{
        id: number;
        name: string;
        username: string;
        role: 'admin' | 'cashier';
        is_active: boolean;
        created_at: string;
    }>;
}>();

function confirmDelete(id: number, name: string) {
    if (confirm(`Hapus user "${name}"? Tindakan ini tidak bisa dibatalkan.`)) {
        router.delete(`/users/${id}`);
    }
}
</script>

<template>
    <Head title="Manajemen User" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Manajemen User</h1>
                <p class="text-sm text-muted-foreground">Kelola akun admin dan kasir</p>
            </div>
            <Link
                href="/users/create"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
            >
                <Plus class="h-4 w-4" />
                Tambah User
            </Link>
        </div>

        <!-- Table -->
        <div class="rounded-xl border border-border overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Nama</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Username</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Role</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-if="users.length === 0"
                        class="border-t border-border"
                    >
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                            Belum ada user.
                        </td>
                    </tr>
                    <tr
                        v-for="user in users"
                        :key="user.id"
                        class="border-t border-border hover:bg-muted/30 transition-colors"
                    >
                        <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ user.username }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="user.role === 'admin'
                                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                    : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'"
                            >
                                {{ user.role === 'admin' ? 'Administrator' : 'Kasir' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="user.is_active
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                            >
                                {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Link
                                    :href="`/users/${user.id}/edit`"
                                    class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium border border-border hover:bg-muted transition-colors"
                                >
                                    <PencilLine class="h-3.5 w-3.5" />
                                    Edit
                                </Link>
                                <button
                                    class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium border border-destructive/30 text-destructive hover:bg-destructive/10 transition-colors"
                                    @click="confirmDelete(user.id, user.name)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
