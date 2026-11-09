<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="auth-split-page relative grid min-h-svh flex-col items-center justify-center sm:px-0 lg:grid-cols-[0.9fr_1.1fr] lg:px-0"
    >
        <div
            class="auth-split-panel relative hidden min-h-svh flex-col justify-between overflow-hidden p-10 text-white lg:flex xl:p-14"
        >
            <div class="auth-split-orbit" aria-hidden="true" />
            <Link
                :href="home()"
                class="auth-split-brand relative z-10 flex w-fit items-center gap-3 text-lg font-semibold"
            >
                <span class="auth-split-mark flex size-10 items-center justify-center rounded-xl">
                    <AppLogoIcon class="size-6 fill-current text-black" />
                </span>
                {{ name }}
            </Link>
            <div class="relative z-10 max-w-md pb-10">
                <span class="auth-split-rule mb-6 block h-px w-12" />
                <p class="text-sm leading-7 text-zinc-300">
                    {{ description }}
                </p>
            </div>
            <span class="relative z-10 text-xs tracking-wide text-zinc-500">
                {{ name }}
            </span>
        </div>
        <main class="auth-split-content flex min-h-svh w-full items-center justify-center px-5 py-10 sm:px-8 lg:px-12">
            <div class="auth-split-form w-full max-w-md">
                <Link
                    :href="home()"
                    class="auth-split-mobile-brand mb-9 flex w-fit items-center gap-3 text-base font-semibold lg:hidden"
                >
                    <span class="auth-split-mark flex size-9 items-center justify-center rounded-xl">
                        <AppLogoIcon class="size-5 fill-current text-black" />
                    </span>
                    {{ name }}
                </Link>
                <div class="mb-8 flex flex-col gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-muted-foreground text-sm leading-6" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </main>
    </div>
</template>

<style scoped>
.auth-split-page {
    color: #111;
    background: #fafafa;
}

.auth-split-panel {
    background:
        radial-gradient(ellipse at 10% 100%, rgb(255 255 255 / 10%), transparent 48%),
        #09090b;
}

.auth-split-orbit {
    position: absolute;
    right: -24%;
    bottom: 8%;
    width: min(74vw, 680px);
    aspect-ratio: 1;
    border: 1px solid rgb(255 255 255 / 10%);
    border-radius: 50%;
}

.auth-split-orbit::before,
.auth-split-orbit::after {
    position: absolute;
    border: 1px solid rgb(255 255 255 / 8%);
    border-radius: 50%;
    content: '';
}

.auth-split-orbit::before {
    inset: 9%;
}

.auth-split-orbit::after {
    inset: 20%;
}

.auth-split-mark {
    background: #fff;
}

.auth-split-rule {
    background: #a1a1aa;
}

.auth-split-content {
    background:
        radial-gradient(ellipse at 50% 0%, #fff, transparent 60%),
        #fafafa;
}

.auth-split-form {
    padding: 32px;
    border: 1px solid #e4e4e7;
    border-radius: 20px;
    background: #fff;
    box-shadow: 0 20px 56px rgb(0 0 0 / 6%);
}

.auth-split-form :deep(h1) {
    color: #111;
}

.auth-split-form :deep(label) {
    color: #27272a;
    font-size: 13px;
    font-weight: 600;
}

.auth-split-form :deep(a) {
    color: #18181b;
    text-decoration-color: #a1a1aa;
    text-underline-offset: 4px;
}

.auth-split-form :deep(a:hover) {
    color: #52525b;
}

.auth-split-form :deep([data-slot='input']) {
    height: 44px;
    border-color: #d4d4d8;
    border-radius: 10px;
    color: #18181b;
    background: #fff;
    box-shadow: none;
}

.auth-split-form :deep([data-slot='input']::placeholder) {
    color: #a1a1aa;
}

.auth-split-form :deep([data-slot='input']:focus-visible) {
    border-color: #27272a;
    --tw-ring-color: rgb(24 24 27 / 13%);
}

.auth-split-form :deep([data-slot='button']) {
    min-height: 44px;
    border-radius: 10px;
    font-weight: 600;
}

.auth-split-form :deep([data-slot='button']:not([data-variant='outline'])) {
    border-color: #000;
    color: #fff;
    background: #000;
}

.auth-split-form :deep([data-slot='button']:not([data-variant='outline']):hover) {
    color: #fff;
    background: #27272a;
}

.auth-split-form :deep([data-slot='button'][data-variant='outline']) {
    border-color: #d4d4d8;
    color: #18181b;
    background: #fff;
}

.auth-split-form :deep([data-slot='button'][data-variant='outline']:hover) {
    border-color: #a1a1aa;
    color: #09090b;
    background: #f4f4f5;
}

.auth-split-form :deep([data-slot='checkbox'][data-state='checked']) {
    border-color: #000;
    background: #000;
}

.auth-split-form :deep([data-slot='input-otp-slot']) {
    border-color: #d4d4d8;
    color: #18181b;
    background: #fff;
}

.auth-split-form :deep(.text-foreground) {
    color: #18181b;
}

.auth-split-form :deep(.text-muted-foreground) {
    color: #71717a;
}

.auth-split-form :deep(.text-green-600) {
    color: #27272a;
}

@media (max-width: 639px) {
    .auth-split-form {
        padding: 26px 22px;
        border-radius: 16px;
    }
}
</style>
