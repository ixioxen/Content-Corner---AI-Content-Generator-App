<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, Wallet } from '@lucide/vue';
import { ref } from 'vue';

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
    can_verify: boolean;
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
    checkoutAvailable: boolean;
    checkoutResult: 'complete' | 'cancelled' | null;
    packages: Package[];
    purchases: Purchase[];
    transactions: {
        data: Transaction[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
    };
}>();

const checkoutForm = useForm<{ package_id: number | null; checkout?: string }>({
    package_id: null,
});
const verifyingPurchaseId = ref<number | null>(null);
const verifyError = ref('');

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Billing', href: '/billing' }],
    },
});

function formatPrice(amount: string, currency: string): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
    }).format(Number(amount));
}

function formatDate(date: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
}

function startCheckout(packageId: number): void {
    checkoutForm.package_id = packageId;
    checkoutForm.post('/billing/purchases');
}

function verifyPurchase(purchaseId: number): void {
    verifyingPurchaseId.value = purchaseId;
    verifyError.value = '';
    router.post(
        `/billing/purchases/${purchaseId}/verify`,
        {},
        {
            preserveScroll: true,
            onError: (errors) => {
                verifyError.value =
                    errors.purchase ?? 'Could not verify this payment.';
            },
            onFinish: () => {
                verifyingPurchaseId.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Billing and credits" />
    <main class="mx-auto w-full max-w-7xl space-y-10 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p
                    class="text-muted-foreground flex items-center gap-2 text-xs font-semibold tracking-[0.16em] uppercase"
                >
                    <CreditCard class="size-4" /> Account
                </p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">
                    Billing and credits
                </h1>
                <p class="text-muted-foreground mt-2 text-sm">
                    Review your balance, credit packages, and account activity.
                </p>
            </div>
            <Link
                href="/dashboard"
                class="inline-flex h-10 items-center gap-2 rounded-full border border-neutral-300 bg-white px-4 text-sm font-medium transition hover:bg-neutral-100"
                ><ArrowLeft class="size-4" /> Dashboard</Link
            >
        </header>

        <section
            class="flex flex-wrap items-center gap-4 rounded-2xl border border-neutral-200 bg-white px-5 py-5 shadow-sm"
        >
            <span
                class="flex size-12 items-center justify-center rounded-xl bg-neutral-100"
                ><Wallet class="size-6 text-neutral-700"
            /></span>
            <div>
                <p class="text-muted-foreground text-sm">Available credits</p>
                <p class="text-3xl font-semibold tabular-nums">
                    {{ creditsBalance }}
                </p>
            </div>
        </section>

        <p
            v-if="checkoutResult === 'complete'"
            class="rounded-xl border border-neutral-200 bg-white p-4 text-sm text-neutral-800"
        >
            Checkout completed. Your credits will appear after Stripe confirms
            the payment.
        </p>
        <p
            v-else-if="checkoutResult === 'cancelled'"
            class="text-muted-foreground rounded-xl border border-neutral-200 bg-white p-4 text-sm"
        >
            Checkout was canceled. No credits were added.
        </p>
        <p
            v-if="checkoutForm.errors.checkout"
            class="rounded-xl border border-neutral-300 bg-white p-4 text-sm text-neutral-800"
            role="alert"
        >
            {{ checkoutForm.errors.checkout }}
        </p>
        <p
            v-if="verifyError"
            class="rounded-xl border border-neutral-300 bg-white p-4 text-sm text-neutral-800"
            role="alert"
        >
            {{ verifyError }}
        </p>

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-semibold">Credit packages</h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    Pay securely through Stripe. Credits are added after payment
                    is confirmed.
                </p>
            </div>
            <div
                v-if="packages.length"
                class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
            >
                <article
                    v-for="creditPackage in packages"
                    :key="creditPackage.id"
                    class="flex flex-col rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition hover:border-neutral-300"
                >
                    <h3 class="text-lg font-semibold">
                        {{ creditPackage.name }}
                    </h3>
                    <p class="mt-4 text-3xl font-semibold tabular-nums">
                        {{ creditPackage.credits_amount }}
                        <span
                            class="text-muted-foreground text-base font-normal"
                            >credits</span
                        >
                    </p>
                    <p class="text-muted-foreground mt-2 text-sm">
                        {{
                            formatPrice(
                                creditPackage.price_amount,
                                creditPackage.currency,
                            )
                        }}
                    </p>
                    <button
                        type="button"
                        class="mt-6 h-11 rounded-full bg-black px-4 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="
                            !checkoutAvailable || checkoutForm.processing
                        "
                        @click="startCheckout(creditPackage.id)"
                    >
                        {{
                            checkoutForm.processing
                                ? 'Connecting to Stripe…'
                                : checkoutAvailable
                                  ? 'Buy credits'
                                  : 'Payments unavailable'
                        }}
                    </button>
                </article>
            </div>
            <p
                v-else
                class="text-muted-foreground rounded-2xl border border-neutral-200 bg-white px-6 py-10 text-center text-sm"
            >
                No credit packages are currently available.
            </p>
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-semibold">Purchase history</h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    Pending payments can be safely verified with Stripe here.
                </p>
            </div>
            <div v-if="purchases.length" class="divide-y border-y">
                <article
                    v-for="purchase in purchases"
                    :key="purchase.id"
                    class="flex flex-wrap items-center justify-between gap-3 py-4"
                >
                    <div>
                        <p class="font-medium">
                            {{ purchase.package_name }} ·
                            {{ purchase.credits_amount }} credits
                        </p>
                        <p class="text-muted-foreground mt-1 text-sm">
                            {{
                                formatPrice(
                                    purchase.price_amount,
                                    purchase.currency,
                                )
                            }}
                            · {{ formatDate(purchase.created_at) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            class="rounded-full border border-neutral-300 bg-neutral-50 px-3 py-1 text-xs text-neutral-700 capitalize"
                            >{{ purchase.status }}</span
                        >
                        <button
                            v-if="
                                purchase.status === 'pending' &&
                                purchase.can_verify
                            "
                            type="button"
                            class="h-9 rounded-full border border-neutral-300 px-4 text-sm font-medium transition hover:bg-neutral-100 disabled:opacity-50"
                            :disabled="verifyingPurchaseId !== null"
                            @click="verifyPurchase(purchase.id)"
                        >
                            {{
                                verifyingPurchaseId === purchase.id
                                    ? 'Checking…'
                                    : 'Verify payment'
                            }}
                        </button>
                    </div>
                </article>
            </div>
            <p
                v-else
                class="text-muted-foreground rounded-2xl border border-neutral-200 bg-white px-6 py-10 text-center text-sm"
            >
                No purchases yet.
            </p>
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-semibold">
                    Credit transaction history
                </h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    Every balance change is recorded in your credit ledger.
                </p>
            </div>
            <div v-if="transactions.data.length" class="divide-y border-y">
                <article
                    v-for="transaction in transactions.data"
                    :key="transaction.id"
                    class="flex flex-wrap items-center justify-between gap-3 py-4"
                >
                    <div>
                        <p class="font-medium">
                            {{
                                transaction.description ||
                                transaction.type.replaceAll('_', ' ')
                            }}
                        </p>
                        <p class="text-muted-foreground mt-1 text-sm">
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
                class="text-muted-foreground rounded-2xl border border-neutral-200 bg-white px-6 py-10 text-center text-sm"
            >
                No credit transactions yet.
            </p>
            <nav
                v-if="transactions.last_page > 1"
                class="flex flex-wrap justify-center gap-2"
                aria-label="Credit transaction pages"
            >
                <Link
                    v-for="link in transactions.links"
                    :key="link.label"
                    :href="link.url ?? '/billing'"
                    class="rounded-full border border-neutral-300 px-4 py-2 text-sm transition"
                    :class="
                        link.active
                            ? 'bg-black text-white'
                            : 'bg-white hover:bg-neutral-100'
                    "
                    :aria-disabled="!link.url"
                    :tabindex="link.url ? 0 : -1"
                >
                    {{
                        link.label
                            .replace('&laquo;', '‹')
                            .replace('&raquo;', '›')
                    }}
                </Link>
            </nav>
        </section>
    </main>
</template>
