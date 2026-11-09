<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpenText,
    Check,
    CircleHelp,
    Clock3,
    CreditCard,
    FileText,
    History,
    MessageSquareText,
    ShieldCheck,
    Sparkles,
    WandSparkles,
} from '@lucide/vue';
import { login, register } from '@/routes';

type CreditPackage = {
    id: number;
    name: string;
    credits_amount: number;
    price_amount: string;
    currency: string;
};

defineProps<{
    stats: {
        creators: number;
        generatedContents: number;
        brandProfiles: number;
    };
    creditPackages: CreditPackage[];
}>();

const contentTypes = [
    { label: 'Social post', icon: MessageSquareText },
    { label: 'Marketing copy', icon: FileText },
    { label: 'Blog outline', icon: BookOpenText },
];

const steps = [
    {
        title: 'Create your account',
        description: 'Register and receive 20 credits, granted once. No card needed to start.',
        icon: Sparkles,
    },
    {
        title: 'Choose type and tone',
        description: 'Select social post, marketing copy or blog outline. Add one of your own custom tones if you like.',
        icon: WandSparkles,
    },
    {
        title: 'Generate and keep',
        description: 'Each successful generation costs 1 credit and is saved to your history. A failed one is refunded.',
        icon: History,
    },
];

const features = [
    {
        title: 'Three content types',
        description: 'Social media posts, marketing copy and blog outlines, each from a short prompt.',
        icon: FileText,
    },
    {
        title: 'Custom tones',
        description: 'Save a name, description and sample content, then reuse your voice in every request.',
        icon: WandSparkles,
    },
    {
        title: 'Content history',
        description: 'Browse, reopen and delete everything you have generated. Only you can see it.',
        icon: History,
    },
    {
        title: 'Transparent credit ledger',
        description: 'See every sign-up bonus, charge, refund and purchase with its own entry.',
        icon: CreditCard,
    },
];

const faqs = [
    {
        question: 'What is a credit?',
        answer: 'One credit pays for one successful generation. Your balance and every change to it are shown in your account.',
    },
    {
        question: 'What if a generation fails?',
        answer: 'The reserved credit is refunded once, and the refund appears in your credit history.',
    },
    {
        question: 'When do I get my purchased credits?',
        answer: 'After Stripe confirms your payment. Pending or failed payments add no credits.',
    },
    {
        question: 'Can others see my content?',
        answer: 'No. Your tones and generated content are visible only to your account.',
    },
];

function formatNumber(value: number): string {
    return new Intl.NumberFormat().format(value);
}

function formatPrice(amount: string, currency: string): string {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(Number(amount));
}
</script>

