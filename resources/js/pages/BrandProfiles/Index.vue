<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, Pencil, Plus, Trash2 } from '@lucide/vue';
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

type BrandProfile = {
    id: number;
    brand_name: string;
    industry: string | null;
    target_audience: string | null;
    personality: string | null;
    writing_style: string | null;
    preferred_words: string[] | null;
    avoid_words: string[] | null;
    emoji_usage: string;
    language: string;
    main_platform: string | null;
    pain_points: string | null;
    additional_instructions: string | null;
};

defineProps<{
    profiles: BrandProfile[];
    profileLimit: number;
    canCreate: boolean;
}>();

const profileToDelete = ref<BrandProfile | null>(null);
const isDeleteDialogOpen = ref(false);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Brand profiles', href: '/brands' }],
    },
});

function deleteProfile(profile: BrandProfile): void {
    profileToDelete.value = profile;
    isDeleteDialogOpen.value = true;
}

function confirmDelete(): void {
    if (!profileToDelete.value) {
        return;
    }

    router.delete(`/brands/${profileToDelete.value.id}`, {
        onFinish: () => {
            isDeleteDialogOpen.value = false;
            profileToDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Brand profiles" />
    <main class="mx-auto w-full max-w-6xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p
                    class="text-muted-foreground text-xs font-semibold tracking-[0.16em] uppercase"
                >
                    Reusable brand identity
                </p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">
                    Brand profiles
                </h1>
                <p class="text-muted-foreground mt-2 max-w-xl text-sm">
                    Save your brand voice and audience details to reuse when
                    creating content.
                </p>
            </div>
            <Link
                v-if="canCreate"
                href="/brands/create"
                class="inline-flex h-11 items-center gap-2 rounded-full bg-black px-5 text-sm font-semibold text-white transition hover:bg-neutral-800"
            >
                <Plus class="size-4" />
                New brand profile
            </Link>
        </header>

        <p
            v-if="$page.props.errors.limit"
            role="alert"
            class="rounded-xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-800"
        >
            {{ $page.props.errors.limit }}
        </p>

        <p class="text-muted-foreground text-sm">
            Profiles: {{ profiles.length }} of {{ profileLimit }}
        </p>

        <section v-if="profiles.length" class="grid gap-4 md:grid-cols-2">
            <article
                v-for="profile in profiles"
                :key="profile.id"
                class="min-w-0 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm transition hover:border-neutral-300"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-start gap-3">
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-700"
                        >
                            <Building2 class="size-5" />
                        </span>
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-semibold">
                                {{ profile.brand_name }}
                            </h2>
                            <p class="text-muted-foreground mt-1 text-sm">
                                {{
                                    profile.industry || 'Industry not specified'
                                }}
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <Link
                            :href="`/brands/${profile.id}/edit`"
                            class="inline-flex size-9 items-center justify-center rounded-full border border-neutral-300 transition hover:bg-neutral-100"
                            :aria-label="`Edit ${profile.brand_name}`"
                            :title="`Edit ${profile.brand_name}`"
                        >
                            <Pencil class="size-4" />
                        </Link>
                        <button
                            type="button"
                            class="inline-flex size-9 items-center justify-center rounded-full border border-neutral-300 text-neutral-600 transition hover:bg-neutral-100 hover:text-black"
                            :aria-label="`Delete ${profile.brand_name}`"
                            :title="`Delete ${profile.brand_name}`"
                            @click="deleteProfile(profile)"
                        >
                            <Trash2 class="size-4" />
                        </button>
                    </div>
                </div>

                <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-medium">Language</dt>
                        <dd class="text-muted-foreground mt-1">
                            {{ profile.language }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium">Main platform</dt>
                        <dd class="text-muted-foreground mt-1">
                            {{ profile.main_platform || 'Not specified' }}
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium">Target audience</dt>
                        <dd
                            class="text-muted-foreground mt-1 line-clamp-3 whitespace-pre-wrap"
                        >
                            {{ profile.target_audience || 'Not specified' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium">Emoji usage</dt>
                        <dd class="text-muted-foreground mt-1 capitalize">
                            {{ profile.emoji_usage }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium">Preferred words</dt>
                        <dd class="text-muted-foreground mt-1">
                            {{ profile.preferred_words?.join(', ') || 'None' }}
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium">Avoid words</dt>
                        <dd class="text-muted-foreground mt-1">
                            {{ profile.avoid_words?.join(', ') || 'None' }}
                        </dd>
                    </div>
                </dl>
            </article>
        </section>

        <section
            v-else
            class="rounded-2xl border border-neutral-200 bg-white px-6 py-14 text-center shadow-sm"
        >
            <Building2 class="text-muted-foreground mx-auto size-10" />
            <h2 class="mt-4 text-lg font-semibold">No brand profiles yet</h2>
            <p class="text-muted-foreground mx-auto mt-2 max-w-md text-sm">
                Create a profile to save your brand voice, audience, language,
                and content preferences.
            </p>
            <Link
                v-if="canCreate"
                href="/brands/create"
                class="mt-5 inline-flex items-center gap-2 text-sm font-medium underline underline-offset-4"
            >
                <Plus class="size-4" />
                Create your first brand profile
            </Link>
        </section>

        <Dialog v-model:open="isDeleteDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete brand profile?</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete
                        <strong>{{ profileToDelete?.brand_name }}</strong
                        >? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose as-child>
                        <Button variant="outline">Cancel</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="confirmDelete">
                        Delete profile
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </main>
</template>
