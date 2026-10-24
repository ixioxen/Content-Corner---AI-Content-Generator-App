<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

type Tone = {
    id: number;
    name: string;
    description: string;
    sample_content: string;
};

const props = defineProps<{ tone: Tone | null }>();
const title = props.tone ? 'Edit tone' : 'Create a tone';

const form = useForm({
    name: props.tone?.name ?? '',
    description: props.tone?.description ?? '',
    sample_content: props.tone?.sample_content ?? '',
});
// const title = props.tone ? 'Edit tone' : 'Create a tone';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Custom tones', href: '/tones' },
            { title: 'Tone Form', href: '/tones' },
        ],
    },
});

function submit(): void {
    if (props.tone) {
        form.put(`/tones/${props.tone.id}`);
    } else {
        form.post('/tones');
    }
}
</script>

<template>
    <Head :title="title" />
    <main class="mx-auto w-full max-w-3xl space-y-8 p-6 lg:p-10">
        <header>
            <Link href="/tones" class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft class="size-4" />
                All tones
            </Link>
            <h1 class="mt-5 text-3xl font-semibold tracking-tight">{{ title }}</h1>
            <p class="mt-2 text-sm text-muted-foreground">Describe the voice and include a short example to make it recognizable.</p>
        </header>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input id="name" v-model="form.name" maxlength="100" required placeholder="Friendly and clear" />
                <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
            </div>

            <div class="grid gap-2">
                <Label for="description">Description</Label>
                <textarea id="description" v-model="form.description" required maxlength="5000" rows="4" placeholder="Describe the voice, audience, and writing habits." class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/40" />
                <p v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</p>
            </div>

            <div class="grid gap-2">
                <Label for="sample_content">Sample content</Label>
                <textarea id="sample_content" v-model="form.sample_content" required maxlength="10000" rows="7" placeholder="Paste a short example that represents this tone." class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/40" />
                <p v-if="form.errors.sample_content" class="text-sm text-destructive">{{ form.errors.sample_content }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t pt-5">
                <Button type="submit" :disabled="form.processing">
                    <Save class="mr-2 size-4" />
                    {{ form.processing ? 'Saving...' : 'Save tone' }}
                </Button>
                <Link href="/tones" class="text-sm text-muted-foreground hover:text-foreground">Cancel</Link>
            </div>
        </form>
    </main>
</template>