<template>
    <Head title="Content Corner — AI Content Generator">
        <meta name="description" content="Write social posts, marketing copy and blog outlines in your own voice. Start with 20 free credits." />
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="landing min-h-screen overflow-hidden text-black">
        <div class="mx-auto max-w-6xl px-5 sm:px-8">
            <header class="site-header flex items-center justify-between gap-4 py-5">
                <a href="#home" class="brand flex shrink-0 items-center gap-2.5" aria-label="Contentry home">
                    <span class="brand-icon">
                        <svg viewBox="0 0 100 100" fill="currentColor" aria-hidden="true">
                            <path d="M75 8H42A34 34 0 0 0 8 42v16a34 34 0 0 0 34 34h33V73H42a15 15 0 0 1-15-15V42a15 15 0 0 1 15-15h33V8Z" />
                            <path d="M72 31c3 13 10 21 24 24-14 3-21 11-24 24-3-13-10-21-24-24 14-3 21-11 24-24Z" />
                        </svg>
                    </span>
                    <span class="text-lg">Content Corner</span>
                </a>

                <nav aria-label="Main navigation" class="hidden items-center gap-7 text-xs font-medium text-black/70 md:flex">
                    <a href="#how-it-works" class="nav-link">How it works</a>
                    <a href="#features" class="nav-link">Features</a>
                    <a href="#pricing" class="nav-link">Pricing</a>
                    <a href="#faq" class="nav-link">FAQ</a>
                </nav>

                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <Link :href="login()" class="login-link">Log in</Link>
                    <Link :href="register()" class="button-primary header-cta">Get 20 free credits</Link>
                </div>
            </header>

            <main>
                <section id="home" class="hero scroll-mt-8 grid items-center gap-8 py-12 sm:py-16 md:grid-cols-2 md:gap-7 lg:gap-12 lg:py-[4.5rem]">
                    <div class="hero-copy relative z-[1]">
                        <div class="offer-badge">
                            <Sparkles class="h-3.5 w-3.5" />
                            20 free credits when you sign up
                        </div>

                        <h1 class="mt-5 max-w-xl text-[2.5rem] font-bold leading-[1.08] tracking-[-0.055em] sm:text-5xl md:text-4xl lg:text-[3.4rem]">
                            Write social posts, marketing copy and blog outlines
                            <span class="headline-accent">in your own voice.</span>
                        </h1>

                        <p class="mt-5 max-w-lg text-sm leading-6 text-black/70 sm:text-[15px] sm:leading-7">
                            Pick a content type, add your custom tone, and get a draft in seconds. One credit per successful generation. If a generation fails, your credit comes back.
                        </p>

                        <div class="mt-7 flex flex-wrap items-center gap-3">
                            <Link :href="register()" class="button-primary inline-flex items-center gap-2 rounded-lg px-5 py-3 text-sm font-semibold">
                                Start free
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                            <a href="#how-it-works" class="button-secondary inline-flex items-center gap-2 rounded-lg px-5 py-3 text-sm font-semibold">
                                See how it works
                            </a>
                        </div>

                        <p class="mt-4 flex items-center gap-2 text-xs text-black/55">
                            <Check class="h-3.5 w-3.5 text-black/70" />
                            No subscription. Buy credit packs only when you need more.
                        </p>
                    </div>

                    <div class="generator-wrap relative mx-auto w-full max-w-[530px]">
                        <div class="hero-glow"></div>
                        <div class="generator-card">
                            <div class="content-type-list grid grid-cols-3 gap-2">
                                <div v-for="(type, index) in contentTypes" :key="type.label" class="content-type" :class="{ 'content-type-active': index === 0 }">
                                    <component :is="type.icon" class="h-4 w-4" />
                                    <span>{{ type.label }}</span>
                                </div>
                                <span class="credit-pill"><Sparkles class="h-3 w-3" /> 20 credits</span>
                            </div>

                            <div class="generator-field">
                                <div class="field-label">Tone</div>
                                <div class="tone-select">
                                    <span>Friendly expert <span class="text-black/60">(custom)</span></span>
                                    <span class="select-chevron">⌄</span>
                                </div>
                            </div>

                            <div class="generator-field">
                                <div class="field-label flex items-center justify-between">
                                    <span>Prompt</span>
                                    <span class="text-black/50">0 / 1000</span>
                                </div>
                                <div class="prompt-box">
                                    Announce our new reusable coffee cup for a spring launch
                                </div>
                            </div>

                            <div class="output-box">
                                <div class="output-heading">
                                    <span class="flex items-center gap-2"><Sparkles class="h-3.5 w-3.5 text-black/60" /> Your draft</span>
                                    <span class="output-status"><span></span> Ready</span>
                                </div>
                                <p>Spring is better with a cup you can keep. Meet our new reusable coffee cup: light, leak-proof and ready for every commute. Tell us your favorite morning ritual below.</p>
                            </div>

                            <div class="generator-footer flex items-center justify-between gap-3">
                                <span class="flex items-center gap-1.5 text-[11px] text-black/55">
                                    <Clock3 class="h-3.5 w-3.5" />
                                    1 credit used · 19 left
                                </span>
                                <span class="button-primary generate-button inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-xs font-semibold">
                                    <WandSparkles class="h-3.5 w-3.5" />
                                    Generate
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="how-it-works" class="scroll-mt-8 border-t border-white/[0.08] py-14 sm:py-[4.5rem]">
                    <div class="max-w-2xl">
                        <p class="section-kicker">How it works</p>
                        <h2 class="section-title mt-3">From sign-up to first draft in three steps</h2>
                        <p class="mt-3 max-w-xl text-sm leading-6 text-black/55">
                            Credits are simple and visible. Every change is recorded in your credit history.
                        </p>
                    </div>

                    <div class="mt-8 grid gap-4 md:grid-cols-3">
                        <article v-for="(step, index) in steps" :key="step.title" class="step-card">
                            <div class="step-topline">
                                <span class="step-icon"><component :is="step.icon" class="h-4 w-4" /></span>
                                <span class="step-count">0{{ index + 1 }}</span>
                            </div>
                            <h3 class="mt-5 text-sm font-semibold">{{ step.title }}</h3>
                            <p class="mt-2 text-xs leading-5 text-black/55">{{ step.description }}</p>
                        </article>
                    </div>
                </section>

                <section id="features" class="scroll-mt-8 border-t border-white/[0.08] py-14 sm:py-[4.5rem]">
                    <div class="max-w-2xl">
                        <p class="section-kicker">Made for your workflow</p>
                        <h2 class="section-title mt-3">Built for repeatable, on-brand writing</h2>
                    </div>

                    <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <article v-for="feature in features" :key="feature.title" class="feature-card">
                            <span class="feature-icon">
                                <component :is="feature.icon" class="h-4 w-4" />
                            </span>
                            <div>
                                <h3 class="text-sm font-semibold">{{ feature.title }}</h3>
                                <p class="mt-1.5 text-xs leading-5 text-black/55">{{ feature.description }}</p>
                            </div>
                        </article>
                    </div>
                </section>

                <section id="pricing" class="scroll-mt-8 border-t border-white/[0.08] py-14 sm:py-[4.5rem]">
                    <div class="grid gap-8 lg:grid-cols-[0.85fr_1.15fr] lg:items-start">
                        <div>
                            <p class="section-kicker">Simple, transparent pricing</p>
                            <h2 class="section-title mt-3">Pay for what you generate</h2>
                            <p class="mt-3 text-sm leading-6 text-black/55">
                                Start with 20 free credits. Top up with a credit pack when you run low. No subscription.
                            </p>

                            <div class="free-plan mt-6">
                                <span class="free-plan-icon"><Sparkles class="h-4 w-4" /></span>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-semibold">Free start</h3>
                                    <p class="mt-1 text-xs text-black/55">Included once with every new account.</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold">20</div>
                                    <div class="text-[10px] text-black/55">credits</div>
                                </div>
                            </div>
                        </div>

                        <div class="package-area">
                            <div class="package-heading">
                                <span class="section-kicker">Credit packs</span>
                                <span class="stripe-note"><ShieldCheck class="h-3.5 w-3.5" /> Secure checkout</span>
                            </div>
                            <p class="mt-2 text-xs leading-5 text-black/55">
                                Paid through Stripe and added to your balance automatically after payment.
                            </p>

                            <div v-if="creditPackages.length" class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                <article v-for="creditPackage in creditPackages" :key="creditPackage.id" class="package-card">
                                    <div class="text-sm font-semibold">{{ creditPackage.name }}</div>
                                    <div class="mt-1 text-xs text-black/55">{{ formatNumber(creditPackage.credits_amount) }} credits</div>
                                    <div class="mt-4 flex items-end justify-between gap-2">
                                        <span class="text-lg font-bold">{{ formatPrice(creditPackage.price_amount, creditPackage.currency) }}</span>
                                        <Link :href="register()" class="package-link">Buy credits <ArrowRight class="h-3 w-3" /></Link>
                                    </div>
                                </article>
                            </div>
                            <p v-else class="package-empty mt-5">
                                Credit packs will appear here when they are available.
                            </p>
                        </div>
                    </div>

                    <div class="safety-notes mt-6 grid gap-3 sm:grid-cols-2">
                        <div class="safety-note"><Check class="h-4 w-4" /><div><strong>Refunded if it fails</strong><span>You are never charged for a generation that did not produce a result.</span></div></div>
                        <div class="safety-note"><ShieldCheck class="h-4 w-4" /><div><strong>Secure checkout</strong><span>Stripe handles payment securely. Failed or pending payments add no credits.</span></div></div>
                    </div>
                </section>

                <section id="faq" class="scroll-mt-8 border-t border-white/[0.08] py-14 sm:py-[4.5rem]">
                    <div class="mx-auto max-w-3xl">
                        <div class="text-center">
                            <p class="section-kicker">FAQ</p>
                            <h2 class="section-title mt-3">Questions, answered</h2>
                        </div>
                        <div class="mt-7 divide-y divide-white/[0.08]">
                            <details v-for="(faq, index) in faqs" :key="faq.question" class="faq-item" :open="index === 0">
                                <summary>
                                    <span>{{ faq.question }}</span>
                                    <CircleHelp class="faq-icon h-4 w-4" />
                                </summary>
                                <p>{{ faq.answer }}</p>
                            </details>
                        </div>
                    </div>
                </section>

                <!-- <section class="cta-panel mb-14 flex flex-col items-start justify-between gap-5 rounded-2xl p-3 sm:flex-row sm:items-center sm:p-9">
                    <div>
                        <p class="section-kicker">Your next draft is waiting</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Try it with 20 free credits</h2>
                        <p class="mt-2 text-sm text-slate-300">Create an account and generate your first draft today.</p>
                    </div>
                    <Link :href="register()" class="button-primary inline-flex shrink-0 items-center gap-2 rounded-lg px-5 py-3 text-sm font-semibold">
                        Start free <ArrowRight class="h-4 w-4" />
                    </Link>
                </section> -->
            </main>

            <footer class="site-footer flex flex-col gap-4 border-t border-black/10 py-6 text-xs text-black/55 sm:flex-row sm:items-center sm:justify-between">
                <a href="#home" class="brand flex items-center gap-2">
                    <span class="brand-icon footer-brand-icon">
                        <svg viewBox="0 0 100 100" fill="currentColor" aria-hidden="true">
                            <path d="M75 8H42A34 34 0 0 0 8 42v16a34 34 0 0 0 34 34h33V73H42a15 15 0 0 1-15-15V42a15 15 0 0 1 15-15h33V8Z" />
                            <path d="M72 31c3 13 10 21 24 24-14 3-21 11-24 24-3-13-10-21-24-24 14-3 21-11 24-24Z" />
                        </svg>
                    </span>
                    <span>© Contentry. All rights reserved.</span>
                </a>
                <nav aria-label="Footer navigation" class="flex flex-wrap gap-5">
                    <Link :href="login()" class="nav-link">Log in</Link>
                    <Link :href="register()" class="nav-link">Register</Link>
                    <a href="#faq" class="nav-link">FAQ</a>
                </nav>
            </footer>
        </div>
    </div>
