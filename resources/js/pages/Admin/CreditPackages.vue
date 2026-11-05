<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Package } from '@lucide/vue';
import { reactive } from 'vue';

type CreditPackage = {
    id: number;
    name: string;
    credits_amount: number;
    price_amount: string;
    currency: string;
    is_active: boolean;
};

type PackageFormData = {
    name: string;
    credits_amount: number;
    price_amount: string;
    currency: string;
    is_active: boolean;
};

const props = defineProps<{ packages: CreditPackage[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin overview', href: '/admin' },
            { title: 'Credit packages', href: '/admin/credit-packages' },
        ],
    },
});

const createForm = useForm<PackageFormData>({
    name: '',
    credits_amount: 100,
    price_amount: '9.99',
    currency: 'USD',
    is_active: true,
});
const packageForms = reactive<Record<number, ReturnType<typeof useForm<PackageFormData>>>>({});

function packageForm(creditPackage: CreditPackage) {
    packageForms[creditPackage.id] ??= useForm<PackageFormData>({
        name: creditPackage.name,
        credits_amount: creditPackage.credits_amount,
        price_amount: creditPackage.price_amount,
        currency: creditPackage.currency,
        is_active: creditPackage.is_active,
    });

    return packageForms[creditPackage.id]!;
}

function createPackage(): void {
    createForm.post('/admin/credit-packages', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function savePackage(creditPackage: CreditPackage): void {
    const form = packageForm(creditPackage);
    form.patch(`/admin/credit-packages/${creditPackage.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Credit packages" />
    <main class="mx-auto w-full max-w-7xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="flex items-center gap-2 text-sm font-medium text-muted-foreground"><Package class="size-4" /> Administration</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">Credit packages</h1>
                <p class="mt-2 text-sm text-muted-foreground">Set the catalog users will see. Deactivate packages instead of deleting them to preserve purchase history.</p>
            </div>
            <Link href="/admin" class="inline-flex h-10 items-center gap-2 rounded-md border px-4 text-sm font-medium hover:bg-muted"><ArrowLeft class="size-4" /> Overview</Link>
        </header>

        <form class="space-y-4 rounded-xl border p-5" @submit.prevent="createPackage">
            <div>
                <h2 class="text-lg font-semibold">Add a package</h2>
                <p class="mt-1 text-sm text-muted-foreground">Prices and currencies are stored with each purchase when a customer checks out.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <label class="grid gap-1.5 text-sm font-medium lg:col-span-2">Package name
                    <input v-model="createForm.name" required maxlength="120" class="h-10 rounded-md border bg-background px-3 font-normal outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                    <span v-if="createForm.errors.name" class="text-destructive">{{ createForm.errors.name }}</span>
                </label>
                <label class="grid gap-1.5 text-sm font-medium">Credits
                    <input v-model.number="createForm.credits_amount" type="number" min="1" max="1000000" required class="h-10 rounded-md border bg-background px-3 font-normal outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                    <span v-if="createForm.errors.credits_amount" class="text-destructive">{{ createForm.errors.credits_amount }}</span>
                </label>
                <label class="grid gap-1.5 text-sm font-medium">Price
                    <input v-model="createForm.price_amount" type="number" min="0.01" max="99999999.99" step="0.01" required class="h-10 rounded-md border bg-background px-3 font-normal outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                    <span v-if="createForm.errors.price_amount" class="text-destructive">{{ createForm.errors.price_amount }}</span>
                </label>
                <label class="grid gap-1.5 text-sm font-medium">Currency
                    <input v-model="createForm.currency" required minlength="3" maxlength="3" pattern="[A-Za-z]{3}" class="h-10 rounded-md border bg-background px-3 uppercase font-normal outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                    <span v-if="createForm.errors.currency" class="text-destructive">{{ createForm.errors.currency }}</span>
                </label>
            </div>
            <button type="submit" class="h-10 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:opacity-90 disabled:opacity-50" :disabled="createForm.processing">Add package</button>
        </form>

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-semibold">Package catalog</h2>
                <p class="mt-1 text-sm text-muted-foreground">Package changes are recorded in the admin audit log.</p>
            </div>
            <div v-if="packages.length" class="divide-y border-y">
                <form v-for="creditPackage in packages" :key="creditPackage.id" class="grid gap-4 py-5 lg:grid-cols-[minmax(12rem,1fr)_repeat(4,minmax(7rem,0.6fr))_auto] lg:items-end" @submit.prevent="savePackage(creditPackage)">
                    <label class="grid gap-1.5 text-sm font-medium">Package name
                        <input v-model="packageForm(creditPackage).name" required maxlength="120" class="h-10 rounded-md border bg-background px-3 font-normal outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                    </label>
                    <label class="grid gap-1.5 text-sm font-medium">Credits
                        <input v-model.number="packageForm(creditPackage).credits_amount" type="number" min="1" max="1000000" required class="h-10 rounded-md border bg-background px-3 font-normal outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                    </label>
                    <label class="grid gap-1.5 text-sm font-medium">Price
                        <input v-model="packageForm(creditPackage).price_amount" type="number" min="0.01" max="99999999.99" step="0.01" required class="h-10 rounded-md border bg-background px-3 font-normal outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                    </label>
                    <label class="grid gap-1.5 text-sm font-medium">Currency
                        <input v-model="packageForm(creditPackage).currency" required minlength="3" maxlength="3" pattern="[A-Za-z]{3}" class="h-10 rounded-md border bg-background px-3 uppercase font-normal outline-none focus-visible:ring-2 focus-visible:ring-ring" />
                    </label>
                    <label class="flex h-10 items-center gap-2 text-sm font-medium">
                        <input v-model="packageForm(creditPackage).is_active" type="checkbox" class="size-4 accent-primary" />
                        Active
                    </label>
                    <button type="submit" class="h-10 rounded-md border px-4 text-sm font-medium hover:bg-muted disabled:opacity-50" :disabled="packageForm(creditPackage).processing">Save</button>
                    <p v-if="packageForm(creditPackage).errors.name || packageForm(creditPackage).errors.credits_amount || packageForm(creditPackage).errors.price_amount || packageForm(creditPackage).errors.currency || packageForm(creditPackage).errors.is_active" class="text-sm text-destructive lg:col-span-6">
                        {{ packageForm(creditPackage).errors.name || packageForm(creditPackage).errors.credits_amount || packageForm(creditPackage).errors.price_amount || packageForm(creditPackage).errors.currency || packageForm(creditPackage).errors.is_active }}
                    </p>
                </form>
            </div>
            <p v-else class="rounded-xl border px-6 py-10 text-center text-sm text-muted-foreground">No packages yet. Add one above to show it on the billing page.</p>
        </section>
    </main>
</template>
