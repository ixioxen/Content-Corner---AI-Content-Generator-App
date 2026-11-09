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
    transactions: {
        data: Transaction[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
    };
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
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
}
</script>

<template>
    <Head title="Transactions and usage" />
    <main class="admin-page mx-auto w-full max-w-7xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p
                    class="text-muted-foreground flex items-center gap-2 text-sm font-medium"
                >
                    <History class="size-4" /> Operations
                </p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">
                    Transactions and usage
                </h1>
                <p class="text-muted-foreground mt-2 text-sm">
                    The credit ledger is the source of truth for balances and
                    generation usage.
                </p>
            </div>
            <Link
                href="/admin"
                class="inline-flex h-10 items-center gap-2 rounded-full border border-black/15 bg-white px-4 text-sm font-medium transition hover:bg-black hover:text-white focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2 focus-visible:outline-none"
                ><ArrowLeft class="size-4" /> Overview</Link
            >
        </header>

        <section
            class="grid gap-4 sm:grid-cols-2"
            aria-label="Generation usage"
        >
            <article class="metric-card rounded-2xl border p-5 sm:p-6">
                <p
                    class="text-xs font-semibold tracking-[0.14em] text-black/50 uppercase"
                >
                    Successful generation charges
                </p>
                <p
                    class="mt-3 text-3xl font-semibold tracking-tight tabular-nums"
                >
                    {{ usage.generationCount }}
                </p>
            </article>
            <article class="metric-card rounded-2xl border p-5 sm:p-6">
                <p
                    class="text-xs font-semibold tracking-[0.14em] text-black/50 uppercase"
                >
                    Generation credits consumed
                </p>
                <p
                    class="mt-3 text-3xl font-semibold tracking-tight tabular-nums"
                >
                    {{ usage.creditsUsed }}
                </p>
            </article>
        </section>

        <section class="activity-card overflow-hidden rounded-2xl border">
            <div class="border-b border-black/10 px-5 py-5 sm:px-6">
                <h2 class="text-xl font-semibold">All credit transactions</h2>
                <p class="mt-1 text-sm text-black/55">
                    Entries are append-only and include both user and admin
                    activity.
                </p>
            </div>
            <div
                v-if="transactions.data.length"
                class="divide-y divide-black/10"
            >
                <article
                    v-for="transaction in transactions.data"
                    :key="transaction.id"
                    class="grid gap-2 px-5 py-4 sm:grid-cols-[minmax(12rem,1fr)_auto] sm:items-center sm:px-6"
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
                            {{ transaction.user.email }}
                        </p>
                        <p class="mt-1 text-xs text-black/50">
                            {{ transaction.type.replaceAll('_', ' ')
                            }}<span v-if="transaction.created_by_admin">
                                · adjusted by
                                {{ transaction.created_by_admin.name }}</span
                            >
                            · {{ formatDate(transaction.created_at) }}
                        </p>
                    </div>
                    <p class="text-lg font-semibold tabular-nums">
                        {{ transaction.amount > 0 ? '+' : ''
                        }}{{ transaction.amount }}
                    </p>
                </article>
            </div>
            <p
                v-else
                class="bg-white px-5 py-12 text-center text-sm text-black/55"
            >
                No transactions recorded yet.
            </p>
        </section>

        <nav
            v-if="transactions.last_page > 1"
            class="flex flex-wrap justify-center gap-2"
            aria-label="Transaction pages"
        >
            <Link
                v-for="link in transactions.links"
                :key="link.label"
                :href="link.url ?? '/admin/transactions'"
                class="inline-flex items-center gap-1 rounded-full border px-3 py-2 text-sm transition focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2 focus-visible:outline-none"
                :class="
                    link.active
                        ? 'border-black bg-black text-white'
                        : 'border-black/15 bg-white text-black hover:bg-black/[0.04]'
                "
                :aria-disabled="!link.url"
                :tabindex="link.url ? 0 : -1"
            >
                <ArrowLeft
                    v-if="link.label.includes('Previous')"
                    class="size-4"
                />
                {{ link.label.replace('&laquo;', '‹').replace('&raquo;', '›') }}
                <ArrowRight v-if="link.label.includes('Next')" class="size-4" />
            </Link>
        </nav>
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
