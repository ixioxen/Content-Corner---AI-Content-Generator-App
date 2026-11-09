<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    BookOpenText,
    Clock3,
    FileText,
    MessageSquareText,
    Sparkles,
    WandSparkles,
} from '@lucide/vue';
import { computed } from 'vue';
import { dashboard } from '@/routes';

type Transaction = {
    id: number;
    amount: number;
    type: string;
    description: string | null;
    created_at: string;
};

type Generation = {
    id: number;
    content_type: string;
    brief: string;
    created_at: string;
};

const props = defineProps<{
    creditsBalance: number;
    generationsCount: number;
    customTonesCount: number;
    recentTransactions: Transaction[];
    recentGenerations: Generation[];
}>();

const page = usePage();
const userName = computed(() => page.props.auth.user.name);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const contentTypes = [
    {
        number: '01',
        title: 'Social post',
        description: 'Short, on-brand posts from a single prompt.',
        icon: MessageSquareText,
    },
    {
        number: '02',
        title: 'Marketing copy',
        description: 'Product and campaign copy in your own tone.',
        icon: FileText,
    },
    {
        number: '03',
        title: 'Blog outline',
        description: 'A clear structure before you start writing.',
        icon: BookOpenText,
    },
];

const metrics = computed(() => [
    { label: 'Credits', value: props.creditsBalance },
    { label: 'Generations', value: props.generationsCount },
    { label: 'Custom tones', value: props.customTonesCount },
]);

function formatNumber(value: number): string {
    return new Intl.NumberFormat().format(value);
}

function formatDate(date: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
}

function contentTypeLabel(contentType: string): string {
    return (
        {
            social_post: 'Social post',
            marketing_copy: 'Marketing copy',
            blog_outline: 'Blog outline',
        }[contentType] ?? contentType
    );
}

function transactionLabel(transaction: Transaction): string {
    return transaction.description ?? transaction.type.replaceAll('_', ' ');
}
</script>

<template>
    <Head title="Dashboard" />

    <main
        class="dashboard-page min-h-[calc(100svh-3rem)] px-5 py-7 sm:px-8 sm:py-9 lg:px-10"
    >
        <div class="mx-auto w-full max-w-7xl">
            <header class="flex flex-wrap items-center justify-between gap-5">
                <div>
                    <p class="eyebrow">Overview</p>
                    <h1 class="page-title mt-2">
                        Welcome back, {{ userName }}
                    </h1>
                </div>
                <Link
                    href="/generate"
                    class="primary-button inline-flex items-center gap-2 rounded-full px-5 py-3 text-sm font-semibold"
                >
                    New generation
                    <Sparkles class="h-4 w-4" />
                </Link>
            </header>

            <section
                aria-label="Account overview"
                class="metrics-card mt-8 flex flex-col gap-5 rounded-2xl border p-5 sm:flex-row sm:items-center sm:justify-between sm:px-7 sm:py-6"
            >
                <div class="metric-group">
                    <template
                        v-for="(metric, index) in metrics"
                        :key="metric.label"
                    >
                        <div class="metric">
                            <span class="metric-label">{{ metric.label }}</span>
                            <strong>{{ formatNumber(metric.value) }}</strong>
                        </div>
                        <span
                            v-if="index < metrics.length - 1"
                            class="metric-divider"
                            aria-hidden="true"
                        ></span>
                    </template>
                </div>
                <Link
                    href="/billing"
                    class="secondary-button inline-flex items-center justify-center gap-2 rounded-full px-5 py-3 text-sm font-semibold"
                >
                    Buy credits
                    <ArrowUpRight class="h-4 w-4" />
                </Link>
            </section>

            <section class="mt-9">
                <div class="section-heading">
                    <div>
                        <h2>Start something new</h2>
                        <p>Choose a format and bring your next idea to life.</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <Link
                        v-for="type in contentTypes"
                        :key="type.number"
                        href="/generate"
                        class="content-card group flex min-h-52 flex-col rounded-2xl border p-6 transition duration-200 hover:-translate-y-1"
                    >
                        <div class="flex items-center justify-between">
                            <span class="card-number">{{ type.number }}</span>
                            <component
                                :is="type.icon"
                                class="h-5 w-5 text-black/50 transition group-hover:text-black"
                            />
                        </div>
                        <h3 class="mt-5">{{ type.title }}</h3>
                        <p class="mt-2 max-w-[18rem] text-sm leading-6">
                            {{ type.description }}
                        </p>
                        <span
                            class="card-arrow mt-auto ml-auto inline-flex h-10 w-10 items-center justify-center rounded-full transition group-hover:bg-black group-hover:text-white"
                        >
                            <ArrowRight class="h-4 w-4" />
                        </span>
                    </Link>
                </div>
            </section>

            <section class="mt-7 grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
                <article class="activity-card rounded-2xl border p-5 sm:p-6">
                    <header class="flex items-center justify-between gap-4">
                        <div>
                            <h2>Recent generations</h2>
                            <p class="mt-1 text-sm">
                                Your latest content, all in one place.
                            </p>
                        </div>
                        <Link href="/generations" class="view-all">
                            View all <ArrowUpRight class="h-4 w-4" />
                        </Link>
                    </header>

                    <div
                        v-if="recentGenerations.length"
                        class="mt-5 divide-y divide-black/10"
                    >
                        <Link
                            v-for="generation in recentGenerations"
                            :key="generation.id"
                            :href="`/generations/${generation.id}`"
                            class="generation-row flex items-center gap-4 py-4 first:pt-0 last:pb-0"
                        >
                            <span class="row-icon"
                                ><Sparkles class="h-4 w-4"
                            /></span>
                            <span class="min-w-0 flex-1">
                                <span
                                    class="block truncate text-sm font-semibold"
                                    >{{ generation.brief }}</span
                                >
                                <span class="mt-1 block text-xs text-black/55">
                                    {{
                                        contentTypeLabel(
                                            generation.content_type,
                                        )
                                    }}
                                    · {{ formatDate(generation.created_at) }}
                                </span>
                            </span>
                            <ArrowUpRight
                                class="h-4 w-4 shrink-0 text-black/45"
                            />
                        </Link>
                    </div>
                    <div
                        v-else
                        class="empty-state mt-5 flex min-h-28 items-center justify-center rounded-xl border border-dashed px-5 text-center"
                    >
                        <p>
                            Nothing here yet. Your generated content will appear
                            here.
                        </p>
                    </div>
                </article>

                <article class="activity-card rounded-2xl border p-5 sm:p-6">
                    <header class="flex items-center justify-between gap-4">
                        <div>
                            <h2>Recent transactions</h2>
                            <p class="mt-1 text-sm">
                                A clear record of your credit activity.
                            </p>
                        </div>
                        <Link href="/billing" class="view-all">
                            View all <ArrowUpRight class="h-4 w-4" />
                        </Link>
                    </header>

                    <div
                        v-if="recentTransactions.length"
                        class="mt-5 divide-y divide-black/10"
                    >
                        <div
                            v-for="transaction in recentTransactions"
                            :key="transaction.id"
                            class="transaction-row flex items-center gap-3 py-4 first:pt-0 last:pb-0"
                        >
                            <span class="row-icon"
                                ><Clock3 class="h-4 w-4"
                            /></span>
                            <span class="min-w-0 flex-1">
                                <span
                                    class="block truncate text-sm font-semibold"
                                    >{{ transactionLabel(transaction) }}</span
                                >
                                <span class="mt-1 block text-xs text-black/55">
                                    {{ transaction.type.replaceAll('_', ' ') }}
                                    · {{ formatDate(transaction.created_at) }}
                                </span>
                            </span>
                            <strong class="transaction-amount">
                                {{ transaction.amount > 0 ? '+' : ''
                                }}{{ formatNumber(transaction.amount) }}
                            </strong>
                        </div>
                    </div>
                    <div
                        v-else
                        class="empty-state mt-5 flex min-h-28 items-center justify-center rounded-xl border border-dashed px-5 text-center"
                    >
                        <p>No credit activity yet.</p>
                    </div>
                </article>
            </section>
        </div>
    </main>
