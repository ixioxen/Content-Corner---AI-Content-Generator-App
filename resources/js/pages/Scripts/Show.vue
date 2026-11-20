<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Copy, RefreshCw, Sparkles } from '@lucide/vue';
import { ref } from 'vue';
import { dashboard } from '@/routes';

// -------------------------------------------------------
// Type — describes the shape of the script object
// passed from ScriptGenerationController::show()
// -------------------------------------------------------
type ScriptGenerate = {
    id: number;
    script_type: string;
    generate_script_text: string | null;
    failure_message: string | null;
    status: string;
    brand_profile_snapshot: { brand_name: string } | null;
    custom_tone_snapshot: { name: string } | null;
    input_data: {
        platform: string;
        tone: string;
        language: string;
        duration: number;
        target_audience: string;
        product_name: string;
    } | null;
    created_at: string;
};

const props = defineProps<{ script: ScriptGenerate }>();

// -------------------------------------------------------
// Breadcrumbs
// -------------------------------------------------------
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Script history', href: '/scripts' },
            { title: 'Generated script', href: '#' },
        ],
    },
});

// -------------------------------------------------------
// Reactive state for copy feedback
// -------------------------------------------------------
const copied = ref(false);       // true for 2 seconds after copying
const copyError = ref('');       // shown if clipboard write fails

// -------------------------------------------------------
// Regenerate form — POST to /scripts/{id}/regenerate
// We use useForm() so we get the loading state for free.
// -------------------------------------------------------
const regenerateForm = useForm({});

// -------------------------------------------------------
// Helpers — format values for display
// -------------------------------------------------------

// Convert raw script_type value to a human-readable label
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

// Format a UTC timestamp to the user's local date + time
function formatDate(date: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
}

// -------------------------------------------------------
// copyScript() — copies the script text to the clipboard
// -------------------------------------------------------
async function copyScript(): Promise<void> {
    // Clear any previous error
    copyError.value = '';

    // navigator.clipboard is the modern browser clipboard API
    try {
        await navigator.clipboard.writeText(props.script.generate_script_text ?? '');
        copied.value = true;

        // Reset the "Copied" label back to "Copy" after 2 seconds
        window.setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        copyError.value = 'Could not copy. Please select the text and copy it manually.';
    }
}

// -------------------------------------------------------
// regenerate() — submits a POST to create a new version
// of this script using the same inputs
// -------------------------------------------------------
function regenerate(): void {
    if (!window.confirm('Regenerate this script? This will use 1 credit.')) {
        return;
    }
    regenerateForm.post(`/scripts/${props.script.id}/regenerate`);
}
</script>

