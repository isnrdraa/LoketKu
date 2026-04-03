<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    LayoutGrid,
    Receipt,
    Settings,
    ShoppingBag,
    Store,
    Tag,
    Users,
    Wallet,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

const page = usePage<{ auth: Auth }>();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value?.role === 'admin');

// ─── Menu utama (semua role) ─────────────────────────────────────────────
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Transaksi Baru',
        href: '/transactions/create',
        icon: ShoppingBag,
    },
    {
        title: 'Riwayat Transaksi',
        href: '/transactions',
        icon: Receipt,
    },
];

// ─── Menu admin ──────────────────────────────────────────────────────────
const adminNavItems: NavItem[] = [
    {
        title: 'Laporan',
        href: '/reports',
        icon: BarChart3,
    },
    {
        title: 'Pengeluaran',
        href: '/expenses',
        icon: Wallet,
    },
    {
        title: 'Kategori Layanan',
        href: '/service-categories',
        icon: Tag,
    },
    {
        title: 'Layanan',
        href: '/services',
        icon: Settings,
    },
    {
        title: 'Pengguna',
        href: '/users',
        icon: Users,
    },
    {
        title: 'Pengaturan Toko',
        href: '/store-settings',
        icon: Store,
    },
];

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Menu" />
            <NavMain v-if="isAdmin" :items="adminNavItems" label="Admin" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
