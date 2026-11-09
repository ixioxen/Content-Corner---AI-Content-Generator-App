<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, X } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

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

const props = defineProps<{ profile: BrandProfile | null }>();
const title = props.profile ? 'Edit brand profile' : 'Create a brand profile';
const languages = [
    'English',
    'Burmese',
    'Thai',
    'Japanese',
    'Chinese',
    'Spanish',
];
const platforms = [
    'Facebook',
    'Instagram',
    'LinkedIn',
    'X / Twitter',
    'TikTok',
    'YouTube',
    'Email',
    'Website / Blog',
];
const languageChoice = ref(
    props.profile?.language && languages.includes(props.profile.language)
        ? props.profile.language
        : props.profile?.language
          ? 'Custom'
          : 'English',
);
const customLanguage = ref(
    languageChoice.value === 'Custom' ? (props.profile?.language ?? '') : '',
);
const preferredWordDraft = ref('');
const avoidWordDraft = ref('');

const form = useForm({
    brand_name: props.profile?.brand_name ?? '',
    industry: props.profile?.industry ?? '',
    target_audience: props.profile?.target_audience ?? '',
    personality: props.profile?.personality ?? '',
    writing_style: props.profile?.writing_style ?? '',
    preferred_words: props.profile?.preferred_words ?? [],
    avoid_words: props.profile?.avoid_words ?? [],
    emoji_usage: props.profile?.emoji_usage ?? 'moderate',
    language: props.profile?.language ?? 'English',
    main_platform: props.profile?.main_platform ?? '',
    pain_points: props.profile?.pain_points ?? '',
    additional_instructions: props.profile?.additional_instructions ?? '',
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Brand profiles', href: '/brands' },
            { title: 'Brand profile form', href: '/brands' },
        ],
    },
});

function addWords(field: 'preferred_words' | 'avoid_words'): void {
    const draft =
        field === 'preferred_words' ? preferredWordDraft : avoidWordDraft;
    const words =
        field === 'preferred_words' ? form.preferred_words : form.avoid_words;

    for (const word of draft.value
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean)) {
        if (
            !words.some(
                (existing) =>
                    existing.toLocaleLowerCase() === word.toLocaleLowerCase(),
            )
        ) {
            words.push(word);
        }
    }

    draft.value = '';
}

function handleWordKeydown(
    event: KeyboardEvent,
    field: 'preferred_words' | 'avoid_words',
): void {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        addWords(field);
    }
}

function removeWord(
    field: 'preferred_words' | 'avoid_words',
    index: number,
): void {
    const words =
        field === 'preferred_words' ? form.preferred_words : form.avoid_words;
    words.splice(index, 1);
}

function wordsError(
    field: 'preferred_words' | 'avoid_words',
): string | undefined {
    return Object.entries(form.errors).find(
        ([key]) => key === field || key.startsWith(`${field}.`),
    )?.[1];
}

function submit(): void {
    form.language =
        languageChoice.value === 'Custom'
            ? customLanguage.value.trim()
            : languageChoice.value;

    if (props.profile) {
        form.put(`/brands/${props.profile.id}`);
    } else {
        form.post('/brands');
    }
}
</script>

