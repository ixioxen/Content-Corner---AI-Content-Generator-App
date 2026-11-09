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
    <main
        class="auth-page flex min-h-svh flex-col items-center justify-center px-5 py-10 sm:px-8"
    >
        <div class="auth-frame w-full max-w-md">
            <Link :href="home()" class="auth-brand" aria-label="Home">
                <span class="auth-mark">
                    <AppLogoIcon class="size-6 fill-current text-white" />
                </span>
            </Link>

            <section class="auth-card">
                <header class="auth-heading">
                    <h1>Generate More Content</h1>
                    <p>
                        login to your account to access the content generator
                        and unlock a world of possibilities.
                    </p>
                </header>
                <div class="auth-content">
                    <slot />
                </div>
            </section>
        </div>
    </main>
</template>

<style scoped>
.auth-page {
    color: #111;
    background:
        radial-gradient(
            ellipse at 50% 0%,
            rgb(255 255 255 / 90%),
            transparent 58%
        ),
        #f4f4f5;
}

.auth-frame {
    max-width: 440px;
}

.auth-brand {
    display: flex;
    width: fit-content;
    margin: 0 auto 20px;
    border-radius: 12px;
}

.auth-brand:focus-visible {
    outline: 2px solid #111;
    outline-offset: 4px;
}

.auth-mark {
    display: flex;
    width: 42px;
    height: 42px;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: #000;
    box-shadow: 0 5px 16px rgb(0 0 0 / 14%);
}

.auth-card {
    overflow: hidden;
    border: 1px solid #e4e4e7;
    border-radius: 20px;
    background: #fff;
    box-shadow:
        0 24px 60px rgb(0 0 0 / 7%),
        0 2px 8px rgb(0 0 0 / 3%);
}

.auth-heading {
    padding: 32px 32px 24px;
}

.auth-heading h1 {
    margin: 0;
    color: #111;
    font-size: 25px;
    font-weight: 650;
    letter-spacing: -0.045em;
    line-height: 1.2;
}

.auth-heading p {
    margin: 9px 0 0;
    color: #71717a;
    font-size: 14px;
    line-height: 1.65;
}

.auth-content {
    padding: 0 32px 32px;
}

.auth-page :deep(label) {
    color: #27272a;
    font-size: 13px;
    font-weight: 600;
}

.auth-page :deep(a) {
    color: #18181b;
    text-decoration-color: #a1a1aa;
    text-underline-offset: 4px;
    transition:
        color 150ms ease,
        text-decoration-color 150ms ease;
}

.auth-page :deep(a:hover) {
    color: #52525b;
    text-decoration-color: currentColor;
}

.auth-page :deep([data-slot='input']) {
    height: 44px;
    border-color: #d4d4d8;
    border-radius: 10px;
    color: #18181b;
    background: #fff;
    box-shadow: none;
}

.auth-page :deep([data-slot='input']::placeholder) {
    color: #a1a1aa;
}

.auth-page :deep([data-slot='input']:focus-visible) {
    border-color: #27272a;
    --tw-ring-color: rgb(24 24 27 / 13%);
}

.auth-page :deep([data-slot='button']) {
    min-height: 44px;
    border-radius: 10px;
    font-weight: 600;
    transition:
        background-color 150ms ease,
        color 150ms ease,
        border-color 150ms ease;
}

.auth-page :deep([data-slot='button']:not([data-variant='outline'])) {
    border-color: #000;
    color: #fff;
    background: #000;
}

.auth-page :deep([data-slot='button']:not([data-variant='outline']):hover) {
    color: #fff;
    background: #27272a;
}

.auth-page :deep([data-slot='button'][data-variant='outline']) {
    border-color: #d4d4d8;
    color: #18181b;
    background: #fff;
}

.auth-page :deep([data-slot='button'][data-variant='outline']:hover) {
    border-color: #a1a1aa;
    color: #09090b;
    background: #f4f4f5;
}

.auth-page :deep([data-slot='checkbox']) {
    border-color: #a1a1aa;
    border-radius: 5px;
}

.auth-page :deep([data-slot='checkbox'][data-state='checked']) {
    border-color: #000;
    background: #000;
}

.auth-page :deep([data-slot='input-otp-slot']) {
    border-color: #d4d4d8;
    color: #18181b;
    background: #fff;
}

.auth-page :deep(.text-foreground) {
    color: #18181b;
}

.auth-page :deep([data-slot='separator']) {
    background: #e4e4e7;
}

.auth-page :deep(img) {
    filter: grayscale(1);
}

.auth-page :deep(.text-muted-foreground) {
    color: #71717a;
}

.auth-page :deep(.text-green-600) {
    color: #27272a;
}

@media (max-width: 480px) {
    .auth-heading {
        padding: 26px 24px 20px;
    }

    .auth-content {
        padding: 0 24px 26px;
    }
}
</style>
