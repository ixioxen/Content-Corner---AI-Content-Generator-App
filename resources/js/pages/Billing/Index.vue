<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, Wallet } from '@lucide/vue';

type Package = {
    id: number;
    name: string;
    credits_amount: number;
    price_amount: string;
    currency: string;
};

type Purchase = {
    id: number;
    package_name: string;
    credits_amount: number;
    price_amount: string;
    currency: string;
    status: 'pending' | 'paid' | 'failed';
    created_at: string;
};

type Transaction = {
    id: number;
    amount: number;
    type: string;
    description: string | null;
    created_at: string;
};

type PaginationLink = { url: string | null; label: string; active: boolean };

defineProps<{
    creditsBalance: number;
    packages: Package[];
    purchases: Purchase[];
    transactions: { data: Transaction[]; links: PaginationLink[]; current_page: number; last_page: number };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Billing', href: '/billing' }],
    },
});

function formatPrice(amount: string, currency: string): string {
    return `${currency} ${Number(amount).toFixed(2)}`;
}

function formatDate(date: string): string {
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(date));
}
</script>

<template>
    <Head title="Billing and credits" />
    <main class="mx-auto w-full max-w-7xl space-y-10 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="flex items-center gap-2 text-sm font-medium text-muted-foreground"><CreditCard class="size-4" /> Account</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">Billing and credits</h1>
                <p class="mt-2 text-sm text-muted-foreground">Review your balance, credit packages, and account activity.</p>
            </div>
            <Link href="/dashboard" class="inline-flex h-10 items-center gap-2 rounded-md border px-4 text-sm font-medium hover:bg-muted"><ArrowLeft class="size-4" /> Dashboard</Link>
        </header>

        <section class="flex flex-wrap items-center gap-4 border-y py-6">
            <Wallet class="size-8 text-muted-foreground" />
            <div>
                <p class="text-sm text-muted-foreground">Available credits</p>
                <p class="text-3xl font-semibold tabular-nums">{{ creditsBalance }}</p>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-semibold">Credit packages</h2>
                <p class="mt-1 text-sm text-muted-foreground">Checkout will be available after Stripe is connected.</p>
            </div>
            <div v-if="packages.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <article v-for="creditPackage in packages" :key="creditPackage.id" class="flex flex-col rounded-xl border p-5">
                    <h3 class="text-lg font-semibold">{{ creditPackage.name }}</h3>
                    <p class="mt-4 text-3xl font-semibold tabular-nums">{{ creditPackage.credits_amount }} <span class="text-base font-normal text-muted-foreground">credits</span></p>
                    <p class="mt-2 text-sm text-muted-foreground">{{ formatPrice(creditPackage.price_amount, creditPackage.currency) }}</p>
                    <button type="button" disabled class="mt-6 h-10 cursor-not-allowed rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground opacity-50">Stripe checkout coming soon</button>
                </article>
            </div>
            <p v-else class="rounded-xl border px-6 py-10 text-center text-sm text-muted-foreground">No credit packages are currently available.</p>
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-semibold">Purchase history</h2>
                <p class="mt-1 text-sm text-muted-foreground">Purchases will appear here after checkout is enabled.</p>
            </div>
            <div v-if="purchases.length" class="divide-y border-y">
                <article v-for="purchase in purchases" :key="purchase.id" class="flex flex-wrap items-center justify-between gap-3 py-4">
                    <div>
                        <p class="font-medium">{{ purchase.package_name }} · {{ purchase.credits_amount }} credits</p>
                        <p class="mt-1 text-sm text-muted-foreground">{{ formatPrice(purchase.price_amount, purchase.currency) }} · {{ formatDate(purchase.created_at) }}</p>
                    </div>
                    <span class="rounded border px-2 py-1 text-xs capitalize" :class="purchase.status === 'paid' ? 'text-emerald-700 dark:text-emerald-400' : purchase.status === 'failed' ? 'text-destructive' : 'text-muted-foreground'">{{ purchase.status }}</span>
                </article>
            </div>
            <p v-else class="rounded-xl border px-6 py-8 text-center text-sm text-muted-foreground">No purchases yet.</p>
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-semibold">Credit transaction history</h2>
                <p class="mt-1 text-sm text-muted-foreground">Every balance change is recorded in your credit ledger.</p>
            </div>
            <div v-if="transactions.data.length" class="divide-y border-y">
                <article v-for="transaction in transactions.data" :key="transaction.id" class="flex flex-wrap items-center justify-between gap-3 py-4">
                    <div>
                        <p class="font-medium">{{ transaction.description || transaction.type.replaceAll('_', ' ') }}</p>
                        <p class="mt-1 text-sm text-muted-foreground">{{ formatDate(transaction.created_at) }}</p>
                    </div>
                    <p class="font-semibold tabular-nums" :class="transaction.amount > 0 ? 'text-emerald-700 dark:text-emerald-400' : ''">{{ transaction.amount > 0 ? '+' : '' }}{{ transaction.amount }}</p>
                </article>
            </div>
            <p v-else class="rounded-xl border px-6 py-8 text-center text-sm text-muted-foreground">No credit transactions yet.</p>
            <nav v-if="transactions.last_page > 1" class="flex flex-wrap justify-center gap-2" aria-label="Credit transaction pages">
                <Link v-for="link in transactions.links" :key="link.label" :href="link.url ?? '/billing'" class="rounded-md border px-3 py-2 text-sm" :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'" :aria-disabled="!link.url" :tabindex="link.url ? 0 : -1">
                    {{ link.label.replace('&laquo;', '‹').replace('&raquo;', '›') }}
                </Link>
            </nav>
        </section>
    </main>
</template>
