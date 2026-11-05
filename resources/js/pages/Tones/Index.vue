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
                <p class="text-muted-foreground text-sm font-medium">
                    Writing preferences
                </p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">
                    Custom tones
                </h1>
                <p class="text-muted-foreground mt-2 max-w-xl text-sm">
                    Keep reusable voice and style examples together.
                </p>
            </div>
            <Link
                href="/tones/create"
                class="bg-primary text-primary-foreground inline-flex h-10 items-center gap-2 rounded-md px-4 text-sm font-medium hover:opacity-90"
            >
                <Plus class="size-4" />
                New tone
            </Link>
        </header>

        <section v-if="tones.length" class="divide-y border-y">
            <article
                v-for="tone in tones"
                :key="tone.id"
                class="flex flex-col gap-4 py-5 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold">{{ tone.name }}</h2>
                    <p
                        class="text-muted-foreground mt-1 text-sm whitespace-pre-wrap"
                    >
                        {{ tone.description }}
                    </p>
                    <blockquote
                        class="text-muted-foreground mt-3 border-l-2 pl-3 text-sm leading-6"
                    >
                        {{ tone.sample_content }}
                    </blockquote>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <Link
                        :href="`/tones/${tone.id}/edit`"
                        class="hover:bg-muted inline-flex size-9 items-center justify-center rounded-md border"
                        :aria-label="`Edit ${tone.name}`"
                        :title="`Edit ${tone.name}`"
                    >
                        <Pencil class="size-4" />
                    </Link>
                    <button
                        type="button"
                        class="text-destructive hover:bg-muted inline-flex size-9 items-center justify-center rounded-md border"
                        :aria-label="`Delete ${tone.name}`"
                        :title="`Delete ${tone.name}`"
                        @click="deleteTone(tone)"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </article>
        </section>

        <section v-else class="border-y py-12 text-center">
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