</template>

<style scoped>
.landing {
    color: #000;
    background: #fff;
}

.site-header {
    border-bottom: 1px solid #e5e5e5;
}

.brand {
    color: #000;
    font-size: 13px;
    font-weight: 650;
    letter-spacing: -0.02em;
}

.brand-icon,
.mini-mark,
.step-icon,
.feature-icon,
.free-plan-icon {
    display: inline-flex;
    flex: none;
    align-items: center;
    justify-content: center;
    color: #fff;
    background: #000;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.16);
}

.brand-icon {
    width: 29px;
    height: 29px;
    border-radius: 9px;
}

.brand-icon svg {
    width: 21px;
    height: 21px;
}

.nav-link {
    color: #555;
    transition: color 150ms ease;
}

.nav-link:hover {
    color: #000;
}

.login-link {
    border-radius: 8px;
    padding: 6px 11px;
    width: 80px;
    text-align: center;
    color: #fff;
    font-size: 15px;
    font-weight: 550;
    transition: background-color 150ms ease, color 150ms ease;
    background-color: #000;

}

.login-link:hover {
    background: #222;
    transform: translateY(-1px);
}

.button-primary {
    color: #fff;
    background: #000;
    box-shadow: 0 5px 16px rgba(0, 0, 0, 0.16);
    transition: filter 150ms ease, transform 150ms ease;
}

