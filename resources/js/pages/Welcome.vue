<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BarChart3,
    Check,
    Play,
    ShieldCheck,
    Sparkles,
    TrendingUp,
    Wand2,
} from '@lucide/vue';
import { login, register } from '@/routes';

const stats = [
    { value: '10k+', label: 'content pieces' },
    { value: '4.9/5', label: 'satisfaction' },
    { value: '2.5x', label: 'faster launch' },
];

const features = [
    {
        icon: Wand2,
        title: 'AI Content Studio',
        description: 'Generate product copy, campaign ideas, and social captions with one command.',
    },
    {
        icon: TrendingUp,
        title: 'Built for Growth',
        description: 'Turn briefs into on-brand, conversion-focused content for every funnel stage.',
    },
    {
        icon: BarChart3,
        title: 'Smart Insights',
        description: 'Track usage, output quality, and project momentum from a single dashboard.',
    },
];

const workflow = ['Describe your goal', 'Choose a tone', 'Generate and refine'];
</script>

<template>
    <Head title="AI Content Generator">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-[#050505] text-white">
        <div class="mx-auto max-w-7xl px-6 py-6 lg:px-8">
            <header class="sticky top-3 z-50 mb-12 flex items-center justify-between gap-4 rounded-full border border-white/10 bg-[#111]/90 px-4 py-3 shadow-xl backdrop-blur-md">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl border border-white/15 bg-white/[0.04] text-sm font-semibold text-white/90">
                        AI
                    </div>
                    <div class="text-xs font-medium uppercase tracking-[0.18em] text-white/75">
                        ContentOS
                    </div>
                </div>

                <nav class="hidden items-center gap-8 text-sm text-white/60 md:flex">
                    <a href="#platform" class="transition hover:text-white">Platform</a>
                    <a href="#pricing" class="transition hover:text-white">Pricing</a>
                    <a href="#features" class="transition hover:text-white">Features</a>
                    <a href="#resources" class="transition hover:text-white">Resources</a>
                </nav>

                <div class="flex items-center gap-3">
                    <Link :href="login()" class="rounded-full border border-white/10 px-4 py-2 text-sm text-white/70 transition hover:border-white/20 hover:text-white">Login</Link>
                    <Link :href="register()" class="rounded-full bg-white px-4 py-2 text-sm font-medium text-black transition hover:bg-white/85">Get started</Link>
                </div>
            </header>

            <main class="pb-12">
                <section id="platform" class="scroll-mt-8 grid items-center gap-10 lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="max-w-xl">
                        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.02] px-3 py-1.5 text-[10px] uppercase tracking-[0.18em] text-white/60">
                            <Sparkles class="h-3.5 w-3.5" />
                            AI-powered workflow
                        </div>

                        <h1 class="text-5xl font-semibold tracking-[-0.06em] text-white md:text-6xl">
                            Turn ideas into polished content in minutes.
                        </h1>

                        <p class="mt-6 max-w-lg text-lg leading-8 text-white/65">
                            Launch blogs, campaigns, product messaging, and social posts with a cleaner, faster creative workflow built for modern teams.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-4">
                            <Link :href="register()" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-medium text-black transition hover:bg-white/85">
                                Start free
                                <ArrowRight class="h-4 w-4" />
                            </Link>

                            <button class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.02] px-5 py-3 text-sm text-white/80 transition hover:border-white/20 hover:text-white">
                                <Play class="h-4 w-4" />
                                Watch demo
                            </button>
                        </div>

                        <div class="mt-10 flex flex-wrap items-center gap-6">
                            <div v-for="metric in stats" :key="metric.label" class="min-w-[120px]">
                                <div class="text-2xl font-semibold tracking-tight text-white">{{ metric.value }}</div>
                                <div class="mt-1 text-[10px] uppercase tracking-[0.14em] text-white/45">{{ metric.label }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="panel p-4 md:p-5">
                            <div class="mb-4 flex items-center justify-between">
                                <div class="flex items-center gap-2 text-[10px] uppercase tracking-[0.14em] text-white/55">
                                    <BarChart3 class="h-3.5 w-3.5" />
                                    Dashboard
                                </div>
                                <span class="rounded-full border border-white/10 bg-white/[0.02] px-2 py-1 text-[9px] uppercase tracking-[0.14em] text-white/60">
                                    Live
                                </span>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                    <div class="text-[10px] uppercase tracking-[0.14em] text-white/45">Credits</div>
                                    <div class="mt-3 text-3xl font-semibold text-white">18</div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                    <div class="text-[10px] uppercase tracking-[0.14em] text-white/45">Posts</div>
                                    <div class="mt-3 text-3xl font-semibold text-white">42</div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                    <div class="text-[10px] uppercase tracking-[0.14em] text-white/45">ROI</div>
                                    <div class="mt-3 text-3xl font-semibold text-white">3.2x</div>
                                </div>
                            </div>

                            <div class="mt-5 rounded-2xl border border-white/10 bg-[#0c0c0c] p-4">
                                <div class="mb-3 flex items-center justify-between text-[10px] uppercase tracking-[0.14em] text-white/45">
                                    <span>Prompt</span>
                                    <span class="text-white/60">Ready</span>
                                </div>
                                <textarea rows="5" class="w-full resize-none bg-transparent text-sm leading-6 text-white/80 placeholder:text-white/25 focus:outline-none">Create a clean launch announcement for a premium AI SaaS product.</textarea>
                            </div>

                            <div class="mt-5 grid gap-3 md:grid-cols-[1fr_auto]">
                                <div class="flex gap-2 rounded-full border border-white/10 bg-white/[0.02] p-1">
                                    <button class="rounded-full bg-white px-3 py-2 text-[10px] font-medium text-black">Social</button>
                                    <button class="rounded-full px-3 py-2 text-[10px] text-white/60">Ads</button>
                                    <button class="rounded-full px-3 py-2 text-[10px] text-white/60">Blog</button>
                                </div>

                                <button class="rounded-full bg-white px-4 py-2 text-sm font-medium text-black transition hover:bg-white/85">Generate</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="features" class="scroll-mt-8 mt-28">
                    <div class="mb-8 text-center">
                        <div class="text-[10px] uppercase tracking-[0.18em] text-white/45">Why teams choose us</div>
                        <h2 class="mt-3 text-3xl font-semibold tracking-[-0.05em] text-white md:text-4xl">
                            One workspace for content creation and delivery.
                        </h2>
                    </div>

                    <div class="grid gap-5 md:grid-cols-3">
                        <article v-for="feature in features" :key="feature.title" class="panel p-6">
                            <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl border border-white/10 bg-white/[0.03]">
                                <component :is="feature.icon" class="h-5 w-5 text-white/85" />
                            </div>
                            <h3 class="text-xl font-semibold text-white">{{ feature.title }}</h3>
                            <p class="mt-3 text-sm leading-7 text-white/60">{{ feature.description }}</p>
                        </article>
                    </div>
                </section>

                <section class="mt-28 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                    <div id="resources" class="panel scroll-mt-8 p-7">
                        <div class="text-[10px] uppercase tracking-[0.18em] text-white/45">Workflow</div>
                        <h3 class="mt-4 text-3xl font-semibold tracking-[-0.05em] text-white">Simple process. Better output.</h3>

                        <div class="mt-8 space-y-4">
                            <div v-for="(step, index) in workflow" :key="step" class="flex items-start gap-4">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/[0.03] text-sm font-medium text-white">{{ index + 1 }}</div>
                                <div class="pt-1 text-base text-white/75">{{ step }}</div>
                            </div>
                        </div>
                    </div>

                    <div id="pricing" class="panel scroll-mt-8 p-7">
                        <div class="flex items-center justify-between">
                            <div class="text-[10px] uppercase tracking-[0.18em] text-white/45">Pricing</div>
                            <ShieldCheck class="h-4 w-4 text-white/70" />
                        </div>

                        <div class="mt-5">
                            <h3 class="text-2xl font-semibold tracking-[-0.04em] text-white">Billing details are coming soon.</h3>
                            <p class="mt-2 text-sm leading-6 text-white/60">We’re preparing clear plan and credit information. You can create an account to explore the platform in the meantime.</p>
                            <Link :href="register()" class="mt-5 inline-flex items-center gap-2 rounded-full bg-white px-4 py-2.5 text-sm font-medium text-black transition hover:bg-white/85">
                                Get started
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                        </div>

                        <div class="mt-6 space-y-4">
                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <div>
                                    <div class="text-sm font-medium text-white">Brand tone library</div>
                                    <div class="mt-1 text-xs text-white/45">Save reusable writing styles</div>
                                </div>
                                <Check class="h-4 w-4 text-white" />
                            </div>

                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <div>
                                    <div class="text-sm font-medium text-white">Credit-based usage</div>
                                    <div class="mt-1 text-xs text-white/45">Monitor every generation accurately</div>
                                </div>
                                <Check class="h-4 w-4 text-white" />
                            </div>

                            <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <div>
                                    <div class="text-sm font-medium text-white">Admin controls</div>
                                    <div class="mt-1 text-xs text-white/45">Manage users, billing, and oversight</div>
                                </div>
                                <Check class="h-4 w-4 text-white" />
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>

<style scoped>
.panel {
    position: relative;
    overflow: hidden;
    border-radius: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background:
        radial-gradient(circle at top left, rgba(255, 255, 255, 0.06), transparent 30%),
        linear-gradient(180deg, rgba(18, 18, 18, 0.96), rgba(8, 8, 8, 0.98));
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04), 0 25px 50px rgba(0, 0, 0, 0.28);
}

.panel::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.02), transparent 40%);
    pointer-events: none;
}
</style>
