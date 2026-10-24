<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';

type Tone = {
    id: number;
    name: string;
    description: string;
    sample_content: string;
    created_at: string;
};

defineProps<{ tones: Tone[] }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Custom tones', href: '/tones' }],
    },
});

function deleteTone(tone: Tone): void {
    if (window.confirm(`Delete the "${tone.name}" tone?`)) {
        router.delete(`/tones/${tone.id}`);
    }
}
</script>

<template>
    <Head title="Custom tones" />
    <main class="mx-auto w-full max-w-6xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-muted-foreground">Writing preferences</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">Custom tones</h1>
                <p class="mt-2 max-w-xl text-sm text-muted-foreground">Keep reusable voice and style examples together.</p>
            </div>
            <Link href="/tones/create" class="inline-flex h-10 items-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90">
                <Plus class="size-4" />
                New tone
            </Link>
        </header>

        <section v-if="tones.length" class="divide-y border-y">
            <article v-for="tone in tones" :key="tone.id" class="flex flex-col gap-4 py-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold">{{ tone.name }}</h2>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-muted-foreground">{{ tone.description }}</p>
                    <blockquote class="mt-3 border-l-2 pl-3 text-sm leading-6 text-muted-foreground">{{ tone.sample_content }}</blockquote>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <Link :href="`/tones/${tone.id}/edit`" class="inline-flex size-9 items-center justify-center rounded-md border hover:bg-muted" :aria-label="`Edit ${tone.name}`" :title="`Edit ${tone.name}`">
                        <Pencil class="size-4" />
                    </Link>
                    <button type="button" class="inline-flex size-9 items-center justify-center rounded-md border text-destructive hover:bg-muted" :aria-label="`Delete ${tone.name}`" :title="`Delete ${tone.name}`" @click="deleteTone(tone)">
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </article>
        </section>

        <section v-else class="border-y py-12 text-center">
            <h2 class="text-lg font-semibold">No custom tones yet</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">Create a tone with a short description and a sample that captures the voice you want to reuse.</p>
            <Link href="/tones/create" class="mt-5 inline-flex items-center gap-2 text-sm font-medium underline underline-offset-4">
                <Plus class="size-4" />
                Create your first tone
            </Link>
        </section>
    </main>
</template>