.button-primary:hover {
    background: #222;
    transform: translateY(-1px);
}

.header-cta {
    color: #fff;
    background: #000;
    box-shadow: none;
    border-radius: 8px;
    padding: 9px 12px;
    font-size: 11px;
    font-weight: 650;
}

.offer-badge,
.credit-pill,
.stripe-note {
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.offer-badge {
    border: 1px solid #d6d6d6;
    border-radius: 999px;
    padding: 7px 11px;
    color: #111;
    background: #f5f5f5;
    font-size: 11px;
    font-weight: 550;
}

.offer-badge :deep(svg) {
    color: #111;
}

.headline-accent {
    color: #000;
    background: none;
    background-clip: text;
    -webkit-text-fill-color: currentColor;
}

.button-secondary {
    border: 1px solid #d6d6d6;
    color: #111;
    background: #fff;
    transition: border-color 150ms ease, background-color 150ms ease;
}

.button-secondary:hover {
    border-color: #888;
    background: #f5f5f5;
}

.generator-wrap {
    isolation: isolate;
}

.hero-glow {
    position: absolute;
    z-index: -1;
    inset: 12% 4%;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.08);
    filter: blur(72px);
}

.generator-card {
    position: relative;
    overflow: hidden;
    border: 1px solid #e5e5e5;
    border-radius: 13px;
    padding: 15px;
    background: #fff;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.07);
}

.content-type-list {
    grid-template-columns: repeat(3, minmax(0, 1fr)) auto;
    align-items: center;
}

