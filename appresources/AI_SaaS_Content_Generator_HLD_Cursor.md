# AI SaaS Content Generator — HLD

> Purpose: concise, implementation-oriented context for Cursor AI Agent. Follow this document as the source of truth. Avoid adding unrequested features.

## 1. Product
A public multi-user SaaS web app that generates text with Gemini Flash. New users receive 20 free credits. Each successful content generation costs 1 credit. Users can create custom tones. Credit purchases are manual: users submit payment details/proof; an admin reviews and adds credits.

## 2. Stack
- Backend: Laravel
- Frontend: Vue 3 + Inertia.js
- Styling: Tailwind CSS
- Database: MySQL
- AI: Gemini Flash API, called only from Laravel backend
- Architecture: modular monolith; do not create separate frontend/API services for the MVP.
- Deployment target: public online SaaS.

## 3. Roles
- Guest: landing page, register, login.
- User: own dashboard, generate content, manage own tones/history, view credits/transactions, submit payment requests.
- Admin: view users, review payments, adjust credits, view usage and audit logs.
Enforce permissions on the backend. Hiding UI elements is not authorization.

## 4. MVP Pages / Features
Public:
- Landing page
- Login
- Register

User app:
- Dashboard: balance, recent generations, recent transactions
- Content Generator: social media posts, marketing copy, blog outlines
- Custom Tones: name, description, sample content; CRUD
- Content History: list/detail/delete own generated content
- Billing: credit packages, payment instructions, submit payment request, status
- Credit transaction history

Admin:
- Overview
- User management / suspend account
- Payment request review: approve/reject
- Manual credit top-up with reason
- Credit transactions, usage overview, audit logs

Out of scope for MVP: automatic payment gateway, subscriptions, team workspaces, social media publishing, multiple AI providers.

## 5. Credit Rules (critical)
- Successful registration grants exactly 20 credits, once.
- One successful generation costs exactly 1 credit.
- No balance => reject generation before calling Gemini.
- Confirmed generation failure => refund the reserved credit exactly once.
- Pending payment grants no credits. Admin approval grants package credits exactly once.
- Every credit change must update `users.credits_balance` and create a `credit_transactions` ledger row in the same DB transaction.
- Prevent negative balances and concurrent double-spend with DB transactions and row locking (`lockForUpdate`).
- Use unique idempotency keys for signup bonus, generation charge/refund, payment approval, and admin adjustments.
- Do not keep a DB transaction open during a slow Gemini HTTP request. Reserve credit in a short transaction, call Gemini, then finalize or refund in another short transaction.
- Handle timeout/unknown AI outcomes safely; do not blindly retry or refund in a way that can double-charge or double-refund.

## 6. Database Tables
Use migrations, foreign keys, timestamps, indexes, and appropriate unique constraints.

### `users`
`id`, `name`, `email` (unique), `password` (hashed), `role` (`user`/`admin`), `status` (`active`/`suspended`), `credits_balance` (integer, default 0), `email_verified_at` nullable, timestamps.

### `custom_tones`
`id`, `user_id` FK, `name`, `description`, `sample_content`, timestamps.
Index `user_id`.

### `generated_contents`
`id`, `user_id` FK, nullable `custom_tone_id` FK, `request_uuid` (unique), `content_type`, `input_prompt`, nullable `generated_text`, `model_name`, `credits_used`, `status` (`pending`/`succeeded`/`failed`), nullable `error_code`, timestamps.
Indexes `(user_id, created_at)` and `(user_id, content_type)`.

### `credit_transactions`
`id`, `user_id` FK, signed `amount` (+/-), `type` (`signup_bonus`/`generation`/`purchase`/`refund`/`admin_adjustment`), nullable `reference_type`, nullable `reference_id`, `idempotency_key` (unique), nullable `description`, nullable `created_by_admin_id` FK to users, `created_at`.
This is the credit ledger; never silently edit/delete historical ledger entries.

### `credit_packages`
`id`, `name`, `credits_amount`, `price_amount` (decimal), `currency`, `is_active`, timestamps.

