<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight, Sparkles } from '@lucide/vue';
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

defineProps<{
    creditsBalance: number;
    recentTransactions: Transaction[];
    recentGenerations: Generation[];
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

function contentTypeLabel(contentType: string): string {
    return (
        {
            social_post: 'Social media post',
            marketing_copy: 'Marketing copy',
            blog_outline: 'Blog outline',
        }[contentType] ?? contentType
    );
}
</script>

<template>
    <Head title="Dashboard" />

    <main class="mx-auto w-full max-w-6xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-muted-foreground text-sm font-medium">
                    Your workspace
                </p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">
                    Dashboard
                </h1>
            </div>
            <Link
                href="/generate"
                class="bg-primary text-primary-foreground inline-flex h-10 items-center gap-2 rounded-md px-4 text-sm font-medium hover:opacity-90"
            >
                Generate content
                <Sparkles class="size-4" />
            </Link>
        </header>

        <section class="grid gap-8 border-y py-7 sm:grid-cols-4">
            <div>
                <p class="text-muted-foreground text-sm">Available credits</p>
                <p class="mt-2 text-4xl font-semibold tabular-nums">
                    {{ creditsBalance }}
                </p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-muted-foreground text-sm">Recent generations</p>
                <div v-if="recentGenerations.length" class="mt-3 space-y-3">
                    <Link
                        v-for="generation in recentGenerations"
                        :key="generation.id"
                        :href="`/generations/${generation.id}`"
                        class="hover:bg-muted/50 block rounded-md border p-3"
                    >
                        <span class="text-muted-foreground text-xs font-medium">
                            {{ contentTypeLabel(generation.content_type) }} ·
                            {{ formatDate(generation.created_at) }}
                        </span>
                        <span
                            class="mt-1 line-clamp-1 block text-sm font-medium"
                        >
                            {{ generation.brief }}
                        </span>
                    </Link>
                    <Link
                        href="/generations"
                        class="inline-block text-sm underline underline-offset-4"
                    >
                        View all history
                    </Link>
                </div>
                <template v-else>
                    <p class="mt-2 text-lg font-medium">No generations yet</p>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Your generated content will appear here.
                    </p>
                </template>
            </div>
            <div>
                <p class="text-muted-foreground text-sm">Custom tones</p>
                <Link
                    href="/tones"
                    class="mt-2 inline-block text-lg font-medium underline underline-offset-4"
                    >Manage your tones</Link
                >
                <p class="text-muted-foreground mt-1 text-sm">
                    Save a writing style for later use.
                </p>
            </div>
        </section>

        <section>
            <div class="mb-4">
                <h2 class="text-xl font-semibold">Recent credit activity</h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    Your latest balance changes.
                </p>
            </div>

            <div v-if="recentTransactions.length" class="divide-y border-y">
                <article
                    v-for="transaction in recentTransactions"
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
                        <time
                            class="text-muted-foreground mt-1 block text-sm"
                            >{{ formatDate(transaction.created_at) }}</time
                        >
                    </div>
                    <p
                        class="font-semibold tabular-nums"
                        :class="
                            transaction.amount > 0
                                ? 'text-emerald-700 dark:text-emerald-400'
                                : ''
                        "
                    >
                        {{ transaction.amount > 0 ? '+' : ''
                        }}{{ transaction.amount }}
                    </p>
                </article>
            </div>
            <div v-else class="text-muted-foreground border-y py-8 text-sm">
                No credit activity yet.
            </div>
        </section>
    </main>
</template>
