# AI SaaS Content Generator — Implementation Phases

> Source of truth for product rules: `AI_SaaS_Content_Generator_HLD_Cursor.md`.
> This file is the build sequence. Finish a phase (exit criteria) before starting the next. Do not add out-of-scope MVP features.

## Current baseline (already present)

The repo is a Laravel Vue starter kit (`laravel/vue-starter-kit`): Laravel, Vue 3, Inertia, Tailwind, Fortify (login/register/password reset/email verification), dashboard page, settings, MySQL-ready migrations for users/jobs/cache/passkeys.

Product domain is **not** started: no roles/credits, tones, generator, billing, or admin.

---

## Phase 0 — Close the scaffold

**Goal:** App runs locally against MySQL and is ready for domain work.

**Do**
- Confirm `.env` MySQL connection, `php artisan migrate`, Vite/Inertia pages load.
- Keep Fortify auth; do not replace it.
- Add env placeholders only: `GEMINI_API_KEY`, `GEMINI_MODEL`.
- Decide (config, not hard-coded later): default currency, whether email verification stays required for dashboard.

**Do not**
- Build product pages, Gemini calls, or billing.

**Exit criteria**
- Local app boots: landing, register, login, dashboard, logout.
- Migrations run cleanly on MySQL.

---

## Phase 1 — Identity, roles, credit ledger

**Goal:** Every user has a role, status, and a correct credit balance backed by a ledger.

**Do**
- Extend `users`: `role` (`user`/`admin`), `status` (`active`/`suspended`), `credits_balance`.
- Tables: `credit_transactions` (ledger).
- Register atomically: user + exactly +20 `signup_bonus` once (idempotency key).
- Public registration never sets `role=admin`.
- Secure artisan command/seeder to create the first admin.
- Policies/middleware: auth, active user, admin.
- Suspended users cannot use the app.
- Tests: signup bonus once, public cannot become admin, ledger + balance update in one DB transaction.

**Do not**
- Generator, tones, payments.

**Exit criteria**
- New user lands on dashboard with 20 credits and one ledger row.
- Re-register / duplicate bonus is impossible.
- Admin exists only via seeder/command.

---

## Phase 2 — Dashboard and custom tones

**Goal:** Users can manage tones and see a real (still simple) dashboard.

**Do**
- Dashboard: credit balance, empty/recent placeholders for generations and transactions.
- `custom_tones` CRUD (own records only): name, description, sample content.
- Routes/pages: `/dashboard`, `/tones` resource.
- Ownership policies on every tone action.

**Do not**
- Call Gemini. Do not show fake generated content.

**Exit criteria**
- Authenticated user can create/edit/delete own tones.
- Cannot access another user’s tones.
- Dashboard shows live `credits_balance`.

---

## Phase 3 — Generate content (core product)

**Goal:** Safe Gemini generation with reserve / finalize / refund credits.

**Do**
- `generated_contents` table and history pages (list/detail/delete own).
- `GeminiService` only; key never sent to Vue.
- Generator page: content types = social posts, marketing copy, blog outlines; optional own tone.
- Flow: validate → unique `request_uuid` → reserve 1 credit (`lockForUpdate`) → call Gemini **outside** the DB transaction → success finalize / confirmed failure refund once.
- Reject generation if balance is 0, before Gemini.
- Rate-limit generation. Treat prompts/tone samples as untrusted.
- Tests: success charge, failure refund once, zero-balance no API call, duplicate request, concurrent double-spend prevented.

**Do not**
- Billing, admin payment review, extra AI providers.

**Exit criteria**
- Successful generation costs exactly 1 credit; balance and ledger match.
- Confirmed Gemini failure refunds exactly once.
- History is per-user only.

**Implementation notes**
- Configure `GEMINI_API_KEY` in the server environment; `GEMINI_MODEL` and `GEMINI_TIMEOUT` are optional.
- Generation supports social posts, marketing copy, and blog outlines, with optional owned brand profiles and custom tones.
- Requests are rate-limited and idempotent per user/request UUID. Failed provider requests are recorded and refunded once.

---

## Phase 4 — Stripe billing

**Goal:** Users purchase credits through Stripe automatically. Verified successful payments grant credits once; failed payments grant none.

**Do**
- Add admin-managed `credit_packages` (name, credits, price, currency, active flag); deactivate rather than delete packages.
- Add user billing UI for active packages, balance, purchase history, and credit transaction history.
- Store a purchase snapshot of the package name, credits, amount, and currency before checkout.
- Use Stripe Checkout and verified webhooks; only verified success may grant credits, and payment failure/expiration grants none.
- Grant purchased credits, update purchase status, and create the ledger row atomically with an idempotency key.
- No admin review or manual payment approval.

**Do not**
- Subscriptions or manual payment proof/review.

**Exit criteria**
- Admin can maintain packages; users see only active packages and only their own billing history.
- Stripe Checkout and verified webhook processing grant credits exactly once on success; failed/expired payments grant none.

---

## Phase 5 — Admin operations and audit

**Goal:** Admins can operate the SaaS without touching the database.

**Do**
- Admin overview, user list, suspend/activate.
- Manual credit top-up/adjustment with reason (ledger + audit).
- Admin views: credit transactions, usage overview, audit logs.
- `admin_audit_logs` for sensitive actions. Never log passwords, API keys, or proof file contents.

**Do not**
- Team workspaces or extra roles beyond `user`/`admin`.

**Exit criteria**
- Admin can suspend a user, adjust credits with a reason, and see an audit trail.
- Non-admin cannot hit admin routes (server-side).

---

## Phase 6 — Hardening and launch readiness

**Goal:** Public MVP is safe to deploy.

**Do**
- Fill remaining tests from HLD §10.
- Rate-limit auth. HTTPS/session/cookie settings. `.env` not in Git.
- Confirm current Gemini Flash model ID, quota, and commercial terms.
- Production: backups, logging, error monitoring.
- Email verification / password reset: keep if already in Fortify; confirm before production.
- Landing page copy for the real product (still no extra features).

**Do not**
- Auto payments, multi-provider AI, social publishing.

**Exit criteria**
- Critical credit/auth tests pass.
- Secrets are server-only. App can be deployed as a public SaaS.

---

## Rules for every phase

- Backend enforces permissions; hidden UI is not security.
- Credit changes always update `users.credits_balance` and insert a `credit_transactions` row in the same DB transaction.
- Never hold a DB transaction open during the Gemini HTTP call.
- If a requirement is ambiguous, ask before adding scope.

## Explicitly out of MVP (all phases)

Automatic payment gateway, subscriptions, team workspaces, social media publishing, multiple AI providers.