<template>
    <Head :title="title" />
    <main class="mx-auto w-full max-w-3xl space-y-8 p-6 lg:p-10">
        <header>
            <Link
                href="/brands"
                class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 text-sm"
            >
                <ArrowLeft class="size-4" />
                All brand profiles
            </Link>
            <h1 class="mt-5 text-3xl font-bold tracking-tight">
                {{ title }}
            </h1>
            <p class="text-muted-foreground mt-2 text-sm">
                Set up the details and writing preferences that define this
                brand.
            </p>
        </header>

        <form class="space-y-7" @submit.prevent="submit">
            <section
                class="space-y-5 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
            >
                <h2 class="text-lg font-semibold">Brand identity</h2>

                <div class="grid gap-2">
                    <Label for="brand_name"
                        >Brand name <span aria-hidden="true">*</span></Label
                    >
                    <Input
                        id="brand_name"
                        v-model="form.brand_name"
                        maxlength="100"
                        required
                        placeholder="Cloud Coffee"
                    />
                    <p
                        v-if="form.errors.brand_name"
                        class="text-destructive text-sm"
                    >
                        {{ form.errors.brand_name }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="industry">Industry</Label>
                    <Input
                        id="industry"
                        v-model="form.industry"
                        maxlength="100"
                        placeholder="Food and beverage"
                    />
                    <p
                        v-if="form.errors.industry"
                        class="text-destructive text-sm"
                    >
                        {{ form.errors.industry }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="target_audience">Target audience</Label>
                    <textarea
                        id="target_audience"
                        v-model="form.target_audience"
                        maxlength="5000"
                        rows="3"
                        placeholder="Who do you want to reach?"
                        class="placeholder:text-muted-foreground w-full rounded-xl border border-neutral-300 bg-white px-3 py-3 text-sm shadow-xs transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                    />
                    <p
                        v-if="form.errors.target_audience"
                        class="text-destructive text-sm"
                    >
                        {{ form.errors.target_audience }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="personality">Brand personality</Label>
                    <textarea
                        id="personality"
                        v-model="form.personality"
                        maxlength="5000"
                        rows="3"
                        placeholder="For example: warm, playful, and trustworthy."
                        class="placeholder:text-muted-foreground w-full rounded-xl border border-neutral-300 bg-white px-3 py-3 text-sm shadow-xs transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                    />
                    <p
                        v-if="form.errors.personality"
                        class="text-destructive text-sm"
                    >
                        {{ form.errors.personality }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="writing_style">Writing style</Label>
                    <textarea
                        id="writing_style"
                        v-model="form.writing_style"
                        maxlength="5000"
                        rows="3"
                        placeholder="Describe the voice, sentence length, and style to use."
                        class="placeholder:text-muted-foreground w-full rounded-xl border border-neutral-300 bg-white px-3 py-3 text-sm shadow-xs transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                    />
                    <p
                        v-if="form.errors.writing_style"
                        class="text-destructive text-sm"
                    >
                        {{ form.errors.writing_style }}
                    </p>
                </div>
            </section>

            <section
                class="space-y-5 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
            >
                <h2 class="text-lg font-semibold">Content preferences</h2>

                <div class="grid gap-2">
                    <Label for="language"
                        >Preferred language
                        <span aria-hidden="true">*</span></Label
                    >
                    <select
                        id="language"
                        v-model="languageChoice"
                        class="h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                    >
                        <option
                            v-for="language in languages"
                            :key="language"
                            :value="language"
                        >
                            {{ language }}
                        </option>
                        <option value="Custom">Other language...</option>
                    </select>
                    <Input
                        v-if="languageChoice === 'Custom'"
                        v-model="customLanguage"
                        maxlength="100"
                        required
                        placeholder="Enter a language"
                        aria-label="Custom language"
                    />
                    <p
                        v-if="form.errors.language"
                        class="text-destructive text-sm"
                    >
                        {{ form.errors.language }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="main_platform">Main platform</Label>
                    <select
                        id="main_platform"
                        v-model="form.main_platform"
                        class="h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                    >
                        <option value="">Select a platform</option>
                        <option
                            v-for="platform in platforms"
                            :key="platform"
                            :value="platform"
                        >
                            {{ platform }}
                        </option>
                        <option value="Other">Other</option>
                    </select>
                    <p
                        v-if="form.errors.main_platform"
                        class="text-destructive text-sm"
                    >
                        {{ form.errors.main_platform }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="emoji_usage">Emoji usage</Label>
                    <select
                        id="emoji_usage"
                        v-model="form.emoji_usage"
                        class="h-11 w-full rounded-xl border border-neutral-300 bg-white px-3 text-sm transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                    >
                        <option value="never">Never</option>
                        <option value="minimal">Minimal</option>
                        <option value="moderate">Moderate</option>
                        <option value="frequent">Frequent</option>
                    </select>
                    <p
                        v-if="form.errors.emoji_usage"
                        class="text-destructive text-sm"
                    >
                        {{ form.errors.emoji_usage }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="pain_points">Audience pain points</Label>
                    <textarea
                        id="pain_points"
                        v-model="form.pain_points"
                        maxlength="5000"
                        rows="4"
                        placeholder="List the problems or needs your audience cares about."
                        class="placeholder:text-muted-foreground w-full rounded-xl border border-neutral-300 bg-white px-3 py-3 text-sm shadow-xs transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                    />
                    <p
                        v-if="form.errors.pain_points"
                        class="text-destructive text-sm"
                    >
                        {{ form.errors.pain_points }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="additional_instructions"
                        >Additional instructions</Label
                    >
                    <textarea
                        id="additional_instructions"
                        v-model="form.additional_instructions"
                        maxlength="5000"
                        rows="3"
                        placeholder="Anything else content creators should know."
                        class="placeholder:text-muted-foreground w-full rounded-xl border border-neutral-300 bg-white px-3 py-3 text-sm shadow-xs transition outline-none focus:border-black focus:ring-2 focus:ring-black/10"
                    />
                    <p
                        v-if="form.errors.additional_instructions"
                        class="text-destructive text-sm"
                    >
                        {{ form.errors.additional_instructions }}
                    </p>
                </div>
            </section>

            <section
                class="space-y-5 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6"
            >
                <h2 class="text-lg font-semibold">Word preferences</h2>

                <div class="grid gap-2">
                    <Label for="preferred_words">Preferred words</Label>
                    <div class="flex gap-2">
                        <Input
                            id="preferred_words"
                            v-model="preferredWordDraft"
                            maxlength="400"
                            placeholder="Add a word or comma-separated words"
                            @keydown="
                                handleWordKeydown($event, 'preferred_words')
                            "
                        />
                        <Button
                            type="button"
                            variant="outline"
                            @click="addWords('preferred_words')"
                            >Add</Button
                        >
                    </div>
                    <div
                        v-if="form.preferred_words.length"
                        class="flex flex-wrap gap-2"
                        aria-label="Preferred words"
                    >
                        <span
                            v-for="(word, index) in form.preferred_words"
                            :key="`${word}-${index}`"
                            class="bg-muted inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm"
                        >
                            {{ word }}
                            <button
                                type="button"
                                class="hover:bg-background rounded-full p-0.5"
                                :aria-label="`Remove ${word}`"
                                @click="removeWord('preferred_words', index)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </span>
                    </div>
                    <p class="text-muted-foreground text-xs">
                        Press Enter or Add to save each word as a tag.
                    </p>
                    <p
                        v-if="wordsError('preferred_words')"
                        class="text-destructive text-sm"
                    >
                        {{ wordsError('preferred_words') }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="avoid_words">Avoid words</Label>
                    <div class="flex gap-2">
                        <Input
                            id="avoid_words"
                            v-model="avoidWordDraft"
                            maxlength="400"
                            placeholder="Add a word or comma-separated words"
                            @keydown="handleWordKeydown($event, 'avoid_words')"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            @click="addWords('avoid_words')"
                            >Add</Button
                        >
                    </div>
                    <div
                        v-if="form.avoid_words.length"
                        class="flex flex-wrap gap-2"
                        aria-label="Avoid words"
                    >
                        <span
                            v-for="(word, index) in form.avoid_words"
                            :key="`${word}-${index}`"
                            class="bg-muted inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm"
                        >
                            {{ word }}
                            <button
                                type="button"
                                class="hover:bg-background rounded-full p-0.5"
                                :aria-label="`Remove ${word}`"
                                @click="removeWord('avoid_words', index)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </span>
                    </div>
                    <p class="text-muted-foreground text-xs">
                        Press Enter or Add to save each word as a tag.
                    </p>
                    <p
                        v-if="wordsError('avoid_words')"
                        class="text-destructive text-sm"
                    >
                        {{ wordsError('avoid_words') }}
                    </p>
                </div>
            </section>

            <p
                v-if="$page.props.errors.limit"
                role="alert"
                class="text-destructive text-sm"
            >
                {{ $page.props.errors.limit }}
            </p>

            <div class="flex flex-wrap items-center gap-3 border-t pt-5">
                <Button type="submit" :disabled="form.processing">
                    <Save class="mr-2 size-4" />
                    {{ form.processing ? 'Saving...' : 'Save brand profile' }}
                </Button>
                <Link
                    href="/brands"
                    class="text-muted-foreground hover:text-foreground text-sm"
                    >Cancel</Link
                >
            </div>
        </form>
    </main>
</template>
