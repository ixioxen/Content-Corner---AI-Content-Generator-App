<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, History } from '@lucide/vue';

type Transaction = {
    id: number;
    amount: number;
    type: string;
    description: string | null;
    created_at: string;
    user: { name: string; email: string };
    created_by_admin: { name: string; email: string } | null;
};

type PaginationLink = { url: string | null; label: string; active: boolean };

defineProps<{
    transactions: { data: Transaction[]; links: PaginationLink[]; current_page: number; last_page: number };
    usage: { generationCount: number; creditsUsed: number };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin overview', href: '/admin' },
            { title: 'Transactions', href: '/admin/transactions' },
        ],
    },
});

function formatDate(date: string): string {
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(date));
}
</script>

<template>
    <Head title="Transactions and usage" />
    <main class="mx-auto w-full max-w-7xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="flex items-center gap-2 text-sm font-medium text-muted-foreground"><History class="size-4" /> Operations</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">Transactions and usage</h1>
                <p class="mt-2 text-sm text-muted-foreground">The credit ledger is the source of truth for balances and generation usage.</p>
            </div>
            <Link href="/admin" class="inline-flex h-10 items-center gap-2 rounded-md border px-4 text-sm font-medium hover:bg-muted"><ArrowLeft class="size-4" /> Overview</Link>
        </header>

        <section class="grid gap-6 border-y py-6 sm:grid-cols-2">
            <div>
                <p class="text-sm text-muted-foreground">Successful generation charges</p>
                <p class="mt-2 text-3xl font-semibold tabular-nums">{{ usage.generationCount }}</p>
            </div>
            <div>
                <p class="text-sm text-muted-foreground">Generation credits consumed</p>
                <p class="mt-2 text-3xl font-semibold tabular-nums">{{ usage.creditsUsed }}</p>
            </div>
        </section>

        <section>
            <div class="mb-4">
                <h2 class="text-xl font-semibold">All credit transactions</h2>
                <p class="mt-1 text-sm text-muted-foreground">Entries are append-only and include both user and admin activity.</p>
            </div>
            <div v-if="transactions.data.length" class="divide-y border-y">
                <article v-for="transaction in transactions.data" :key="transaction.id" class="grid gap-2 py-4 sm:grid-cols-[minmax(12rem,1fr)_auto] sm:items-center">
                    <div class="min-w-0">
                        <p class="font-medium">{{ transaction.description || transaction.type.replaceAll('_', ' ') }}</p>
                        <p class="mt-1 truncate text-sm text-muted-foreground">{{ transaction.user.name }} · {{ transaction.user.email }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">{{ transaction.type.replaceAll('_', ' ') }}<span v-if="transaction.created_by_admin"> · adjusted by {{ transaction.created_by_admin.name }}</span> · {{ formatDate(transaction.created_at) }}</p>
                    </div>
                    <p class="text-lg font-semibold tabular-nums" :class="transaction.amount > 0 ? 'text-emerald-700 dark:text-emerald-400' : ''">
                        {{ transaction.amount > 0 ? '+' : '' }}{{ transaction.amount }}
                    </p>
                </article>
            </div>
            <p v-else class="border-y py-10 text-center text-sm text-muted-foreground">No transactions recorded yet.</p>
        </section>

        <nav v-if="transactions.last_page > 1" class="flex flex-wrap justify-center gap-2" aria-label="Transaction pages">
            <Link v-for="link in transactions.links" :key="link.label" :href="link.url ?? '/admin/transactions'" class="inline-flex items-center gap-1 rounded-md border px-3 py-2 text-sm" :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'" :aria-disabled="!link.url" :tabindex="link.url ? 0 : -1">
                <ArrowLeft v-if="link.label.includes('Previous')" class="size-4" />
                {{ link.label.replace('&laquo;', '‹').replace('&raquo;', '›') }}
                <ArrowRight v-if="link.label.includes('Next')" class="size-4" />
            </Link>
        </nav>
    </main>
</template>