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
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
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
