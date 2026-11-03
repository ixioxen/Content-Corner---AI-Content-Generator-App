<script setup lang="ts">
import { CircleCheck, X } from '@lucide/vue';

defineProps<{
    message: string;
    onUndo: () => void;
    onCloseToast: () => void;
}>();

function undo(onUndo: () => void, onCloseToast: () => void): void {
    onUndo();
    onCloseToast();
}
</script>

<template>
    <div class="flex w-full items-center gap-3">
        <CircleCheck class="size-5 shrink-0 text-red-500" />
        <span class="min-w-0 flex-1 font-medium">{{ message }}</span>
        <button
            type="button"
            class="focus-visible:ring-ring inline-flex size-8 shrink-0 items-center justify-center rounded-md opacity-70 transition-opacity hover:opacity-100 focus-visible:ring-2 focus-visible:outline-none"
            aria-label="Undo this change"
            title="Undo this change"
            @pointerdown.stop
            @click="undo(onUndo, onCloseToast)"
        >
            <X class="size-6" />
        </button>
    </div>
</template>
