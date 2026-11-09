<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <main
        class="auth-card-page flex min-h-svh flex-col items-center justify-center px-5 py-10 sm:px-8"
    >
        <div class="auth-card-frame flex w-full max-w-md flex-col gap-5">
            <Link
                :href="home()"
                class="auth-brand flex items-center gap-2 self-center font-medium"
                aria-label="Home"
            >
                <span
                    class="auth-mark flex h-10 w-10 items-center justify-center"
                >
                    <AppLogoIcon class="size-6 fill-current text-white" />
                </span>
            </Link>

            <div class="flex flex-col">
                <Card class="auth-card rounded-2xl">
                    <CardHeader class="px-8 pt-8 pb-0 text-center sm:px-10">
                        <CardTitle class="text-xl">{{ title }}</CardTitle>
                        <CardDescription>
                            {{ description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-8 py-8 sm:px-10">
                        <slot />
                    </CardContent>
                </Card>
            </div>
        </div>
    </main>
</template>

<style scoped>
.auth-card-page {
    color: #111;
    background:
        radial-gradient(
            ellipse at 50% 0%,
            rgb(255 255 255 / 90%),
            transparent 58%
        ),
        #f4f4f5;
}

.auth-brand {
    border-radius: 12px;
}

.auth-brand:focus-visible {
    outline: 2px solid #111;
    outline-offset: 4px;
}

.auth-mark {
    border-radius: 12px;
    background: #000;
    box-shadow: 0 5px 16px rgb(0 0 0 / 14%);
}

.auth-card {
    overflow: hidden;
    border-color: #e4e4e7;
    color: #18181b;
    background: #fff;
    box-shadow:
        0 24px 60px rgb(0 0 0 / 7%),
        0 2px 8px rgb(0 0 0 / 3%);
}

.auth-card-page :deep([data-slot='card-title']) {
    color: #111;
    font-weight: 650;
    letter-spacing: -0.035em;
}

.auth-card-page :deep([data-slot='card-description']) {
    color: #71717a;
    line-height: 1.6;
}

.auth-card-page :deep(label) {
    color: #27272a;
    font-size: 13px;
    font-weight: 600;
}

.auth-card-page :deep(a) {
    color: #18181b;
    text-decoration-color: #a1a1aa;
    text-underline-offset: 4px;
}

.auth-card-page :deep(a:hover) {
    color: #52525b;
}

.auth-card-page :deep([data-slot='input']) {
    height: 44px;
    border-color: #d4d4d8;
    border-radius: 10px;
    color: #18181b;
    background: #fff;
    box-shadow: none;
}

.auth-card-page :deep([data-slot='input']::placeholder) {
    color: #a1a1aa;
}

.auth-card-page :deep([data-slot='input']:focus-visible) {
    border-color: #27272a;
    --tw-ring-color: rgb(24 24 27 / 13%);
}

.auth-card-page :deep([data-slot='button']) {
    min-height: 44px;
    border-radius: 10px;
    font-weight: 600;
}

.auth-card-page :deep([data-slot='button']:not([data-variant='outline'])) {
    border-color: #000;
    color: #fff;
    background: #000;
}

.auth-card-page
    :deep([data-slot='button']:not([data-variant='outline']):hover) {
    color: #fff;
    background: #27272a;
}

.auth-card-page :deep([data-slot='button'][data-variant='outline']) {
    border-color: #d4d4d8;
    color: #18181b;
    background: #fff;
}

.auth-card-page :deep([data-slot='button'][data-variant='outline']:hover) {
    border-color: #a1a1aa;
    color: #09090b;
    background: #f4f4f5;
}

.auth-card-page :deep([data-slot='checkbox'][data-state='checked']) {
    border-color: #000;
    background: #000;
}

.auth-card-page :deep([data-slot='input-otp-slot']) {
    border-color: #d4d4d8;
    color: #18181b;
    background: #fff;
}

.auth-card-page :deep(.text-foreground) {
    color: #18181b;
}

.auth-card-page :deep(.text-muted-foreground) {
    color: #71717a;
}

.auth-card-page :deep(.text-green-600) {
    color: #27272a;
}
</style>
