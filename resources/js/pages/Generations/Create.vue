<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, Sparkles } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

type BrandProfile = {
    id: number;
    brand_name: string;
    industry: string | null;
    language: string;
};

type Tone = {
    id: number;
    name: string;
    description: string;
};

const props = defineProps<{
    creditsBalance: number;
    providerConfigured: boolean;
    brandProfiles: BrandProfile[];
    tones: Tone[];
}>();

const form = useForm({
    request_uuid: crypto.randomUUID(),
    content_type: 'social_post',
    brief: '',
    brand_profile_id: '',
    custom_tone_id: '',
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Generate content', href: '/generate' },
        ],
    },
});

function submit(): void {
    form.post('/generate', {
        onError: (errors) => {
            const generationError = Object.entries(errors).find(
                ([key]) => key === 'generation',
            )?.[1];

            if (generationError?.includes('refunded')) {
                form.request_uuid = crypto.randomUUID();
            }
        },
    });
}
</script>

<template>
    <Head title="Generate content" />
    <main class="mx-auto w-full max-w-3xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-muted-foreground text-sm font-medium">
                    AI writing workspace
                </p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">
                    Generate content
                </h1>
                <p class="text-muted-foreground mt-2 text-sm">
                    Create a social post, marketing copy, or blog outline in
                    seconds.
                </p>
            </div>
            <Link
                href="/generations"
                class="inline-flex items-center gap-2 text-sm font-medium underline underline-offset-4"
            >
                Generation history
                <ArrowRight class="size-4" />
            </Link>
        </header>

        <section class="flex items-center justify-between border-y py-4">
            <span class="text-muted-foreground text-sm">Available credits</span>
            <strong class="text-lg tabular-nums">{{ creditsBalance }}</strong>
        </section>

        <p
            v-if="!providerConfigured"
            role="alert"
            class="rounded-md border border-amber-500/40 bg-amber-500/5 px-4 py-3 text-sm"
        >
            Gemini is not configured. Add <code>GEMINI_API_KEY</code> to the
            server environment and restart Laravel before generating content.
        </p>
        <p
            v-if="$page.props.errors.credits"
            role="alert"
            class="border-destructive/40 text-destructive rounded-md border px-4 py-3 text-sm"
        >
            {{ $page.props.errors.credits }}
        </p>
        <p
            v-if="$page.props.errors.generation"
            role="alert"
            class="border-destructive/40 text-destructive rounded-md border px-4 py-3 text-sm"
        >
            {{ $page.props.errors.generation }}
        </p>

        <form
            class="space-y-6 rounded-xl border p-5 sm:p-7"
            @submit.prevent="submit"
        >
            <div class="grid gap-2">
                <Label for="content_type">Content type</Label>
                <select
                    id="content_type"
                    v-model="form.content_type"
                    class="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/40 h-10 w-full rounded-md border px-3 text-sm outline-none focus-visible:ring-2"
                >
                    <option value="social_post">Social media post</option>
                    <option value="marketing_copy">Marketing copy</option>
                    <option value="blog_outline">Blog outline</option>
                </select>
                <p
                    v-if="form.errors.content_type"
                    class="text-destructive text-sm"
                >
                    {{ form.errors.content_type }}
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="brief">What would you like to create?</Label>
                <textarea
                    id="brief"
                    v-model="form.brief"
                    required
                    minlength="10"
                    maxlength="5000"
                    rows="6"
                    placeholder="Describe the topic, audience, key points, offer, and desired call to action."
                    class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/40 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-2"
                />
                <p class="text-muted-foreground text-xs">
                    Do not include passwords, API keys, or other sensitive
                    information.
                </p>
                <p v-if="form.errors.brief" class="text-destructive text-sm">
                    {{ form.errors.brief }}
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="brand_profile_id"
                    >Brand profile
                    <span class="text-muted-foreground font-normal"
                        >(optional)</span
                    ></Label
                >
                <select
                    id="brand_profile_id"
                    v-model="form.brand_profile_id"
                    class="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/40 h-10 w-full rounded-md border px-3 text-sm outline-none focus-visible:ring-2"
                >
                    <option value="">No brand profile</option>
                    <option
                        v-for="profile in brandProfiles"
                        :key="profile.id"
                        :value="String(profile.id)"
                    >
                        {{ profile.brand_name
                        }}<template v-if="profile.industry">
                            · {{ profile.industry }}</template
                        >
                    </option>
                </select>
                <p
                    v-if="!brandProfiles.length"
                    class="text-muted-foreground text-xs"
                >
                    No brand profiles yet.
                    <Link
                        href="/brands/create"
                        class="underline underline-offset-4"
                        >Create one</Link
                    >
                    to reuse your brand voice.
                </p>
                <p
                    v-if="form.errors.brand_profile_id"
                    class="text-destructive text-sm"
                >
                    {{ form.errors.brand_profile_id }}
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="custom_tone_id"
                    >Custom tone
                    <span class="text-muted-foreground font-normal"
                        >(optional)</span
                    ></Label
                >
                <select
                    id="custom_tone_id"
                    v-model="form.custom_tone_id"
                    class="border-input bg-background focus-visible:border-ring focus-visible:ring-ring/40 h-10 w-full rounded-md border px-3 text-sm outline-none focus-visible:ring-2"
                >
                    <option value="">Default AI voice</option>
                    <option
                        v-for="tone in tones"
                        :key="tone.id"
                        :value="String(tone.id)"
                    >
                        {{ tone.name }}
                    </option>
                </select>
                <p v-if="!tones.length" class="text-muted-foreground text-xs">
                    No custom tones yet.
                    <Link
                        href="/tones/create"
                        class="underline underline-offset-4"
                        >Create one</Link
                    >
                    to guide the writing style.
                </p>
                <p
                    v-if="form.errors.custom_tone_id"
                    class="text-destructive text-sm"
                >
                    {{ form.errors.custom_tone_id }}
                </p>
            </div>

            <div
                class="flex flex-wrap items-center justify-between gap-3 border-t pt-5"
            >
                <p class="text-muted-foreground text-sm">
                    Each successful generation uses 1 credit. Failed requests
                    are refunded.
                </p>
                <Button
                    type="submit"
                    :disabled="
                        form.processing ||
                        !providerConfigured ||
                        creditsBalance < 1
                    "
                >
                    <Sparkles class="mr-2 size-4" />
                    {{
                        form.processing
                            ? 'Generating...'
                            : 'Generate content · 1 credit'
                    }}
                </Button>
            </div>
            <p v-if="creditsBalance < 1" class="text-destructive text-sm">
                You need at least 1 credit to generate content.
            </p>
        </form>

        <div class="flex flex-wrap gap-4 text-sm">
            <Link
                href="/brands"
                class="text-muted-foreground hover:text-foreground underline underline-offset-4"
                >Manage brand profiles</Link
            >
            <Link
                href="/tones"
                class="text-muted-foreground hover:text-foreground underline underline-offset-4"
                >Manage custom tones</Link
            >
        </div>
    </main>
</template>
