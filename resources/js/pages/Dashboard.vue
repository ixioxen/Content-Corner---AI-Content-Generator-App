<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight } from '@lucide/vue';
import { dashboard } from '@/routes';

type Transaction = {
    id: number;
    amount: number;
    type: string;
    description: string | null;
    created_at: string;
};

defineProps<{
    creditsBalance: number;
    recentTransactions: Transaction[];
    recentGenerations: never[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
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
    <Head title="Dashboard" />

    <main class="mx-auto w-full max-w-6xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-muted-foreground">Your workspace</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">Dashboard</h1>
            </div>
            <Link href="/tones/create" class="inline-flex h-10 items-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90">
                Create a tone
                <ArrowUpRight class="size-4" />
            </Link>
        </header>

        <section class="grid gap-8 border-y py-7 sm:grid-cols-3">
            <div>
                <p class="text-sm text-muted-foreground">Available credits</p>
                <p class="mt-2 text-4xl font-semibold tabular-nums">{{ creditsBalance }}</p>
            </div>
            <div>
                <p class="text-sm text-muted-foreground">Recent generations</p>
                <p class="mt-2 text-lg font-medium">No generations yet</p>
                <p class="mt-1 text-sm text-muted-foreground">Your generated content will appear here.</p>
            </div>
            <div>
                <p class="text-sm text-muted-foreground">Custom tones</p>
                <Link href="/tones" class="mt-2 inline-block text-lg font-medium underline underline-offset-4">Manage your tones</Link>
                <p class="mt-1 text-sm text-muted-foreground">Save a writing style for later use.</p>
            </div>
        </section>

        <section>
            <div class="mb-4">
                <h2 class="text-xl font-semibold">Recent credit activity</h2>
                <p class="mt-1 text-sm text-muted-foreground">Your latest balance changes.</p>
            </div>

            <div v-if="recentTransactions.length" class="divide-y border-y">
                <article v-for="transaction in recentTransactions" :key="transaction.id" class="flex flex-wrap items-center justify-between gap-3 py-4">
                    <div>
                        <p class="font-medium">{{ transaction.description || transaction.type.replaceAll('_', ' ') }}</p>
                        <time class="mt-1 block text-sm text-muted-foreground">{{ formatDate(transaction.created_at) }}</time>
                    </div>
                    <p class="font-semibold tabular-nums" :class="transaction.amount > 0 ? 'text-emerald-700 dark:text-emerald-400' : ''">
                        {{ transaction.amount > 0 ? '+' : '' }}{{ transaction.amount }}
                    </p>
                </article>
            </div>
            <div v-else class="border-y py-8 text-sm text-muted-foreground">No credit activity yet.</div>
        </section>
    </main>
</template>