### `payment_requests`
`id`, `user_id` FK, `credit_package_id` FK, snapshot `credits_amount`, `amount_paid`, `currency`, `payment_method`, `payment_reference`, nullable private `proof_path`, `status` (`pending`/`approved`/`rejected`), nullable `reviewed_by` FK to users, nullable `reviewed_at`, nullable `rejection_reason`, timestamps.
Index `(status, created_at)`. Approval must be atomic and idempotent.

### `admin_audit_logs`
`id`, `admin_id` FK to users, `action`, `target_type`, `target_id`, nullable JSON `metadata`, nullable `ip_address`, `created_at`.
Never log passwords, API keys, or sensitive payment proof contents.

## 7. Main Flows
### Register
Validate -> create user and grant +20 ledger transaction atomically -> dashboard. Signup bonus must never be duplicated.

### Generate content
Validate input and tone ownership -> create unique request -> reserve 1 credit atomically -> call Gemini via backend service -> on success save output and finalize charge -> on confirmed failure refund once -> return updated balance and result/error.

### Manual payment
User selects active package -> server stores package/price/credits snapshot -> user submits reference and optional proof -> status pending -> admin verifies out-of-band -> approve/reject. Approval atomically changes status, adds package credits, creates ledger row, and audit log. Only pending requests may be reviewed.

## 8. Routes (Laravel web + Inertia)
Public:
- `GET /`
- Auth routes for register/login/logout

Authenticated:
- `GET /dashboard`
- `GET /generator`, `POST /generator`
- `GET /contents`, `GET /contents/{content}`, `DELETE /contents/{content}`
- `resource /tones` (CRUD)
- `GET /billing`, `POST /billing/payment-requests`
- `GET /credits/transactions`

Admin-only:
- `GET /admin`
- `GET /admin/users`
- `PATCH /admin/users/{user}/status`
- `POST /admin/users/{user}/credits`
- `GET /admin/payments`
- `POST /admin/payments/{payment}/approve`
- `POST /admin/payments/{payment}/reject`
- `GET /admin/transactions`
- `GET /admin/audit-logs`

Use Form Requests for validation, policies for ownership, and middleware for auth/admin. Inertia means a separate REST API is not required for the MVP.

## 9. Gemini Integration
- Keep `GEMINI_API_KEY` and model ID in server environment variables only.
- Use a dedicated `GeminiService`; do not call Gemini directly from Vue or controllers.
- Validate content type, prompt length, tone ownership, output size, timeout, and rate limits.
- Treat user prompts and tone samples as untrusted input; do not allow them to override application instructions.
- Store model ID and output; return safe errors without leaking secrets.
- Verify current Gemini Flash model ID, free-tier quota, commercial-use terms, and rate limits before launch. Never assume free usage is unlimited.

## 10. Security / Quality
- Hash passwords; use HTTPS in production and secure session/cookie settings.
- Keep `.env` out of Git; never expose API keys in Vue props or browser code.
- Enforce per-user ownership on every read/update/delete.
- Protect admin routes server-side; public registration must never set `role=admin`.
- Rate-limit authentication and generation.
- Validate uploads; payment proof files must use private storage and authorized downloads.
- Use safe rendering, CSRF protection, and server-side validation.
- Add tests for signup bonus, credit concurrency, generation success/failure/refund, duplicate requests, payment approval once, and user/admin authorization.
- Configure production backups, logging, and error monitoring.

## 11. Build Order
1. Laravel/Vue/Inertia/Tailwind setup and MySQL.
2. Auth, roles, migrations, signup bonus, credit ledger.
3. Dashboard and custom tone CRUD.
4. Gemini service, generation, history, safe refund flow.
5. Manual billing and admin payment review.
6. Admin users, adjustments, usage and audit logs.
7. Automated tests, security checks, deployment.

## 12. Assumptions / TODO
- Currency, package prices, payment channels, and proof requirements are not decided; make them configurable, not hard-coded across the app.
- Email verification and password reset are recommended for public launch; finalize before production.
- Admin account must be created through a secure seeder/command, not public registration.
- Use the currently supported Gemini Flash model and confirm current quotas/terms.
- Do not implement automatic gateway payments in the MVP.
- If a requirement is ambiguous, ask before adding scope. Prefer the simplest maintainable implementation.
