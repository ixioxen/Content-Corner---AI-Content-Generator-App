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
    <main class="admin-page mx-auto w-full max-w-7xl space-y-8 p-6 lg:p-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="flex items-center gap-2 text-sm font-medium text-muted-foreground"><Package class="size-4" /> Administration</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">Credit packages</h1>
                <p class="mt-2 text-sm text-muted-foreground">Set the catalog users will see. Deactivate packages instead of deleting them to preserve purchase history.</p>
            </div>
            <Link href="/admin" class="inline-flex h-10 items-center gap-2 rounded-full border border-black/15 bg-white px-4 text-sm font-medium transition hover:bg-black hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2"><ArrowLeft class="size-4" /> Overview</Link>
        </header>

        <form class="form-card space-y-5 rounded-2xl border p-5 sm:p-6" @submit.prevent="createPackage">
            <div>
                <h2 class="text-lg font-semibold">Add a package</h2>
                <p class="mt-1 text-sm text-black/55">Prices and currencies are stored with each purchase when a customer checks out.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <label class="grid gap-1.5 text-sm font-medium lg:col-span-2">Package name
                    <input v-model="createForm.name" required maxlength="120" class="package-input h-10 rounded-xl border px-3 font-normal outline-none transition focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2" />
                    <span v-if="createForm.errors.name" class="text-destructive">{{ createForm.errors.name }}</span>
                </label>
                <label class="grid gap-1.5 text-sm font-medium">Credits
                    <input v-model.number="createForm.credits_amount" type="number" min="1" max="1000000" required class="package-input h-10 rounded-xl border px-3 font-normal outline-none transition focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2" />
                    <span v-if="createForm.errors.credits_amount" class="text-destructive">{{ createForm.errors.credits_amount }}</span>
                </label>
                <label class="grid gap-1.5 text-sm font-medium">Price
                    <input v-model="createForm.price_amount" type="number" min="0.01" max="99999999.99" step="0.01" required class="package-input h-10 rounded-xl border px-3 font-normal outline-none transition focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2" />
                    <span v-if="createForm.errors.price_amount" class="text-destructive">{{ createForm.errors.price_amount }}</span>
                </label>
                <label class="grid gap-1.5 text-sm font-medium">Currency
                    <input v-model="createForm.currency" required minlength="3" maxlength="3" pattern="[A-Za-z]{3}" class="package-input h-10 rounded-xl border px-3 uppercase font-normal outline-none transition focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2" />
                    <span v-if="createForm.errors.currency" class="text-destructive">{{ createForm.errors.currency }}</span>
                </label>
            </div>
            <button type="submit" class="h-10 rounded-full bg-black px-5 text-sm font-semibold text-white transition hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2 disabled:opacity-50" :disabled="createForm.processing">Add package</button>
        </form>

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-semibold">Package catalog</h2>
                <p class="mt-1 text-sm text-black/55">Package changes are recorded in the admin audit log.</p>
            </div>
            <div v-if="packages.length" class="package-list overflow-hidden rounded-2xl border">
                <form v-for="creditPackage in packages" :key="creditPackage.id" class="package-row grid gap-4 border-b border-black/10 p-5 last:border-b-0 sm:p-6 lg:grid-cols-[minmax(12rem,1fr)_repeat(4,minmax(7rem,0.6fr))_auto] lg:items-end" @submit.prevent="savePackage(creditPackage)">
                    <label class="grid gap-1.5 text-sm font-medium">Package name
                        <input v-model="packageForm(creditPackage).name" required maxlength="120" class="package-input h-10 rounded-xl border px-3 font-normal outline-none transition focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2" />
                    </label>
                    <label class="grid gap-1.5 text-sm font-medium">Credits
                        <input v-model.number="packageForm(creditPackage).credits_amount" type="number" min="1" max="1000000" required class="package-input h-10 rounded-xl border px-3 font-normal outline-none transition focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2" />
                    </label>
                    <label class="grid gap-1.5 text-sm font-medium">Price
                        <input v-model="packageForm(creditPackage).price_amount" type="number" min="0.01" max="99999999.99" step="0.01" required class="package-input h-10 rounded-xl border px-3 font-normal outline-none transition focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2" />
                    </label>
                    <label class="grid gap-1.5 text-sm font-medium">Currency
                        <input v-model="packageForm(creditPackage).currency" required minlength="3" maxlength="3" pattern="[A-Za-z]{3}" class="package-input h-10 rounded-xl border px-3 uppercase font-normal outline-none transition focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2" />
                    </label>
                    <label class="flex h-10 items-center gap-2 text-sm font-medium">
                        <input v-model="packageForm(creditPackage).is_active" type="checkbox" class="size-4 accent-black focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black" />
                        Active
                    </label>
                    <button type="submit" class="h-10 rounded-full border border-black/15 bg-white px-5 text-sm font-semibold transition hover:bg-black hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-black focus-visible:ring-offset-2 disabled:opacity-50" :disabled="packageForm(creditPackage).processing">Save</button>
                    <p v-if="packageForm(creditPackage).errors.name || packageForm(creditPackage).errors.credits_amount || packageForm(creditPackage).errors.price_amount || packageForm(creditPackage).errors.currency || packageForm(creditPackage).errors.is_active" class="text-sm text-destructive lg:col-span-6">
                        {{ packageForm(creditPackage).errors.name || packageForm(creditPackage).errors.credits_amount || packageForm(creditPackage).errors.price_amount || packageForm(creditPackage).errors.currency || packageForm(creditPackage).errors.is_active }}
                    </p>
                </form>
            </div>
            <p v-else class="rounded-2xl border border-dashed border-black/15 bg-white px-6 py-12 text-center text-sm text-black/55">No packages yet. Add one above to show it on the billing page.</p>
        </section>
    </main>
</template>

<style scoped>
.admin-page {
    color: #000;
    background: #fafafa;
}

.form-card,
.package-list {
    border-color: #e5e5e5;
    background: #fff;
}

.package-row:last-child {
    border-bottom: 0;
}

.package-input {
    border-color: rgb(0 0 0 / 15%);
    background: #fff;
}
</style>
