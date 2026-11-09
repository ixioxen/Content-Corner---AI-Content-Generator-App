<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
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

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Custom tones', href: '/tones' }],
    },
});

function deleteTone(tone: Tone): void {
    toneToDelete.value = tone;
    isDeleteDialogOpen.value = true;
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

        <section v-if="tones.length" class="grid gap-4 md:grid-cols-2">
            <article
                v-for="tone in tones"
                :key="tone.id"
                class="flex min-w-0 flex-col gap-4 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition hover:border-neutral-300 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold">{{ tone.name }}</h2>
                    <p
                        class="text-muted-foreground mt-1 text-sm whitespace-pre-wrap"
                    >
                        {{ tone.description }}
                    </p>
                    <blockquote
                        class="text-muted-foreground mt-3 rounded-r-xl border-l-2 border-neutral-300 bg-neutral-50 py-3 pr-3 pl-4 text-sm leading-6"
                    >
                        {{ tone.sample_content }}
                    </blockquote>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <Link
                        :href="`/tones/${tone.id}/edit`"
                        class="inline-flex size-9 items-center justify-center rounded-full border border-neutral-300 transition hover:bg-neutral-100"
                        :aria-label="`Edit ${tone.name}`"
                        :title="`Edit ${tone.name}`"
                    >
                        <Pencil class="size-4" />
                    </Link>
                    <button
                        type="button"
                        class="inline-flex size-9 items-center justify-center rounded-full border border-neutral-300 text-neutral-600 transition hover:bg-neutral-100 hover:text-black"
                        :aria-label="`Delete ${tone.name}`"
                        :title="`Delete ${tone.name}`"
                        @click="deleteTone(tone)"
                    >
                        <Trash2 class="size-4" />
                    </button>
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
