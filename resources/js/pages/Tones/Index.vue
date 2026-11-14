<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronDown, Pencil, Plus, Sparkles, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Tone = {
    id: number;
    name: string;
    description: string;
    sample_content: string;
    created_at: string;
};

defineProps<{ tones: Tone[] }>();

const toneToDelete = ref<Tone | null>(null);
const isDeleteDialogOpen = ref(false);
const toneToView = ref<Tone | null>(null);
const isToneDialogOpen = ref(false);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Custom tones', href: '/tones' }],
    },
});

function deleteTone(tone: Tone): void {
    toneToDelete.value = tone;
    isDeleteDialogOpen.value = true;
}

function viewTone(tone: Tone): void {
    toneToView.value = tone;
    isToneDialogOpen.value = true;
}

function descriptionPreview(description: string): string {
    const previewLength = 80;
    if (description.length <= previewLength) {
        return description;
    }

    const preview = description.slice(0, previewLength);
    const lastSpace = preview.lastIndexOf(' ');
    return `${preview.slice(0, lastSpace > 0 ? lastSpace : previewLength).trimEnd()}…`;
}

function confirmDelete(): void {
    if (!toneToDelete.value) {
        return;
    }

    router.delete(`/tones/${toneToDelete.value.id}`, {
        onFinish: () => {
            isDeleteDialogOpen.value = false;
            toneToDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Custom tones" />
    <main class="mx-auto w-full max-w-6xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p
                    class="text-muted-foreground text-xs font-semibold tracking-[0.16em] uppercase"
                >
                    Writing preferences
                </p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">
                    Custom tones
                </h1>
                <p class="text-muted-foreground mt-2 max-w-xl text-sm">
                    Keep reusable voice and style examples together.
                </p>
            </div>
            <Link
                href="/tones/create"
                class="inline-flex h-11 items-center gap-2 rounded-full bg-black px-5 text-sm font-semibold text-white transition hover:bg-neutral-800"
            >
                <Plus class="size-4" />
                New tone
            </Link>
        </header>

        <section
            v-if="tones.length"
            class="grid items-start gap-4 md:grid-cols-2"
        >
            <article
                v-for="tone in tones"
                :key="tone.id"
                class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-neutral-300 hover:shadow-lg"
            >
                <div class="h-1 w-full bg-gradient-to-r from-violet-500 via-indigo-500 to-sky-400"></div>
                <div class="flex min-w-0 flex-1 flex-col p-5 sm:p-6">
                    <div class="flex min-w-0 items-start justify-between gap-4">
                        <div class="min-w-0">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2.5 py-1 text-[11px] font-semibold tracking-wide text-violet-700"
                            >
                                <Sparkles class="size-3.5" />
                                CUSTOM TONE
                            </span>
                            <h2 class="mt-3 truncate text-xl font-semibold tracking-tight text-neutral-900">
                                {{ tone.name }}
                            </h2>
                        </div>
                        <div class="flex shrink-0 items-center gap-1.5">
                            <Link
                                :href="`/tones/${tone.id}/edit`"
                                class="inline-flex size-9 items-center justify-center rounded-xl text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900"
                                :aria-label="`Edit ${tone.name}`"
                                :title="`Edit ${tone.name}`"
                            >
                                <Pencil class="size-4" />
                            </Link>
                            <button
                                type="button"
                                class="inline-flex size-9 items-center justify-center rounded-xl text-neutral-500 transition hover:bg-red-50 hover:text-red-600"
                                :aria-label="`Delete ${tone.name}`"
                                :title="`Delete ${tone.name}`"
                                @click="deleteTone(tone)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                    </div>

                    <div class="mt-5 min-w-0">
                        <p class="text-[11px] font-semibold tracking-[0.12em] text-neutral-400 uppercase">
                            Voice description
                        </p>
                        <p class="mt-2 text-sm leading-6 text-neutral-600 whitespace-pre-wrap break-words">
                            {{ descriptionPreview(tone.description) }}
                        </p>

                        <div class="mt-5">
                            <p class="text-[11px] font-semibold tracking-[0.12em] text-neutral-400 uppercase">
                                Sample
                            </p>
                            <blockquote
                                class="mt-2 line-clamp-2 rounded-xl border border-neutral-100 bg-neutral-50/80 px-4 py-3.5 text-sm leading-6 text-neutral-600 whitespace-pre-wrap break-words"
                            >
                                {{ tone.sample_content }}
                            </blockquote>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end border-t border-neutral-100 pt-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-sm font-semibold text-violet-700 transition hover:bg-violet-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600"
                            :aria-label="`See more about ${tone.name}`"
                            aria-haspopup="dialog"
                            @click="viewTone(tone)"
                        >
                            See more
                            <ChevronDown class="size-4" />
                        </button>
                    </div>
                </div>
            </article>
        </section>

        <section
            v-else
            class="rounded-2xl border border-neutral-200 bg-white px-6 py-14 text-center shadow-sm"
        >
            <h2 class="text-lg font-semibold">No custom tones yet</h2>
            <p class="text-muted-foreground mx-auto mt-2 max-w-md text-sm">
                Create a tone with a short description and a sample that
                captures the voice you want to reuse.
            </p>
            <Link
                href="/tones/create"
                class="mt-5 inline-flex items-center gap-2 text-sm font-medium underline underline-offset-4"
            >
                <Plus class="size-4" />
                Create your first tone
            </Link>
        </section>

        <Dialog v-model:open="isToneDialogOpen">
            <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ toneToView?.name }}</DialogTitle>
                    <DialogDescription>
                        Full custom tone details and sample content.
                    </DialogDescription>
                </DialogHeader>
                <div v-if="toneToView" class="space-y-6">
                    <section>
                        <h3
                            class="text-[11px] font-semibold tracking-[0.12em] text-neutral-400 uppercase"
                        >
                            Voice description
                        </h3>
                        <p
                            class="mt-2 text-sm leading-6 text-neutral-700 whitespace-pre-wrap break-words"
                        >
                            {{ toneToView.description }}
                        </p>
                    </section>
                    <section>
                        <h3
                            class="text-[11px] font-semibold tracking-[0.12em] text-neutral-400 uppercase"
                        >
                            Sample
                        </h3>
                        <blockquote
                            class="mt-2 rounded-xl border border-neutral-100 bg-neutral-50 px-4 py-3.5 text-sm leading-6 text-neutral-700 whitespace-pre-wrap break-words"
                        >
                            {{ toneToView.sample_content }}
                        </blockquote>
                    </section>
                </div>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline">Close</Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="isDeleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete custom tone?</DialogTitle>
                    <DialogDescription>
                        Delete <strong>{{ toneToDelete?.name }}</strong
                        >? You can undo this for 5 seconds from the success
                        toast.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline">Cancel</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="confirmDelete">
                        Delete tone
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </main>
</template>
