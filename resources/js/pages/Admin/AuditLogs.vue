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
    auditLogs: {
        data: AuditLog[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
    };
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
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
}
</script>

<template>
    <Head title="Admin audit logs" />
    <main class="admin-page mx-auto w-full max-w-7xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p
                    class="text-muted-foreground flex items-center gap-2 text-sm font-medium"
                >
                    <ScrollText class="size-4" /> Operations
                </p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">
                    Audit logs
                </h1>
                <p class="text-muted-foreground mt-2 text-sm">
                    An append-only record of administrative changes.
                </p>
            </div>
            <Link
                href="/admin"
                class="inline-flex h-10 items-center gap-2 rounded-full border border-black/15 bg-white px-4 text-sm font-medium transition hover:bg-black hover:text-white focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2 focus-visible:outline-none"
                ><ArrowLeft class="size-4" /> Overview</Link
            >
        </header>

        <section
            v-if="auditLogs.data.length"
            class="audit-list overflow-hidden rounded-2xl border"
            aria-label="Administrative audit entries"
        >
            <article
                v-for="entry in auditLogs.data"
                :key="entry.id"
                class="audit-row grid gap-4 border-b border-black/10 p-5 last:border-b-0 sm:p-6 lg:grid-cols-[minmax(12rem,0.8fr)_minmax(16rem,1fr)_minmax(18rem,1.4fr)] lg:items-start"
            >
                <div class="min-w-0">
                    <h2 class="font-semibold">
                        {{ entry.action.replaceAll('.', ' ') }}
                    </h2>
                    <p class="mt-1 text-sm text-black/55">
                        {{ formatDate(entry.created_at) }}
                    </p>
                </div>
                <div class="text-sm">
                    <p class="font-medium">{{ entry.admin.name }}</p>
                    <p class="mt-1 break-all text-black/55">
                        {{ entry.admin.email }}
                    </p>
                    <p
                        v-if="entry.ip_address"
                        class="mt-1 text-xs text-black/50"
                    >
                        {{ entry.ip_address }}
                    </p>
                </div>
                <div class="min-w-0 text-sm">
                    <p class="font-medium">
                        {{ entry.target_type.split('\\').pop() }} #{{
                            entry.target_id
                        }}
                    </p>
                    <pre
                        v-if="entry.metadata"
                        class="mt-2 overflow-x-auto rounded-xl border border-black/10 bg-black/[0.03] p-3 text-xs leading-5 break-words whitespace-pre-wrap text-black/75"
                        >{{ JSON.stringify(entry.metadata, null, 2) }}</pre>
                </div>
            </article>
        </section>
        <p
            v-else
            class="rounded-2xl border border-dashed border-black/15 bg-white px-5 py-12 text-center text-sm text-black/55"
        >
            No administrative actions recorded yet.
        </p>

        <nav
            v-if="auditLogs.last_page > 1"
            class="flex flex-wrap justify-center gap-2"
            aria-label="Audit log pages"
        >
            <Link
                v-for="link in auditLogs.links"
                :key="link.label"
                :href="link.url ?? '/admin/audit-logs'"
                class="rounded-full border px-3 py-2 text-sm transition focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2 focus-visible:outline-none"
                :class="
                    link.active
                        ? 'border-black bg-black text-white'
                        : 'border-black/15 bg-white text-black hover:bg-black/[0.04]'
                "
                :aria-disabled="!link.url"
                :tabindex="link.url ? 0 : -1"
            >
                {{ link.label.replace('&laquo;', '‹').replace('&raquo;', '›') }}
            </Link>
        </nav>
    </main>
</template>

<style scoped>
.admin-page {
    color: #000;
    background: #fafafa;
}

.audit-list {
    border-color: #e5e5e5;
    background: #fff;
}

.audit-row:last-child {
    border-bottom: 0;
}
</style>
