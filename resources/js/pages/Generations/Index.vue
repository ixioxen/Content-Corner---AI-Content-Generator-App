<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FileText, Sparkles, Trash2 } from '@lucide/vue';

type Generation = {
    id: number;
    content_type: string;
    brief: string;
    generated_text: string;
    brand_profile_snapshot: { brand_name: string } | null;
    created_at: string;
};

type Pagination = {
    data: Generation[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

defineProps<{ generations: Pagination }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Generation history', href: '/generations' }],
    },
});

function contentTypeLabel(contentType: string): string {
    return (
        {
            social_post: 'Social media post',
            marketing_copy: 'Marketing copy',
            blog_outline: 'Blog outline',
        }[contentType] ?? contentType
    );
}

function formatDate(date: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
}

function deleteGeneration(generation: Generation): void {
    if (window.confirm('Delete this generated content from your history?')) {
        router.delete(`/generations/${generation.id}`);
    }
}
</script>

<template>
    <Head title="Generation history" />
    <main class="mx-auto w-full max-w-6xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-muted-foreground text-sm font-medium">
                    Saved results
                </p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">
                    Generation history
                </h1>
                <p class="text-muted-foreground mt-2 text-sm">
                    Review content you have generated.
                </p>
            </div>
            <Link
                href="/generate"
                class="bg-primary text-primary-foreground inline-flex h-10 items-center gap-2 rounded-md px-4 text-sm font-medium hover:opacity-90"
            >
                <Sparkles class="size-4" />
                Generate content
            </Link>
        </header>

        <section v-if="generations.data.length" class="space-y-4">
            <article
                v-for="generation in generations.data"
                :key="generation.id"
                class="rounded-xl border p-5"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="bg-muted rounded-md px-2 py-1 text-xs font-medium"
                                >{{
                                    contentTypeLabel(generation.content_type)
                                }}</span
                            >
                            <span
                                v-if="generation.brand_profile_snapshot"
                                class="text-muted-foreground text-xs"
                                >Brand:
                                {{
                                    generation.brand_profile_snapshot.brand_name
                                }}</span
                            >
                            <time class="text-muted-foreground text-xs">{{
                                formatDate(generation.created_at)
                            }}</time>
                        </div>
                        <h2 class="mt-3 line-clamp-2 font-semibold">
                            {{ generation.brief }}
                        </h2>
                        <p
                            class="text-muted-foreground mt-2 line-clamp-3 text-sm whitespace-pre-wrap"
                        >
                            {{ generation.generated_text }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <Link
                            :href="`/generations/${generation.id}`"
                            class="hover:bg-muted inline-flex h-9 items-center gap-2 rounded-md border px-3 text-sm font-medium"
                        >
                            <FileText class="size-4" />
                            View
                        </Link>
                        <button
                            type="button"
                            class="text-destructive hover:bg-muted inline-flex size-9 items-center justify-center rounded-md border"
                            :aria-label="`Delete generation ${generation.id}`"
                            title="Delete from history"
                            @click="deleteGeneration(generation)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                </div>
            </article>

            <nav
                v-if="generations.last_page > 1"
                aria-label="Generation history pages"
                class="flex items-center justify-between border-t pt-4 text-sm"
            >
                <Link
                    v-if="generations.prev_page_url"
                    :href="generations.prev_page_url"
                    class="underline underline-offset-4"
                    >Previous</Link
                >
                <span v-else class="text-muted-foreground">Previous</span>
                <span class="text-muted-foreground"
                    >Page {{ generations.current_page }} of
                    {{ generations.last_page }}</span
                >
                <Link
                    v-if="generations.next_page_url"
                    :href="generations.next_page_url"
                    class="underline underline-offset-4"
                    >Next</Link
                >
                <span v-else class="text-muted-foreground">Next</span>
            </nav>
        </section>

        <section v-else class="rounded-xl border px-6 py-12 text-center">
            <FileText class="text-muted-foreground mx-auto size-10" />
            <h2 class="mt-4 text-lg font-semibold">No generated content yet</h2>
            <p class="text-muted-foreground mx-auto mt-2 max-w-md text-sm">
                Your successful generations will be saved here.
            </p>
            <Link
                href="/generate"
                class="mt-5 inline-flex items-center gap-2 text-sm font-medium underline underline-offset-4"
            >
                <Sparkles class="size-4" />
                Generate your first content
            </Link>
        </section>
    </main>
</template>
