<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Clapperboard, FileText, Sparkles } from '@lucide/vue';
import { dashboard } from '@/routes';

// -------------------------------------------------------
// Types — describes a script item and pagination structure
// -------------------------------------------------------
type ScriptItem = {
    id: number;
    script_type: string;
    generate_script_text: string | null;
    status: string;
    brief: string;
    brand_profile_snapshot: { brand_name: string } | null;
    created_at: string;
};

type Pagination = {
    data: ScriptItem[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

defineProps<{ scripts: Pagination }>();

// -------------------------------------------------------
// Breadcrumbs
// -------------------------------------------------------
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Script history', href: '/scripts' },
        ],
    },
});

// Format script objective for display
function scriptTypeLabel(type: string): string {
    return ({
        product_promotion: 'Product Promotion',
        product_selling: 'Product Selling',
        education: 'Education',
        brand_awareness: 'Brand Awareness',
        storytelling: 'Storytelling',
        audience_engagement: 'Audience Engagement',
    }[type] ?? type);
}

// Format UTC date to local readable string
function formatDate(date: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
}
</script>

<template>
    <Head title="Script history" />

    <main class="mx-auto w-full max-w-6xl space-y-8 p-6 lg:p-10">
        <!-- Page Header -->
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-muted-foreground text-xs font-semibold tracking-[0.16em] uppercase">
                    Saved results
                </p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">
                    Script history
                </h1>
                <p class="text-muted-foreground mt-2 text-sm">
                    Review and reuse video scripts you have generated.
                </p>
            </div>
            <Link
                href="/scripts/create"
                class="inline-flex h-11 items-center gap-2 rounded-full bg-black px-5 text-sm font-semibold text-white transition hover:bg-neutral-800"
            >
                <Sparkles class="size-4" />
                Generate script
            </Link>
        </header>

        <!-- Scripts List -->
        <section v-if="scripts.data.length" class="space-y-4">
            <article
                v-for="item in scripts.data"
                :key="item.id"
                class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition hover:border-neutral-300 sm:p-6"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-700">
                                {{ scriptTypeLabel(item.script_type) }}
                            </span>
                            <span
                                v-if="item.status === 'failed'"
                                class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700"
                            >
                                Failed (refunded)
                            </span>
                            <span
                                v-else-if="item.status === 'pending'"
                                class="rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800"
                            >
                                Processing
                            </span>
                            <span
                                v-if="item.brand_profile_snapshot"
                                class="text-muted-foreground text-xs"
                            >
                                Brand: {{ item.brand_profile_snapshot.brand_name }}
                            </span>
                            <time class="text-muted-foreground text-xs">
                                {{ formatDate(item.created_at) }}
                            </time>
                        </div>

                        <h2 class="mt-3 line-clamp-2 font-semibold">
                            {{ item.brief }}
                        </h2>

                        <p class="text-muted-foreground mt-2 line-clamp-3 text-sm whitespace-pre-wrap">
                            {{ item.generate_script_text ?? 'No script generated.' }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <Link
                            :href="`/scripts/${item.id}`"
                            class="inline-flex h-9 items-center gap-2 rounded-full border border-neutral-300 px-4 text-sm font-medium transition hover:bg-neutral-100"
                        >
                            <FileText class="size-4" />
                            View
                        </Link>
                    </div>
                </div>
            </article>

            <!-- Pagination Navigation -->
            <nav
                v-if="scripts.last_page > 1"
                aria-label="Script history pages"
                class="flex items-center justify-between border-t border-neutral-200 pt-4 text-sm"
            >
                <Link
                    v-if="scripts.prev_page_url"
                    :href="scripts.prev_page_url"
                    class="underline underline-offset-4"
                >
                    Previous
                </Link>
                <span v-else class="text-muted-foreground">Previous</span>

                <span class="text-muted-foreground">
                    Page {{ scripts.current_page }} of {{ scripts.last_page }}
                </span>

                <Link
                    v-if="scripts.next_page_url"
                    :href="scripts.next_page_url"
                    class="underline underline-offset-4"
                >
                    Next
                </Link>
                <span v-else class="text-muted-foreground">Next</span>
            </nav>
        </section>

        <!-- Empty State -->
        <section
            v-else
            class="rounded-2xl border border-neutral-200 bg-white px-6 py-14 text-center shadow-sm"
        >
            <Clapperboard class="text-muted-foreground mx-auto size-10" />
            <h2 class="mt-4 text-lg font-semibold">No scripts generated yet</h2>
            <p class="text-muted-foreground mx-auto mt-2 max-w-md text-sm">
                Your video scripts will appear here once generated.
            </p>
            <Link
                href="/scripts/create"
                class="mt-5 inline-flex items-center gap-2 text-sm font-medium underline underline-offset-4"
            >
                <Sparkles class="size-4" />
                Generate your first script
            </Link>
        </section>
    </main>
</template>