</template>

<style scoped>
.dashboard-page {
    color: #000;
    background: #fafafa;
}

.eyebrow,
.metric-label,
.card-number {
    color: #666;
    font-size: 11px;
    font-weight: 650;
    letter-spacing: 0.13em;
    text-transform: uppercase;
}

.page-title {
    color: #000;
    font-size: clamp(1.8rem, 3.7vw, 2.8rem);
    font-weight: 700;
    letter-spacing: -0.055em;
    line-height: 1.05;
}

.primary-button {
    color: #fff;
    background: #000;
    transition:
        background-color 150ms ease,
        transform 150ms ease;
}

.primary-button:hover {
    background: #252525;
    transform: translateY(-1px);
}

.metrics-card,
.content-card,
.activity-card {
    border-color: #e5e5e5;
    background: #fff;
}

.metric-group {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 24px;
}

.metric {
    display: flex;
    align-items: baseline;
    gap: 12px;
}

.metric strong {
    color: #000;
    font-size: 1.65rem;
    font-weight: 650;
    letter-spacing: -0.04em;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.metric-divider {
    width: 1px;
    height: 32px;
    background: #e5e5e5;
}

.secondary-button {
    border: 1px solid #111;
    color: #000;
    background: #fff;
    transition:
        color 150ms ease,
        background-color 150ms ease;
}

.secondary-button:hover {
    color: #fff;
    background: #000;
}

.section-heading h2,
.activity-card h2 {
    color: #111;
    font-size: 1.2rem;
    font-weight: 650;
    letter-spacing: -0.025em;
}

.section-heading p,
.activity-card header p,
.content-card p,
.empty-state {
    color: #666;
}

.section-heading p {
    margin-top: 5px;
    font-size: 13px;
}

.content-card {
    border-color: #e5e5e5;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.015);
}

.content-card:hover {
    border-color: #b5b5b5;
    box-shadow: 0 14px 32px rgba(0, 0, 0, 0.06);
}

.content-card h3 {
    color: #000;
    font-size: clamp(1.25rem, 2vw, 1.65rem);
    font-weight: 650;
    letter-spacing: -0.04em;
}

.card-number {
    letter-spacing: 0;
}

.card-arrow {
    color: #000;
    background: #f2f2f2;
}

.activity-card {
    min-width: 0;
}

.activity-card header p {
    font-size: 12px;
}

.view-all {
    display: inline-flex;
    flex: none;
    align-items: center;
    gap: 5px;
    color: #111;
    font-size: 12px;
    font-weight: 600;
}

.view-all:hover {
    text-decoration: underline;
    text-underline-offset: 3px;
}

.empty-state {
    border-color: #dedede;
    font-size: 13px;
}

.row-icon {
    display: inline-flex;
    width: 34px;
    height: 34px;
    flex: none;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    color: #111;
    background: #f2f2f2;
}

.generation-row:hover .row-icon {
    color: #fff;
    background: #000;
}

.transaction-amount {
    color: #000;
    font-size: 15px;
    font-weight: 650;
    font-variant-numeric: tabular-nums;
}

@media (max-width: 639px) {
    .metric-group {
        align-items: stretch;
        flex-direction: column;
        gap: 14px;
    }

    .metrics-card {
        gap: 16px;
    }

    .metric {
        justify-content: space-between;
    }

    .metric-divider {
        display: none;
    }
}
</style>
