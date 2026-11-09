<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    CreditCard,
    FileClock,
    History,
    LayoutGrid,
    MessagesSquare,
    Package,
    ScrollText,
    ShieldCheck,
    Sparkles,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
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
import type { NavItem } from '@/types';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Custom tones',
        href: '/tones',
        icon: MessagesSquare,
    },
    {
        title: 'Brand profiles',
        href: '/brands',
        icon: Building2,
    },
    {
        title: 'Generate content',
        href: '/generate',
        icon: Sparkles,
    },
    {
        title: 'Generation history',
        href: '/generations',
        icon: FileClock,
    },
    {
        title: 'Billing',
        href: '/billing',
        icon: CreditCard,
    },
]);

const adminNavItems: NavItem[] = [
    { title: 'Overview', href: '/admin', icon: ShieldCheck },
    { title: 'Users', href: '/admin/users', icon: Users },
    { title: 'Transactions', href: '/admin/transactions', icon: History },
    { title: 'Credit packages', href: '/admin/credit-packages', icon: Package },
    { title: 'Audit logs', href: '/admin/audit-logs', icon: ScrollText },
];
</script>

<template>
    <Sidebar
        collapsible="icon"
        variant="inset"
        class="workspace-sidebar border-r"
    >
        <SidebarHeader class="border-sidebar-border/70 border-b px-3 py-4">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        class="hover:bg-sidebar-accent/70 h-auto rounded-xl px-2 py-2"
                    >
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="gap-1 px-2 py-4">
            <NavMain label="Workspace" :items="mainNavItems" />
            <NavMain
                v-if="page.props.auth.user.role === 'admin'"
                label="Administration"
                :items="adminNavItems"
            />
        </SidebarContent>

        <SidebarFooter class="border-sidebar-border/70 border-t px-3 py-3">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

<style scoped>
.workspace-sidebar {
    --sidebar: #000;
    --sidebar-background: #000;
    --sidebar-foreground: #fff;
    --sidebar-primary: #fff;
    --sidebar-primary-foreground: #000;
    --sidebar-accent: #1d1d1d;
    --sidebar-accent-foreground: #fff;
    --sidebar-border: #262626;
    --sidebar-ring: #a3a3a3;
    --muted-foreground: #a3a3a3;
}

.workspace-sidebar :deep([data-slot='sidebar']) {
    color: #fff;
    background: #000;
}

.workspace-sidebar :deep([data-slot='sidebar-menu-button']) {
    color: #ccc;
}

.workspace-sidebar :deep([data-slot='sidebar-menu-button']:hover),
.workspace-sidebar
    :deep([data-slot='sidebar-menu-button'][data-active='true']) {
    color: #fff;
}

.workspace-sidebar
    :deep([data-slot='sidebar-menu-button'][data-active='true']) {
    background: #202020;
}

.workspace-sidebar :deep([data-slot='sidebar-group-label']) {
    color: #888;
}

:global([data-mobile='true'][data-sidebar='sidebar']) {
    --sidebar: #000;
    --sidebar-background: #000;
    --sidebar-foreground: #fff;
    --sidebar-primary: #fff;
    --sidebar-primary-foreground: #000;
    --sidebar-accent: #1d1d1d;
    --sidebar-accent-foreground: #fff;
    --sidebar-border: #262626;
    --sidebar-ring: #a3a3a3;
    color: #fff;
    background: #000;
}
</style>
