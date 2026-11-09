<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, ShieldCheck } from '@lucide/vue';

type Metrics = {
    users: number;
    activeUsers: number;
    suspendedUsers: number;
    creditsInCirculation: number;
    generations: number;
    creditsUsed: number;
};

type Transaction = {
    id: number;
    amount: number;
    type: string;
    description: string | null;
    created_at: string;
    user: { name: string; email: string };
};

type AuditLog = {
    id: number;
    action: string;
    target_type: string;
    target_id: number;
    created_at: string;
    admin: { name: string; email: string };
};

defineProps<{
    metrics: Metrics;
    recentTransactions: Transaction[];
    recentAuditLogs: AuditLog[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Admin overview', href: '/admin' }],
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
    <Head title="Admin overview" />
    <main class="admin-page mx-auto w-full max-w-7xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p
                    class="text-muted-foreground flex items-center gap-2 text-sm font-medium"
                >
                    <ShieldCheck class="size-4" />
                    Operations
                </p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">
                    Admin overview
                </h1>
                <p class="text-muted-foreground mt-2 text-sm">
                    Account status, credit usage, and recent administrative
                    activity.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <Link
                    href="/admin/users"
                    class="inline-flex h-10 items-center gap-2 rounded-full border border-black/15 bg-white px-4 text-sm font-medium transition hover:bg-black hover:text-white focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    Manage users <ArrowRight class="size-4" />
                </Link>
                <Link
                    href="/admin/audit-logs"
                    class="inline-flex h-10 items-center gap-2 rounded-full bg-black px-4 text-sm font-medium text-white transition hover:bg-neutral-800 focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    Audit logs <ArrowRight class="size-4" />
                </Link>
            </div>
        </header>

        <section
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
            aria-label="Administration metrics"
        >
            <article class="metric-card rounded-2xl border p-5 sm:p-6">
                <p
                    class="text-xs font-semibold tracking-[0.14em] text-black/50 uppercase"
                >
                    User accounts
                </p>
                <p
                    class="mt-3 text-3xl font-semibold tracking-tight tabular-nums"
                >
                    {{ metrics.users }}
                </p>
                <p class="mt-2 text-sm text-black/55">
                    {{ metrics.activeUsers }} active ·
                    {{ metrics.suspendedUsers }} suspended
                </p>
            </article>
            <article class="metric-card rounded-2xl border p-5 sm:p-6">
                <p
                    class="text-xs font-semibold tracking-[0.14em] text-black/50 uppercase"
                >
                    Credits in circulation
                </p>
                <p
                    class="mt-3 text-3xl font-semibold tracking-tight tabular-nums"
                >
                    {{ metrics.creditsInCirculation }}
                </p>
                <p class="mt-2 text-sm text-black/55">Across all accounts</p>
            </article>
            <article class="metric-card rounded-2xl border p-5 sm:p-6">
                <p
                    class="text-xs font-semibold tracking-[0.14em] text-black/50 uppercase"
                >
                    Generation usage
                </p>
                <p
                    class="mt-3 text-3xl font-semibold tracking-tight tabular-nums"
                >
                    {{ metrics.generations }}
                </p>
                <p class="mt-2 text-sm text-black/55">
                    {{ metrics.creditsUsed }} credits consumed
                </p>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-2">
            <article class="activity-card rounded-2xl border p-5 sm:p-6">
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold">
                            Recent credit activity
                        </h2>
                        <p class="mt-1 text-sm text-black/55">
                            Latest ledger entries across all accounts.
                        </p>
                    </div>
                    <Link
                        href="/admin/transactions"
                        class="shrink-0 text-sm font-semibold underline decoration-black/30 underline-offset-4 transition hover:decoration-black focus-visible:ring-2 focus-visible:ring-black focus-visible:outline-none"
                        >All transactions</Link
                    >
                </div>
                <div
                    v-if="recentTransactions.length"
                    class="divide-y divide-black/10"
                >
                    <article
                        v-for="transaction in recentTransactions"
                        :key="transaction.id"
                        class="flex flex-wrap items-center justify-between gap-3 py-4 first:pt-0 last:pb-0"
                    >
                        <div class="min-w-0">
                            <p class="font-medium">
                                {{
                                    transaction.description ||
                                    transaction.type.replaceAll('_', ' ')
                                }}
                            </p>
                            <p class="mt-1 truncate text-sm text-black/55">
                                {{ transaction.user.name }} ·
                                {{ formatDate(transaction.created_at) }}
                            </p>
                        </div>
                        <p class="font-semibold tabular-nums">
                            {{ transaction.amount > 0 ? '+' : ''
                            }}{{ transaction.amount }}
                        </p>
                    </article>
                </div>
                <p
                    v-else
                    class="rounded-xl border border-dashed border-black/15 px-4 py-8 text-center text-sm text-black/55"
                >
                    No credit activity yet.
                </p>
            </article>

            <article class="activity-card rounded-2xl border p-5 sm:p-6">
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold">
                            Administrative activity
                        </h2>
                        <p class="mt-1 text-sm text-black/55">
                            Recent account and credit actions.
                        </p>
                    </div>
                    <Link
                        href="/admin/audit-logs"
                        class="shrink-0 text-sm font-semibold underline decoration-black/30 underline-offset-4 transition hover:decoration-black focus-visible:ring-2 focus-visible:ring-black focus-visible:outline-none"
                        >Full audit trail</Link
                    >
                </div>
                <div
                    v-if="recentAuditLogs.length"
                    class="divide-y divide-black/10"
                >
                    <article
                        v-for="entry in recentAuditLogs"
                        :key="entry.id"
                        class="py-4 first:pt-0 last:pb-0"
                    >
                        <p class="font-medium">
                            {{ entry.action.replaceAll('.', ' ') }}
                        </p>
                        <p class="mt-1 text-sm text-black/55">
                            {{ entry.admin.name }} ·
                            {{ entry.target_type.split('\\').pop() }} #{{
                                entry.target_id
                            }}
                            · {{ formatDate(entry.created_at) }}
                        </p>
                    </article>
                </div>
                <p
                    v-else
                    class="rounded-xl border border-dashed border-black/15 px-4 py-8 text-center text-sm text-black/55"
                >
                    No administrative actions recorded yet.
                </p>
            </article>
        </section>
    </main>
</template>

<style scoped>
.admin-page {
    color: #000;
    background: #fafafa;
}

.metric-card,
.activity-card {
    border-color: #e5e5e5;
    background: #fff;
}
</style>