.mini-mark {
    width: 25px;
    height: 25px;
    border-radius: 8px;
}

.credit-pill {
    border: 1px solid #dedede;
    border-radius: 999px;
    padding: 6px 9px;
    color: #111;
    background: #f5f5f5;
    font-size: 10px;
    font-weight: 600;
}

.content-type {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 7px;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    padding: 8px;
    color: #444;
    background: #fff;
    font-size: 10px;
    font-weight: 550;
}

.content-type :deep(svg) {
    flex: none;
    color: #111;
}

.content-type-active {
    border-color: #888;
    color: #000;
    background: #f2f2f2;
    box-shadow: none;
}

.generator-field {
    margin-top: 15px;
}

.field-label {
    margin-bottom: 7px;
    color: #333;
    font-size: 10px;
    font-weight: 600;
}

.tone-select,
.prompt-box {
    border: 1px solid #dedede;
    border-radius: 8px;
    color: #111;
    background: #fff;
    font-size: 11px;
}

.tone-select {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 11px;
}

.select-chevron {
    color: #666;
    font-size: 15px;
    line-height: 10px;
}

.prompt-box {
    min-height: 57px;
    padding: 11px;
    color: #222;
    line-height: 1.65;
}

.output-box {
    margin-top: 14px;
    border: 1px solid #e5e5e5;
    border-radius: 10px;
    padding: 12px;
    background: #f7f7f7;
}

.output-heading,
.package-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.output-heading {
    color: #111;
    font-size: 10px;
    font-weight: 600;
}

.output-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #333;
    font-size: 9px;
}

.output-status span {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #111;
    box-shadow: none;
}

.output-box p {
    margin-top: 9px;
    color: #444;
    font-size: 11px;
    line-height: 1.75;
}

.generator-footer {
    margin-top: 13px;
}

.generate-button {
    box-shadow: 0 5px 16px rgba(0, 0, 0, 0.16);
}

.floating-note {
    position: absolute;
    right: -12px;
    bottom: -16px;
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid rgba(0, 0, 0, 0.14);
    border-radius: 10px;
    padding: 9px 11px;
    color: #111;
    background: #fff;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.28);
    font-size: 10px;
}

.section-kicker {
    color: #333;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
}

.section-title {
    max-width: 700px;
    color: #111;
    font-size: clamp(1.55rem, 3.2vw, 2.2rem);
    font-weight: 700;
    letter-spacing: -0.045em;
    line-height: 1.16;
}

.step-card,
.feature-card,
.package-card {
    border: 1px solid #e5e5e5;
    border-radius: 11px;
    background: #fff;
}

.step-card {
    padding: 17px;
    transition: border-color 150ms ease, transform 150ms ease;
}

.step-card:hover,
.feature-card:hover,
.package-card:hover {
    border-color: #888;
}

.step-card:hover {
    transform: translateY(-2px);
}

.step-topline {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.step-icon,
.feature-icon {
    width: 31px;
    height: 31px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 9px;
    
}

.step-count {
    color: #333;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.1em;
}

.feature-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    transition: border-color 150ms ease;
}

.feature-icon {
    color: #fff;
    background: #17181d;
    box-shadow: none;
}

.free-plan {
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #e5e5e5;
    border-radius: 12px;
    padding: 14px;
    background: #fff;
}

.free-plan-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
}

.package-area {
    border: 1px solid #e5e5e5;
    border-radius: 13px;
    padding: 16px;
    background: #fff;
}

.stripe-note {
    color: #666770;
    font-size: 10px;
}

.package-card {
    padding: 14px;
    transition: border-color 150ms ease;
}

.package-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #111;
    font-size: 10px;
    font-weight: 650;
    white-space: nowrap;
}

.package-link:hover {
    color: #000;
}

.package-empty {
    border: 1px dashed #ccc;
    border-radius: 10px;
    padding: 16px;
    color: #555;
    font-size: 12px;
}

.safety-note {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    border: 1px solid #e5e5e5;
    border-radius: 10px;
    padding: 12px;
    color: #444;
    background: #fff;
}

.safety-note div {
    display: grid;
    gap: 4px;
}

.safety-note strong {
    color: #111;
    font-size: 11px;
}

.safety-note span {
    color: #555;
    font-size: 10px;
    line-height: 1.5;
}

.faq-item {
    padding: 16px 2px;
}

.faq-item summary {
    display: flex;
    cursor: pointer;
    list-style: none;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    color: #111;
    font-size: 13px;
    font-weight: 600;
}

