<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Sparkles, History } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

// -------------------------------------------------------
// Types — describe the shape of data passed from Laravel
// -------------------------------------------------------

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

// defineProps receives the data from ScriptGenerationController::create()
const props = defineProps<{
    creditsBalance: number;
    providerConfigured: boolean;
    brandProfiles: BrandProfile[];
    tones: Tone[];
}>();

// -------------------------------------------------------
// Breadcrumbs — shown at the top of the page layout
// -------------------------------------------------------
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Script Generator', href: '/scripts/create' },
        ],
    },
});

// -------------------------------------------------------
// Form — useForm() manages field values, errors, loading state
// -------------------------------------------------------
const form = useForm({
    // A unique ID generated fresh each page load.
    // The backend uses this to prevent duplicate submissions.
    request_uuid: crypto.randomUUID(),

    // --- Configuration ---
    script_type: 'product_promotion',
    platform: 'tiktok',
    tone: 'friendly',
    language: 'english',
    duration: 30,

    // --- Content Details ---
    target_audience: '',
    pain_points: '',
    product_name: '',
    product_description: '',
    product_benefits: '',
    additional_instructions: '',

    // --- Optional references ---
    brand_profile_id: '',
    custom_tone_id: '',
});

// -------------------------------------------------------
// submit() — called when the user clicks "Generate Script"
// -------------------------------------------------------
function submit(): void {
    form.post('/scripts', {
        onError: (errors) => {
            // If the error message contains 'refunded', the previous request
            // already failed. We generate a new UUID so the user can try again
            // fresh without being blocked by the duplicate-request check.
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
    <Head title="Script Generator" />

    <main class="mx-auto w-full max-w-3xl space-y-8 p-6 lg:p-10">

        <!-- Page Header -->
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-muted-foreground text-xs font-semibold tracking-[0.16em] uppercase">
                    AI writing workspace
                </p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">
                    Script Generator
                </h1>
                <p class="text-muted-foreground mt-2 text-sm">
                    Generate a structured short-form video script in seconds.
                </p>
            </div>
            <Link
                href="/scripts"
                class="inline-flex items-center gap-2 text-sm font-medium underline underline-offset-4 transition hover:text-neutral-500"
            >
                Script history
                <History class="size-4" />
            </Link>
        </header>

        <!-- Credit Balance -->
        <section
            class="flex items-center justify-between rounded-2xl border border-neutral-200 bg-white px-5 py-4 shadow-sm"
        >
            <span class="text-muted-foreground text-sm">Available credits</span>
            <strong class="text-xl tabular-nums">{{ creditsBalance }}</strong>
        </section>

        <!-- Warning: Gemini API key not set -->
        <p
            v-if="!providerConfigured"
            role="alert"
            class="rounded-xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-700"
        >
            Gemini is not configured. Add <code>GEMINI_API_KEY</code> to the
            server environment and restart Laravel before generating scripts.
        </p>

        <!-- Error: not enough credits -->
        <p
            v-if="$page.props.errors.credits"
            role="alert"
            class="rounded-xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-800"
        >
            {{ $page.props.errors.credits }}
        </p>

        <!-- Error: generation failed -->
        <p
            v-if="$page.props.errors.generation"
            role="alert"
            class="rounded-xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-800"
        >
            {{ $page.props.errors.generation }}
        </p>

        <!-- ================================================ -->
        <!-- Main Form -->
        <!-- ================================================ -->
        <form
            class="space-y-6 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-7"
            @submit.prevent="submit"
        >

            <!-- ============================================ -->
            <!-- SECTION 1 — Script Configuration            -->
            <!-- ============================================ -->
            <h2 class="text-base font-semibold">Configuration</h2>

            <!-- Script Type / Objective -->
            <div class="grid gap-2">
                <Label for="script_type">Script objective</Label>
                <select
                    id="script_type"
                    v-model="form.script_type"
                    class="h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                >
                    <option value="product_promotion">Product Promotion</option>
                    <option value="product_selling">Product Selling</option>
                    <option value="education">Education</option>
                    <option value="brand_awareness">Brand Awareness</option>
                    <option value="storytelling">Storytelling</option>
                    <option value="audience_engagement">Audience Engagement</option>
                </select>
                <p v-if="form.errors.script_type" class="text-destructive text-sm">
                    {{ form.errors.script_type }}
                </p>
            </div>

            <!-- Platform -->
            <div class="grid gap-2">
                <Label for="platform">Platform</Label>
                <select
                    id="platform"
                    v-model="form.platform"
                    class="h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                >
                    <option value="tiktok">TikTok</option>
                    <option value="facebook_reels">Facebook Reels</option>
                    <option value="youtube_shorts">YouTube Shorts</option>
                </select>
                <p v-if="form.errors.platform" class="text-destructive text-sm">
                    {{ form.errors.platform }}
                </p>
            </div>

            <!-- Tone & Language — side by side on larger screens -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                <!-- Tone -->
                <div class="grid gap-2">
                    <Label for="tone">Tone</Label>
                    <select
                        id="tone"
                        v-model="form.tone"
                        class="h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                    >
                        <option value="professional">Professional</option>
                        <option value="friendly">Friendly</option>
                        <option value="casual">Casual</option>
                        <option value="persuasive">Persuasive</option>
                        <option value="educational">Educational</option>
                        <option value="funny">Funny</option>
                        <option value="storytelling">Storytelling</option>
                        <option value="custom">Custom Tone</option>
                    </select>
                    <p v-if="form.errors.tone" class="text-destructive text-sm">
                        {{ form.errors.tone }}
                    </p>
                </div>

                <!-- Language -->
                <div class="grid gap-2">
                    <Label for="language">Language</Label>
                    <select
                        id="language"
                        v-model="form.language"
                        class="h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                    >
                        <option value="english">English</option>
                        <option value="burmese">Burmese</option>
                    </select>
                    <p v-if="form.errors.language" class="text-destructive text-sm">
                        {{ form.errors.language }}
                    </p>
                </div>
            </div>

            <!-- Duration -->
            <div class="grid gap-2">
                <Label for="duration">Video duration</Label>
                <select
                    id="duration"
                    v-model="form.duration"
                    class="h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                >
                    <option :value="15">15 seconds</option>
                    <option :value="30">30 seconds</option>
                    <option :value="60">60 seconds</option>
                    <option :value="90">90 seconds</option>
                    <option :value="120">120 seconds</option>
                </select>
                <p v-if="form.errors.duration" class="text-destructive text-sm">
                    {{ form.errors.duration }}
                </p>
            </div>

            <hr class="border-neutral-200" />

            <!-- ============================================ -->
            <!-- SECTION 2 — Product & Audience Details      -->
            <!-- ============================================ -->
            <h2 class="text-base font-semibold">Content Details</h2>

            <!-- Target Audience (required) -->
            <div class="grid gap-2">
                <Label for="target_audience">Target audience</Label>
                <input
                    id="target_audience"
                    v-model="form.target_audience"
                    type="text"
                    required
                    maxlength="500"
                    placeholder="e.g. Young adults 18–25 interested in fitness"
                    class="h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                />
                <p v-if="form.errors.target_audience" class="text-destructive text-sm">
                    {{ form.errors.target_audience }}
                </p>
            </div>

            <!-- Audience Pain Points (optional) -->
            <div class="grid gap-2">
                <Label for="pain_points">
                    Audience pain points
                    <span class="text-muted-foreground font-normal">(optional)</span>
                </Label>
                <input
                    id="pain_points"
                    v-model="form.pain_points"
                    type="text"
                    maxlength="500"
                    placeholder="e.g. Most protein shakes taste bad and are too expensive"
                    class="h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                />
                <p v-if="form.errors.pain_points" class="text-destructive text-sm">
                    {{ form.errors.pain_points }}
                </p>
            </div>

            <!-- Product / Service Name (required) -->
            <div class="grid gap-2">
                <Label for="product_name">Product / service name</Label>
                <input
                    id="product_name"
                    v-model="form.product_name"
                    type="text"
                    required
                    maxlength="255"
                    placeholder="e.g. FitPro Protein Shake"
                    class="h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                />
                <p v-if="form.errors.product_name" class="text-destructive text-sm">
                    {{ form.errors.product_name }}
                </p>
            </div>

            <!-- Product Description (required) -->
            <div class="grid gap-2">
                <Label for="product_description">Product description</Label>
                <textarea
                    id="product_description"
                    v-model="form.product_description"
                    required
                    maxlength="2000"
                    rows="4"
                    placeholder="Describe what your product is and what it does."
                    class="placeholder:text-muted-foreground w-full rounded-xl border border-neutral-300 bg-white px-3 py-3 text-sm shadow-xs transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                />
                <p v-if="form.errors.product_description" class="text-destructive text-sm">
                    {{ form.errors.product_description }}
                </p>
            </div>

            <!-- Product Benefits (optional) -->
            <div class="grid gap-2">
                <Label for="product_benefits">
                    Key benefits
                    <span class="text-muted-foreground font-normal">(optional)</span>
                </Label>
                <input
                    id="product_benefits"
                    v-model="form.product_benefits"
                    type="text"
                    maxlength="1000"
                    placeholder="e.g. Fast absorption, 25g protein, 3 great flavors"
                    class="h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                />
                <p v-if="form.errors.product_benefits" class="text-destructive text-sm">
                    {{ form.errors.product_benefits }}
                </p>
            </div>

            <!-- Additional Instructions (optional) -->
            <div class="grid gap-2">
                <Label for="additional_instructions">
                    Additional instructions
                    <span class="text-muted-foreground font-normal">(optional)</span>
                </Label>
                <textarea
                    id="additional_instructions"
                    v-model="form.additional_instructions"
                    maxlength="1000"
                    rows="3"
                    placeholder="Any extra notes for the AI (e.g. 'Use humor', 'Mention our sale ends Friday')"
                    class="placeholder:text-muted-foreground w-full rounded-xl border border-neutral-300 bg-white px-3 py-3 text-sm shadow-xs transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                />
                <p v-if="form.errors.additional_instructions" class="text-destructive text-sm">
                    {{ form.errors.additional_instructions }}
                </p>
            </div>

            <hr class="border-neutral-200" />

            <!-- ============================================ -->
            <!-- SECTION 3 — Optional References             -->
            <!-- ============================================ -->
            <h2 class="text-base font-semibold">Optional References</h2>

            <!-- Brand Profile -->
            <div class="grid gap-2">
                <Label for="brand_profile_id">
                    Brand profile
                    <span class="text-muted-foreground font-normal">(optional)</span>
                </Label>
                <select
                    id="brand_profile_id"
                    v-model="form.brand_profile_id"
                    class="h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                >
                    <option value="">No brand profile</option>
                    <option
                        v-for="profile in brandProfiles"
                        :key="profile.id"
                        :value="String(profile.id)"
                    >
                        {{ profile.brand_name }}<template v-if="profile.industry"> · {{ profile.industry }}</template>
                    </option>
                </select>
                <p v-if="!brandProfiles.length" class="text-muted-foreground text-xs">
                    No brand profiles yet.
                    <Link href="/brands/create" class="underline underline-offset-4">Create one</Link>
                    to reuse your brand voice.
                </p>
                <p v-if="form.errors.brand_profile_id" class="text-destructive text-sm">
                    {{ form.errors.brand_profile_id }}
                </p>
            </div>

            <!-- Custom Tone -->
            <div class="grid gap-2">
                <Label for="custom_tone_id">
                    Custom tone
                    <span class="text-muted-foreground font-normal">(optional)</span>
                </Label>
                <select
                    id="custom_tone_id"
                    v-model="form.custom_tone_id"
                    class="h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
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
                    <Link href="/tones/create" class="underline underline-offset-4">Create one</Link>
                    to guide the writing style.
                </p>
                <p v-if="form.errors.custom_tone_id" class="text-destructive text-sm">
                    {{ form.errors.custom_tone_id }}
                </p>
            </div>

            <!-- ============================================ -->
            <!-- Submit Button                               -->
            <!-- ============================================ -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-t pt-5">
                <p class="text-muted-foreground text-sm">
                    Each successful generation uses 1 credit. Failed requests are refunded.
                </p>
                <Button
                    type="submit"
                    :disabled="form.processing || !providerConfigured || creditsBalance < 1"
                >
                    <Sparkles class="mr-2 size-4" />
                    {{ form.processing ? 'Generating...' : 'Generate script · 1 credit' }}
                </Button>
            </div>

            <!-- Warning if no credits -->
            <p v-if="creditsBalance < 1" class="text-destructive text-sm">
                You need at least 1 credit to generate a script.
            </p>
        </form>

        <!-- Bottom links -->
        <div class="flex flex-wrap gap-4 text-sm">
            <Link
                href="/brands"
                class="text-muted-foreground hover:text-foreground underline underline-offset-4"
            >Manage brand profiles</Link>
            <Link
                href="/tones"
                class="text-muted-foreground hover:text-foreground underline underline-offset-4"
            >Manage custom tones</Link>
        </div>

    </main>
</template>
