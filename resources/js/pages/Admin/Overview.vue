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
    <main class="mx-auto w-full max-w-7xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                    <ShieldCheck class="size-4" />
                    Operations
                </p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">Admin overview</h1>
                <p class="mt-2 text-sm text-muted-foreground">Account status, credit usage, and recent administrative activity.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <Link href="/admin/users" class="inline-flex h-10 items-center gap-2 rounded-md border px-4 text-sm font-medium hover:bg-muted">
                    Manage users <ArrowRight class="size-4" />
                </Link>
                <Link href="/admin/audit-logs" class="inline-flex h-10 items-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90">
                    Audit logs <ArrowRight class="size-4" />
                </Link>
            </div>
        </header>

        <section class="grid gap-5 border-y py-6 sm:grid-cols-2 xl:grid-cols-3">
            <div>
                <p class="text-sm text-muted-foreground">User accounts</p>
                <p class="mt-2 text-3xl font-semibold tabular-nums">{{ metrics.users }}</p>
                <p class="mt-1 text-sm text-muted-foreground">{{ metrics.activeUsers }} active · {{ metrics.suspendedUsers }} suspended</p>
            </div>
            <div>
                <p class="text-sm text-muted-foreground">Credits in circulation</p>
                <p class="mt-2 text-3xl font-semibold tabular-nums">{{ metrics.creditsInCirculation }}</p>
                <p class="mt-1 text-sm text-muted-foreground">Across all accounts</p>
            </div>
            <div>
                <p class="text-sm text-muted-foreground">Generation usage</p>
                <p class="mt-2 text-3xl font-semibold tabular-nums">{{ metrics.generations }}</p>
                <p class="mt-1 text-sm text-muted-foreground">{{ metrics.creditsUsed }} credits consumed</p>
            </div>
        </section>

        <section class="grid gap-10 xl:grid-cols-2">
            <div>
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold">Recent credit activity</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Latest ledger entries across all accounts.</p>
                    </div>
                    <Link href="/admin/transactions" class="text-sm font-medium underline underline-offset-4">All transactions</Link>
                </div>
                <div v-if="recentTransactions.length" class="divide-y border-y">
                    <article v-for="transaction in recentTransactions" :key="transaction.id" class="flex flex-wrap items-center justify-between gap-3 py-4">
                        <div class="min-w-0">
                            <p class="font-medium">{{ transaction.description || transaction.type.replaceAll('_', ' ') }}</p>
                            <p class="mt-1 truncate text-sm text-muted-foreground">{{ transaction.user.name }} · {{ formatDate(transaction.created_at) }}</p>
                        </div>
                        <p class="font-semibold tabular-nums" :class="transaction.amount > 0 ? 'text-emerald-700 dark:text-emerald-400' : ''">
                            {{ transaction.amount > 0 ? '+' : '' }}{{ transaction.amount }}
                        </p>
                    </article>
                </div>
                <p v-else class="border-y py-8 text-sm text-muted-foreground">No credit activity yet.</p>
            </div>

            <div>
                <div class="mb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold">Administrative activity</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Recent account and credit actions.</p>
                    </div>
                    <Link href="/admin/audit-logs" class="text-sm font-medium underline underline-offset-4">Full audit trail</Link>
                </div>
                <div v-if="recentAuditLogs.length" class="divide-y border-y">
                    <article v-for="entry in recentAuditLogs" :key="entry.id" class="py-4">
                        <p class="font-medium">{{ entry.action.replaceAll('.', ' ') }}</p>
                        <p class="mt-1 text-sm text-muted-foreground">{{ entry.admin.name }} · {{ entry.target_type.split('\\').pop() }} #{{ entry.target_id }} · {{ formatDate(entry.created_at) }}</p>
                    </article>
                </div>
                <p v-else class="border-y py-8 text-sm text-muted-foreground">No administrative actions recorded yet.</p>
            </div>
        </section>
    </main>
</template>