.faq-item summary::-webkit-details-marker {
    display: none;
}

.faq-icon {
    flex: none;
    color: #444;
    transition: transform 150ms ease;
}

.faq-item[open] .faq-icon {
    transform: rotate(20deg);
}

.faq-item p {
    max-width: 640px;
    padding: 10px 24px 0 0;
    color: #555;
    font-size: 12px;
    line-height: 1.7;
}

.cta-panel {
    border: 1px solid #000;
    background: #000;
}

.cta-panel .section-kicker { color: #fff; }
.cta-panel h2 { color: #fff; }
.cta-panel p:not(.section-kicker) { color: #ccc; }

.site-footer {
    border-color: #e5e5e5;
}

.footer-brand-icon {
    width: 24px;
    height: 24px;
    border-radius: 7px;
}

.footer-brand-icon svg {
    width: 18px;
    height: 18px;
}

@media (max-width: 639px) {
    .header-cta {
        padding: 9px 10px;
        font-size: 10px;
    }

    .login-link {
        padding: 8px 7px;
        font-size: 11px;
    }

    .generator-card {
        padding: 13px;
    }

    .content-type {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
        font-size: 9px;
    }

    .content-type-list {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .credit-pill {
        grid-column: 1 / -1;
        justify-self: start;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        scroll-behavior: auto !important;
        transition-duration: 0.01ms !important;
    }
}

.landing {
    color: #000;
    background: #fff;
}

.landing :deep(.text-white) { color: #000; }
.landing :deep(.text-slate-200),
.landing :deep(.text-slate-300) { color: #444; }
.landing :deep(.text-slate-400),
.landing :deep(.text-slate-500) { color: #666; }
.landing :deep(.text-violet-300),
.landing :deep(.text-emerald-300) { color: #333; }
.landing :deep(.button-primary) { color: #fff; }
.landing :deep(.header-cta) { color: #fff; background: #000; }
.landing :deep(.cta-panel .text-slate-300) { color: #ccc; }

.brand,
.nav-link:hover,
.login-link:hover,
.offer-badge,
.offer-badge :deep(svg),
.headline-accent,
.button-secondary,
.credit-pill,
.content-type :deep(svg),
.content-type-active,
.section-kicker,
.step-count,
.feature-icon,
.package-link,
.stripe-note,
.faq-icon {
    color: #111;
}

.brand-icon,
.mini-mark,
.step-icon,
.feature-icon,
.free-plan-icon {
    color: #fff;
    background: #000;
    box-shadow: none;
}

.site-header,
.site-footer {
    border-color: #e5e5e5;
}

.button-primary,
.header-cta {
    color: #fff;
    background: #000;
    box-shadow: 0 5px 16px rgba(0, 0, 0, 0.14);
}

.login-link:hover,
.button-primary:hover,
.header-cta:hover {
    color: #fff;
    background: #222;
    filter: none;
}

.offer-badge,
.credit-pill,
.content-type-active{
    border-color: #d6d6d6;
    background: white;
}

.button-secondary {
    border-color: #d6d6d6;
    background: #fff;
}

.button-secondary:hover {
    border-color: #888;
    background: #f5f5f5;
}

.hero-glow {
    background: rgba(0, 0, 0, 0.08);
}

.generator-card,
.step-card,
.feature-card,
.package-card,
.package-area,
.safety-note,
.free-plan {
    border-color: #e5e5e5;
    background: #fff;
}

.generator-card {
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.07);
}

.content-type,
.tone-select,
.prompt-box,
.output-box {
    border-color: #e5e5e5;
    color: #222;
    background: #fff;
}

.output-box {
    background: #f7f7f7;
}

.content-type-active,
.step-card:hover,
.feature-card:hover,
.package-card:hover {
    border-color: #888;
}

.output-status,
.output-status span,
.safety-note {
    color: #222;
}

.output-status span {
    background: #111;
    box-shadow: none;
}

.step-icon,
.feature-icon {
    border-color: #ddd;
}

.step-card:hover {
    border-color: #888;
}

.package-link:hover {
    color: #000;
}

.package-empty {
    border-color: #ccc;
    color: #555;
}

.faq-item summary {
    color: #111;
}

.cta-panel {
    border-color: #000;
    background: #000;
}

.cta-panel .section-kicker,
.cta-panel h2 {
    color: #fff;
}

.cta-panel p:not(.section-kicker) {
    color: #ccc;
}
</style>
