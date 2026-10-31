<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ScrollText } from '@lucide/vue';

type AuditLog = {
    id: number;
    action: string;
    target_type: string;
    target_id: number;
    metadata: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string;
    admin: { name: string; email: string };
};

type PaginationLink = { url: string | null; label: string; active: boolean };

defineProps<{
    auditLogs: { data: AuditLog[]; links: PaginationLink[]; current_page: number; last_page: number };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin overview', href: '/admin' },
            { title: 'Audit logs', href: '/admin/audit-logs' },
        ],
    },
});

function formatDate(date: string): string {
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(date));
}
</script>

<template>
    <Head title="Admin audit logs" />
    <main class="mx-auto w-full max-w-7xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="flex items-center gap-2 text-sm font-medium text-muted-foreground"><ScrollText class="size-4" /> Operations</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">Audit logs</h1>
                <p class="mt-2 text-sm text-muted-foreground">An append-only record of administrative changes.</p>
            </div>
            <Link href="/admin" class="inline-flex h-10 items-center gap-2 rounded-md border px-4 text-sm font-medium hover:bg-muted"><ArrowLeft class="size-4" /> Overview</Link>
        </header>

        <section v-if="auditLogs.data.length" class="divide-y border-y">
            <article v-for="entry in auditLogs.data" :key="entry.id" class="grid gap-3 py-5 lg:grid-cols-[minmax(12rem,0.8fr)_minmax(16rem,1fr)_minmax(18rem,1.4fr)] lg:items-start">
                <div>
                    <h2 class="font-semibold">{{ entry.action.replaceAll('.', ' ') }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">{{ formatDate(entry.created_at) }}</p>
                </div>
                <div class="text-sm">
                    <p class="font-medium">{{ entry.admin.name }}</p>
                    <p class="mt-1 text-muted-foreground">{{ entry.admin.email }}</p>
                    <p v-if="entry.ip_address" class="mt-1 text-xs text-muted-foreground">{{ entry.ip_address }}</p>
                </div>
                <div class="min-w-0 text-sm">
                    <p class="font-medium">{{ entry.target_type.split('\\').pop() }} #{{ entry.target_id }}</p>
                    <pre v-if="entry.metadata" class="mt-2 overflow-x-auto whitespace-pre-wrap break-words rounded-md border bg-muted/40 p-3 text-xs leading-5">{{ JSON.stringify(entry.metadata, null, 2) }}</pre>
                </div>
            </article>
        </section>
        <p v-else class="border-y py-10 text-center text-sm text-muted-foreground">No administrative actions recorded yet.</p>

        <nav v-if="auditLogs.last_page > 1" class="flex flex-wrap justify-center gap-2" aria-label="Audit log pages">
            <Link v-for="link in auditLogs.links" :key="link.label" :href="link.url ?? '/admin/audit-logs'" class="rounded-md border px-3 py-2 text-sm" :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'" :aria-disabled="!link.url" :tabindex="link.url ? 0 : -1">
                {{ link.label.replace('&laquo;', '‹').replace('&raquo;', '›') }}
            </Link>
        </nav>
    </main>
</template>