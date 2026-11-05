<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Copy, Sparkles, Trash2 } from '@lucide/vue';
import { ref } from 'vue';

type Generation = {
    id: number;
    content_type: string;
    brief: string;
    generated_text: string;
    brand_profile_snapshot: { brand_name: string } | null;
    custom_tone_snapshot: { name: string } | null;
    created_at: string;
};

const props = defineProps<{ generation: Generation }>();
const copied = ref(false);
const copyError = ref('');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Generation history', href: '/generations' },
            { title: 'Generated content', href: `/generations` },
        ],
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

async function copyContent(): Promise<void> {
    copyError.value = '';

    try {
        await navigator.clipboard.writeText(props.generation.generated_text);
        copied.value = true;
        window.setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        copyError.value =
            'Could not copy the content. Please select and copy it manually.';
    }
}

function deleteGeneration(): void {
    if (window.confirm('Delete this generated content from your history?')) {
        router.delete(`/generations/${props.generation.id}`);
    }
}
</script>

<template>
    <Head title="Generated content" />
    <main class="mx-auto w-full max-w-4xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <Link
                    href="/generations"
                    class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 text-sm"
                >
                    <ArrowLeft class="size-4" />
                    Generation history
                </Link>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span
                        class="bg-muted rounded-md px-2 py-1 text-xs font-medium"
                        >{{ contentTypeLabel(generation.content_type) }}</span
                    >
                    <time class="text-muted-foreground text-xs">{{
                        formatDate(generation.created_at)
                    }}</time>
                </div>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight">
                    Generated content
                </h1>
            </div>
            <div class="flex items-center gap-2">
                <Link
                    href="/generate"
                    class="hover:bg-muted inline-flex h-9 items-center gap-2 rounded-md border px-3 text-sm font-medium"
                >
                    <Sparkles class="size-4" />
                    New
                </Link>
                <button
                    type="button"
                    class="text-destructive hover:bg-muted inline-flex size-9 items-center justify-center rounded-md border"
                    aria-label="Delete from history"
                    title="Delete from history"
                    @click="deleteGeneration"
                >
                    <Trash2 class="size-4" />
                </button>
            </div>
        </header>

        <section class="space-y-6 rounded-xl border p-5 sm:p-7">
            <div>
                <h2 class="text-sm font-semibold">Your brief</h2>
                <p
                    class="text-muted-foreground mt-2 text-sm leading-6 whitespace-pre-wrap"
                >
                    {{ generation.brief }}
                </p>
            </div>
            <div
                v-if="
                    generation.brand_profile_snapshot ||
                    generation.custom_tone_snapshot
                "
                class="flex flex-wrap gap-2 border-y py-4 text-xs"
            >
                <span
                    v-if="generation.brand_profile_snapshot"
                    class="bg-muted rounded-md px-2 py-1"
                    >Brand:
                    {{ generation.brand_profile_snapshot.brand_name }}</span
                >
                <span
                    v-if="generation.custom_tone_snapshot"
                    class="bg-muted rounded-md px-2 py-1"
                    >Tone: {{ generation.custom_tone_snapshot.name }}</span
                >
            </div>
            <div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">Result</h2>
                    <button
                        type="button"
                        class="hover:bg-muted inline-flex h-9 items-center gap-2 rounded-md border px-3 text-sm font-medium"
                        @click="copyContent"
                    >
                        <Copy class="size-4" />
                        {{ copied ? 'Copied' : 'Copy' }}
                    </button>
                </div>
                <p
                    v-if="copyError"
                    role="alert"
                    class="text-destructive mt-2 text-sm"
                >
                    {{ copyError }}
                </p>
                <div
                    class="bg-muted/40 mt-4 rounded-lg p-4 text-sm leading-7 whitespace-pre-wrap sm:p-6"
                >
                    {{ generation.generated_text }}
                </div>
            </div>
        </section>
    </main>
</template>
