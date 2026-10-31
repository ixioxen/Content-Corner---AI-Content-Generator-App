<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Ban, Check, Search, ShieldCheck } from '@lucide/vue';
import { reactive } from 'vue';

type UserRecord = {
    id: number;
    name: string;
    email: string;
    role: 'user' | 'admin';
    status: 'active' | 'suspended';
    credits_balance: number;
    created_at: string;
};

type PaginationLink = { url: string | null; label: string; active: boolean };
type CreditFormData = { amount: number; reason: string; idempotency_key: string };

const props = defineProps<{
    users: { data: UserRecord[]; links: PaginationLink[]; current_page: number; last_page: number };
    filters: { search?: string; status?: string };
    currentAdminId: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin overview', href: '/admin' },
            { title: 'Users', href: '/admin/users' },
        ],
    },
});

const searchForm = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
});

const creditForms = reactive<Record<number, ReturnType<typeof useForm<CreditFormData>>>>({});

function creditForm(userId: number) {
    creditForms[userId] ??= useForm<CreditFormData>({
        amount: 1,
        reason: '',
        idempotency_key: crypto.randomUUID(),
    });

    return creditForms[userId]!;
}

function searchUsers(): void {
    searchForm.get('/admin/users', { preserveState: true, preserveScroll: true, replace: true });
}

function changeStatus(user: UserRecord): void {
    const status = user.status === 'active' ? 'suspended' : 'active';
    const message = status === 'suspended'
        ? `Suspend ${user.name}? They will be signed out on their next request.`
        : `Reactivate ${user.name}?`;

    if (window.confirm(message)) {
        router.patch(`/admin/users/${user.id}/status`, { status }, { preserveScroll: true });
    }
}

function submitCreditAdjustment(user: UserRecord): void {
    const form = creditForm(user.id);
    form.post(`/admin/users/${user.id}/credits`, {
        preserveScroll: true,
        onSuccess: () => {
            form.amount = 1;
            form.reason = '';
            form.idempotency_key = crypto.randomUUID();
        },
    });
}
</script>

<template>
    <Head title="Manage users" />
    <main class="mx-auto w-full max-w-7xl space-y-8 p-6 lg:p-10">
        <header>
            <p class="flex items-center gap-2 text-sm font-medium text-muted-foreground"><ShieldCheck class="size-4" /> Operations</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">Manage users</h1>
            <p class="mt-2 text-sm text-muted-foreground">Review account status and make documented credit adjustments.</p>
        </header>

        <form class="flex flex-wrap items-end gap-3 border-y py-5" @submit.prevent="searchUsers">
            <label class="grid min-w-56 flex-1 gap-1.5 text-sm font-medium">
                Search users
                <input v-model="searchForm.search" type="search" placeholder="Name or email" class="h-10 rounded-md border bg-background px-3 font-normal outline-none focus-visible:ring-2 focus-visible:ring-ring" />
            </label>
            <label class="grid gap-1.5 text-sm font-medium">
                Status
                <select v-model="searchForm.status" class="h-10 min-w-36 rounded-md border bg-background px-3 font-normal outline-none focus-visible:ring-2 focus-visible:ring-ring">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                </select>
            </label>
            <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90" :disabled="searchForm.processing">
                <Search class="size-4" /> Search
            </button>
        </form>

        <section class="divide-y border-y">
            <article v-for="user in users.data" :key="user.id" class="grid gap-5 py-5 xl:grid-cols-[minmax(12rem,1fr)_auto_minmax(20rem,1.3fr)] xl:items-center">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-semibold">{{ user.name }}</h2>
                        <span class="rounded border px-1.5 py-0.5 text-xs text-muted-foreground">{{ user.role }}</span>
                    </div>
                    <p class="mt-1 truncate text-sm text-muted-foreground">{{ user.email }}</p>
                    <p class="mt-2 text-sm"><span class="font-medium tabular-nums">{{ user.credits_balance }}</span> credits · <span :class="user.status === 'active' ? 'text-emerald-700 dark:text-emerald-400' : 'text-destructive'">{{ user.status }}</span></p>
                </div>

                <button type="button" class="inline-flex h-9 items-center justify-center gap-2 rounded-md border px-3 text-sm font-medium hover:bg-muted disabled:cursor-not-allowed disabled:opacity-50" :disabled="user.id === currentAdminId && user.status === 'active'" @click="changeStatus(user)">
                    <Ban v-if="user.status === 'active'" class="size-4" />
                    <Check v-else class="size-4" />
                    {{ user.status === 'active' ? 'Suspend' : 'Reactivate' }}
                </button>

                <form class="grid gap-2 sm:grid-cols-[6rem_minmax(10rem,1fr)_auto]" @submit.prevent="submitCreditAdjustment(user)">
                    <label class="sr-only" :for="`credit-amount-${user.id}`">Credit adjustment amount</label>
                    <input :id="`credit-amount-${user.id}`" v-model.number="creditForm(user.id).amount" type="number" step="1" min="-1000000" max="1000000" required class="h-9 min-w-0 rounded-md border bg-background px-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring" aria-label="Credit adjustment amount" />
                    <input v-model="creditForm(user.id).reason" type="text" maxlength="255" required placeholder="Reason for adjustment" class="h-9 min-w-0 rounded-md border bg-background px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring" :aria-label="`Reason for adjustment for ${user.name}`" />
                    <button type="submit" class="h-9 rounded-md border px-3 text-sm font-medium hover:bg-muted disabled:opacity-50" :disabled="creditForm(user.id).processing || !creditForm(user.id).reason.trim()">Adjust</button>
                    <p v-if="creditForm(user.id).errors.amount || creditForm(user.id).errors.reason || creditForm(user.id).errors.idempotency_key" class="text-sm text-destructive sm:col-span-3">
                        {{ creditForm(user.id).errors.amount || creditForm(user.id).errors.reason || creditForm(user.id).errors.idempotency_key }}
                    </p>
                </form>
            </article>

            <p v-if="users.data.length === 0" class="py-10 text-center text-sm text-muted-foreground">No users match these filters.</p>
        </section>

        <nav v-if="users.last_page > 1" class="flex flex-wrap justify-center gap-2" aria-label="User list pages">
            <Link v-for="link in users.links" :key="link.label" :href="link.url ?? '/admin/users'" class="rounded-md border px-3 py-2 text-sm" :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'" :aria-disabled="!link.url" :tabindex="link.url ? 0 : -1">
                {{ link.label.replace('&laquo;', '‹').replace('&raquo;', '›') }}
            </Link>
        </nav>
    </main>
</template>