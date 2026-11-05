import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import UndoToast from '@/components/UndoToast.vue';
import type { FlashToast } from '@/types/ui';

export function initializeFlashToast(): void {
    router.on('flash', (event) => {
        const flash = (event as CustomEvent).detail?.flash;
        const data = flash?.toast as FlashToast | undefined;

        if (!data) {
            return;
        }

        if (data.undoUrl) {
            const undoUrl = data.undoUrl;

            toast[data.type](data.message, {
                component: UndoToast,
                componentProps: {
                    message: data.message,
                    onUndo: () => {
                        router.post(
                            undoUrl,
                            {},
                            {
                                preserveScroll: true,
                                onError: (errors) => {
                                    toast.error(
                                        errors.undo ??
                                            'This change could not be undone.',
                                    );
                                },
                            },
                        );
                    },
                },
                duration: 5000,
            });

            return;
        }

        toast[data.type](data.message);
    });
}
