<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import { usePage } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent
            variant="sidebar"
            class="workspace-content min-w-0 overflow-x-clip"
        >
            <AppSidebarHeader
                v-if="page.component !== 'Dashboard'"
                :breadcrumbs="breadcrumbs"
            />
            <AppSidebarHeader
                v-else
                class="md:hidden"
                :breadcrumbs="breadcrumbs"
            />
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>

<style scoped>
.workspace-content {
    color: #111;
    color-scheme: light;
    background: #fafafa;
    --background: hsl(0 0% 98%);
    --foreground: hsl(0 0% 7%);
    --card: hsl(0 0% 100%);
    --card-foreground: hsl(0 0% 7%);
    --popover: hsl(0 0% 100%);
    --popover-foreground: hsl(0 0% 7%);
    --primary: hsl(0 0% 9%);
    --primary-foreground: hsl(0 0% 98%);
    --secondary: hsl(0 0% 94%);
    --secondary-foreground: hsl(0 0% 10%);
    --muted: hsl(0 0% 95%);
    --muted-foreground: hsl(0 0% 38%);
    --accent: hsl(0 0% 94%);
    --accent-foreground: hsl(0 0% 10%);
    --border: hsl(0 0% 88%);
    --input: hsl(0 0% 82%);
    --ring: hsl(0 0% 12%);
}
</style>
