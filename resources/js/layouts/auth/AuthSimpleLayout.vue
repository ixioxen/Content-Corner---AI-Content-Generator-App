<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="auth-page flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-start gap-4">
                    <div class="space-y-2 text-start">
                        <h1 class="text-xl font-medium">Generate More Content</h1>
                        <p class="text-muted-foreground text-start text-sm">
                            login to your account to access the content generator and unlock a world of possibilities.
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>

<style scoped>
.auth-page {
    color: #000;
    background: #fff;
}

.auth-page :deep(h1),
.auth-page :deep(label) {
    color: #111;
}

.auth-page :deep(p) {
    color: #555;
}

.auth-page :deep(a) {
    color: #111;
}

.auth-page :deep(a:hover) {
    color: #555;
}

.auth-page :deep(.size-9) {
    color: #000 !important;
}

.auth-page :deep([data-slot='input']) {
    border-color: #ccc;
    color: #111;
    background: #fff;
}

.auth-page :deep([data-slot='input']::placeholder) {
    color: #777;
}

.auth-page :deep([data-slot='input']:focus-visible) {
    border-color: #111;
    --tw-ring-color: rgb(0 0 0 / 20%);
}

.auth-page :deep([data-slot='button']) {
    border-color: #000;
    color: #fff;
    background: #000;
}

.auth-page :deep([data-slot='button']:hover) {
    color: #fff;
    background: #222;
}

.auth-page :deep([data-slot='checkbox']) {
    border-color: #888;
}

.auth-page :deep([data-slot='checkbox'][data-state='checked']) {
    border-color: #000;
    background: #000;
}

.auth-page :deep(.text-green-600) {
    color: #333;
}
</style>
