<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
    },
    {
        title: 'Security',
        href: editSecurity(),
    },
    {
        title: 'Appearance',
        href: editAppearance(),
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="space-y-6 p-4 py-6 sm:p-6">
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <div class="flex flex-col gap-6 lg:flex-row">
            <aside
                class="w-full shrink-0 rounded-2xl border border-neutral-200 bg-white p-3 shadow-sm lg:w-52"
            >
                <nav class="flex flex-col gap-1" aria-label="Settings">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start rounded-xl text-neutral-600 hover:bg-neutral-100 hover:text-black',
                            {
                                'bg-black text-white hover:bg-neutral-800 hover:text-white':
                                    isCurrentOrParentUrl(item.href),
                            },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-0 lg:hidden" />

            <div class="min-w-0 flex-1">
                <section
                    class="max-w-3xl space-y-8 rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-7"
                >
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