<template>
    <Head title="Generated script" />

    <main class="mx-auto w-full max-w-4xl space-y-8 p-6 lg:p-10">

        <!-- ================================================ -->
        <!-- Page Header                                       -->
        <!-- ================================================ -->
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <!-- Back link -->
                <Link
                    href="/scripts"
                    class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 text-sm transition"
                >
                    <ArrowLeft class="size-4" />
                    Script history
                </Link>

                <!-- Script type badge + date -->
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-700">
                        {{ scriptTypeLabel(script.script_type) }}
                    </span>
                    <time class="text-muted-foreground text-xs">
                        {{ formatDate(script.created_at) }}
                    </time>
                </div>

                <h1 class="mt-2 text-3xl font-bold tracking-tight">Generated script</h1>
            </div>

            <!-- Action buttons — top right -->
            <div class="flex items-center gap-2">

                <!-- New Script button -->
                <Link
                    href="/scripts/create"
                    class="inline-flex h-10 items-center gap-2 rounded-full border border-neutral-300 bg-white px-4 text-sm font-medium transition hover:bg-neutral-100"
                >
                    <Sparkles class="size-4" />
                    New
                </Link>

                <!-- Regenerate button -->
                <button
                    type="button"
                    :disabled="regenerateForm.processing || script.status !== 'completed'"
                    class="inline-flex h-10 items-center gap-2 rounded-full border border-neutral-300 bg-white px-4 text-sm font-medium transition hover:bg-neutral-100 disabled:opacity-50 disabled:cursor-not-allowed"
                    :title="script.status !== 'completed' ? 'Only completed scripts can be regenerated' : 'Regenerate this script (costs 1 credit)'"
                    @click="regenerate"
                >
                    <RefreshCw class="size-4" :class="{ 'animate-spin': regenerateForm.processing }" />
                    {{ regenerateForm.processing ? 'Regenerating...' : 'Regenerate' }}
                </button>
            </div>
        </header>

        <!-- Regenerate error -->
        <p
            v-if="$page.props.errors.generation"
            role="alert"
            class="rounded-xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-800"
        >
            {{ $page.props.errors.generation }}
        </p>
        <p
            v-if="$page.props.errors.credits"
            role="alert"
            class="rounded-xl border border-neutral-300 bg-white px-4 py-3 text-sm text-neutral-800"
        >
            {{ $page.props.errors.credits }}
        </p>

        <!-- ================================================ -->
        <!-- Main Content Card                                 -->
        <!-- ================================================ -->
        <section class="space-y-6 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-7">

            <!-- Input Summary (what the user entered) -->
            <div>
                <h2 class="text-sm font-semibold">Script details</h2>
                <div class="text-muted-foreground mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-sm sm:grid-cols-3">
                    <div v-if="script.input_data?.product_name">
                        <span class="font-medium text-neutral-700">Product: </span>
                        {{ script.input_data.product_name }}
                    </div>
                    <div v-if="script.input_data?.platform">
                        <span class="font-medium text-neutral-700">Platform: </span>
                        {{ script.input_data.platform }}
                    </div>
                    <div v-if="script.input_data?.tone">
                        <span class="font-medium text-neutral-700">Tone: </span>
                        {{ script.input_data.tone }}
                    </div>
                    <div v-if="script.input_data?.language">
                        <span class="font-medium text-neutral-700">Language: </span>
                        {{ script.input_data.language }}
                    </div>
                    <div v-if="script.input_data?.duration">
                        <span class="font-medium text-neutral-700">Duration: </span>
                        {{ script.input_data.duration }}s
                    </div>
                    <div v-if="script.input_data?.target_audience">
                        <span class="font-medium text-neutral-700">Audience: </span>
                        {{ script.input_data.target_audience }}
                    </div>
                </div>
            </div>

            <!-- Brand / Tone reference badges (if used) -->
            <div
                v-if="script.brand_profile_snapshot || script.custom_tone_snapshot"
                class="flex flex-wrap gap-2 border-y py-4 text-xs"
            >
                <span
                    v-if="script.brand_profile_snapshot"
                    class="bg-muted rounded-md px-2 py-1"
                >
                    Brand: {{ script.brand_profile_snapshot.brand_name }}
                </span>
                <span
                    v-if="script.custom_tone_snapshot"
                    class="bg-muted rounded-md px-2 py-1"
                >
                    Tone: {{ script.custom_tone_snapshot.name }}
                </span>
            </div>

            <!-- ============================================ -->
            <!-- FAILED state                                 -->
            <!-- ============================================ -->
            <div v-if="script.status === 'failed'">
                <p
                    role="alert"
                    class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                >
                    {{ script.failure_message ?? 'Script generation failed. Your credit was refunded.' }}
                </p>
                <p class="text-muted-foreground mt-2 text-sm">
                    You can try again by clicking
                    <Link href="/scripts/create" class="underline underline-offset-4">Generate a new script</Link>.
                </p>
            </div>

            <!-- ============================================ -->
            <!-- PENDING state                                -->
            <!-- ============================================ -->
            <div v-else-if="script.status === 'pending'">
                <p class="text-muted-foreground text-sm">
                    Your script is still being processed. Please refresh in a moment.
                </p>
            </div>

            <!-- ============================================ -->
            <!-- COMPLETED state — show the script           -->
            <!-- ============================================ -->
            <div v-else>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">Generated script</h2>

                    <!-- Copy button -->
                    <button
                        type="button"
                        class="hover:bg-muted inline-flex h-9 items-center gap-2 rounded-md border px-3 text-sm font-medium"
                        @click="copyScript"
                    >
                        <Copy class="size-4" />
                        {{ copied ? 'Copied!' : 'Copy' }}
                    </button>
                </div>

                <!-- Copy error (shown if clipboard API fails) -->
                <p
                    v-if="copyError"
                    role="alert"
                    class="text-destructive mt-2 text-sm"
                >
                    {{ copyError }}
                </p>

                <!-- Script text output -->
                <!-- whitespace-pre-wrap preserves line breaks from the AI response -->
                <div class="mt-4 rounded-xl bg-neutral-50 p-4 text-sm leading-7 whitespace-pre-wrap sm:p-6">
                    {{ script.generate_script_text }}
                </div>
            </div>

        </section>

        <!-- Bottom links -->
        <div class="flex flex-wrap gap-4 text-sm">
            <Link
                href="/scripts/create"
                class="text-muted-foreground hover:text-foreground underline underline-offset-4"
            >
                Generate another script
            </Link>
            <Link
                href="/scripts"
                class="text-muted-foreground hover:text-foreground underline underline-offset-4"
            >
                View all scripts
            </Link>
        </div>

    </main>
</template>
