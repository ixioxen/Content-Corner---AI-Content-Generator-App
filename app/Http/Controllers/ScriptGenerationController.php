<?php

namespace App\Http\Controllers;

use App\Enums\CreditTransactionType;
use App\Exceptions\GeminiGenerationException;
use App\Exceptions\InsufficientCreditsException;
use App\Http\Requests\Script\StoreScriptRequest;
use App\Models\ScriptGenerate;
use App\Models\User;
use App\Services\CreditService;
use App\Services\GeminiService;
use App\Services\ScriptPromptBuilder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ScriptGenerationController extends Controller
{
    /**
     * Show the Script Generator form page.
     *
     * We load the data the form needs (brand profiles, tones, credit balance)
     * and pass them to the Vue page via Inertia.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Scripts/Create', [

            // Current credit balance so the UI can show it
            'creditsBalance' => $request->user()->credits_balance,

            // Check if the Gemini API key is set in .env
            // If it is not, we will show a warning on the page
            'providerConfigured' => is_string(config('services.gemini.api_key'))
                && config('services.gemini.api_key') !== '',

            // Load only this user's brand profiles (id, name, etc.)
            'brandProfiles' => $request->user()->brandProfiles()
                ->latest()
                ->get(['id', 'brand_name', 'industry', 'language']),

            // Load only this user's custom tones
            'tones' => $request->user()->customTones()
                ->latest()
                ->get(['id', 'name', 'description']),
        ]);
    }

    /**
     * Handle the form submission — generate a script.
     *
     * Step-by-step what happens here:
     * 1. StoreScriptRequest validates all the form fields automatically.
     * 2. We check that the Gemini API key is configured.
     * 3. Inside a DB transaction, we:
     *    a. Lock the user row (prevents double-spending credits).
     *    b. Check for duplicate UUID (idempotency).
     *    c. Check the user has at least 1 credit.
     *    d. Create the script record with status = 'pending'.
     *    e. Deduct 1 credit immediately (refunded if AI fails).
     * 4. Build the prompt and call GeminiService.
     * 5. If AI fails → refund the credit, mark script as 'failed'.
     * 6. If AI succeeds → save the output, mark script as 'completed'.
     * 7. Redirect to the Show page.
     */
    public function store(
        StoreScriptRequest $request,
        GeminiService $gemini,
        CreditService $credits,
        ScriptPromptBuilder $promptBuilder,
    ): RedirectResponse {

        // $validated contains only the fields that passed StoreScriptRequest rules.
        // This is safer than using $request->all() directly.
        $validated = $request->validated();

        // Safety check: Gemini API key must be set in .env
        if (! is_string(config('services.gemini.api_key')) || config('services.gemini.api_key') === '') {
            return back()->withErrors([
                'generation' => 'Gemini is not configured. Please contact the administrator.',
            ]);
        }

        // We store the user ID before entering the transaction
        // so we can re-fetch the user inside the transaction safely.
        $userId = $request->user()->getKey();

        // ----------------------------------------------------------------
        // STEP 1 — DB Transaction: create the script record & deduct credit
        // ----------------------------------------------------------------
        // DB::transaction() wraps everything inside it in one database
        // operation. If anything goes wrong, ALL changes are rolled back.
        // This ensures the credit is never deducted without saving the record.
        // ----------------------------------------------------------------
        try {
            $script = DB::transaction(function () use ($validated, $userId, $credits): ScriptGenerate {

                // Lock this user's row for the duration of the transaction.
                // This prevents two simultaneous requests from both passing
                // the credit check and spending credits twice.
                $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

                // Check if this exact request_uuid was already submitted.
                // This handles the case where the user clicks "Generate" twice.
                $existing = $user->scriptGenerates()
                    ->where('request_uuid', $validated['request_uuid'])
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    // If it already completed successfully, just redirect to it
                    if ($existing->status === 'completed') {
                        return $existing;
                    }

                    // If it previously failed, tell the user to try again
                    throw ValidationException::withMessages([
                        'generation' => $existing->status === 'failed'
                            ? 'This request already failed. Please try again.'
                            : 'This request is already being processed. Please wait.',
                    ]);
                }

                // Check the user has at least 1 credit before proceeding
                if ($user->credits_balance < 1) {
                    throw new InsufficientCreditsException;
                }

                // Collect all the form fields into one array for input_data
                // This is stored as JSON in the database for reference
                $inputData = [
                    'script_type'             => $validated['script_type'],
                    'platform'                => $validated['platform'],
                    'tone'                    => $validated['tone'],
                    'language'                => $validated['language'],
                    'duration'                => $validated['duration'],
                    'target_audience'         => $validated['target_audience'],
                    'pain_points'             => $validated['pain_points']             ?? null,
                    'product_name'            => $validated['product_name'],
                    'product_description'     => $validated['product_description'],
                    'product_benefits'        => $validated['product_benefits']        ?? null,
                    'additional_instructions' => $validated['additional_instructions'] ?? null,
                ];

                // Load brand profile if the user selected one
                // We also save a "snapshot" of it at the time of generation,
                // so even if the user edits/deletes the brand profile later,
                // we still know exactly what data was used for this script.
                $brandProfile = isset($validated['brand_profile_id'])
                    ? $user->brandProfiles()->findOrFail($validated['brand_profile_id'])
                    : null;

                // Same snapshot approach for custom tone
                $customTone = isset($validated['custom_tone_id'])
                    ? $user->customTones()->findOrFail($validated['custom_tone_id'])
                    : null;

                // Create the script record in the database with status = 'pending'
                $script = $user->scriptGenerates()->create([
                    'brandprofile_id'      => $brandProfile?->id,
                    'customtone_id'        => $customTone?->id,
                    'brand_profile_snapshot' => $brandProfile?->only([
                        'brand_name', 'industry', 'target_audience', 'personality',
                        'writing_style', 'preferred_words', 'avoid_words',
                        'emoji_usage', 'language', 'main_platform', 'pain_points',
                        'additional_instructions',
                    ]),
                    'custom_tone_snapshot'  => $customTone?->only([
                        'name', 'description', 'sample_content',
                    ]),
                    'request_uuid'          => $validated['request_uuid'],
                    'script_type'           => $validated['script_type'],
                    'brief'                 => $validated['product_name'].' — '.$validated['product_description'],
                    'input_data'            => $inputData,
                    'status'                => 'pending',
                ]);

                // Deduct 1 credit. We pass an idempotency key so the same
                // deduction can never accidentally happen twice.
                $credits->apply(
                    user: $user,
                    amount: -1,
                    type: CreditTransactionType::Generation,
                    idempotencyKey: 'script:'.$script->id.':charge',
                    description: 'Script generation',
                    referenceType: ScriptGenerate::class,
                    referenceId: $script->id,
                );

                return $script;
            });

        } catch (InsufficientCreditsException) {
            // User does not have enough credits
            return back()->withErrors([
                'credits' => 'You do not have enough credits to generate a script.',
            ]);

        } catch (UniqueConstraintViolationException) {
            // Two requests came in at the exact same millisecond with the same UUID.
            // This is very rare but we handle it safely.
            $existing = $request->user()
                ->scriptGenerates()
                ->where('request_uuid', $validated['request_uuid'])
                ->firstOrFail();

            if ($existing->status === 'completed') {
                return to_route('scripts.show', $existing);
            }

            return back()->withErrors([
                'generation' => 'This request is already being processed. Please wait.',
            ]);
        }

        // If the record was already completed (duplicate UUID hit the happy path above),
        // just redirect to it without calling the AI again.
        if ($script->status === 'completed') {
            return to_route('scripts.show', $script);
        }

        // ----------------------------------------------------------------
        // STEP 2 — Build the prompt and call the AI
        // ----------------------------------------------------------------

        // Build the structured text prompt from all the user inputs
        $prompt = $promptBuilder->build(
            inputData: $script->input_data ?? [],
            brandProfile: $script->brand_profile_snapshot,
            customTone: $script->custom_tone_snapshot,
        );

        try {
            // Send the prompt to Gemini and get the raw text back
            $generatedText = $gemini->generate(
                contentType: $script->script_type,
                brief: $prompt,
                brandProfile: $script->brand_profile_snapshot,
                customTone: $script->custom_tone_snapshot,
            );

        } catch (GeminiGenerationException $exception) {

            // ----------------------------------------------------------------
            // AI FAILED — refund the credit and mark the script as 'failed'
            // ----------------------------------------------------------------
            DB::transaction(function () use ($credits, $script, $userId): void {

                $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

                // Re-fetch and lock the script to avoid race conditions
                $lockedScript = $user->scriptGenerates()
                    ->whereKey($script->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Only refund if the script is still 'pending'
                // (prevents double refunds)
                if ($lockedScript->status === 'pending') {
                    $lockedScript->update([
                        'status'          => 'failed',
                        'failure_message' => 'The AI provider could not generate a script.',
                    ]);

                    // Refund 1 credit back to the user
                    $credits->apply(
                        user: $user,
                        amount: 1,
                        type: CreditTransactionType::Refund,
                        idempotencyKey: 'script:'.$lockedScript->id.':refund',
                        description: 'Refund for failed script generation',
                        referenceType: ScriptGenerate::class,
                        referenceId: $lockedScript->id,
                    );
                }
            });

            return back()->withErrors(['generation' => $exception->userMessage()]);
        }

        // ----------------------------------------------------------------
        // STEP 3 — AI succeeded — save the output and mark as 'completed'
        // ----------------------------------------------------------------
        DB::transaction(function () use ($script, $generatedText, $userId): void {

            $lockedScript = User::query()
                ->findOrFail($userId)
                ->scriptGenerates()
                ->whereKey($script->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Guard: only update if still pending (not already completed by another request)
            if ($lockedScript->status !== 'pending') {
                throw ValidationException::withMessages([
                    'generation' => 'This generation is no longer available to complete.',
                ]);
            }

            $lockedScript->update([
                'status'               => 'completed',
                'generate_script_text' => $generatedText,
                'failure_message'      => null,
            ]);
        });

        // Redirect to the Show page to display the generated script
        return to_route('scripts.show', $script);
    }

    /**
     * Show a single generated script.
     *
     * We verify that the script belongs to the current user (ownership check).
     * If another user tries to access this URL with a different ID, they get a 404.
     */
    public function show(Request $request, ScriptGenerate $script): Response
    {
        // findOrFail scoped to this user — prevents accessing other users' scripts
        $ownedScript = $request->user()
            ->scriptGenerates()
            ->findOrFail($script->id);

        return Inertia::render('Scripts/Show', [
            'script' => $ownedScript,
        ]);
    }

    /**
     * Show a paginated list of the user's generated scripts.
     * This feeds the generation history page.
     */
    public function index(Request $request): Response
    {
        $scripts = $request->user()
            ->scriptGenerates()
            ->latest()                // newest first
            ->paginate(10);           // 10 per page

        return Inertia::render('Scripts/Index', [
            'scripts' => $scripts,
        ]);
    }

    /**
     * Regenerate a script based on an existing one.
     *
     * This creates a NEW script record using the same input_data as before.
     * It does NOT overwrite the original script.
     * It costs 1 credit (same as a new generation).
     */
    public function regenerate(
        Request $request,
        ScriptGenerate $script,
        GeminiService $gemini,
        CreditService $credits,
        ScriptPromptBuilder $promptBuilder,
    ): RedirectResponse {

        // Verify ownership — user can only regenerate their own scripts
        $originalScript = $request->user()
            ->scriptGenerates()
            ->findOrFail($script->id);

        // Regeneration must start from a completed script
        if ($originalScript->status !== 'completed') {
            return back()->withErrors([
                'generation' => 'Only completed scripts can be regenerated.',
            ]);
        }

        $userId = $request->user()->getKey();

        // ----------------------------------------------------------------
        // Create a new script record with the same inputs, deduct 1 credit
        // ----------------------------------------------------------------
        try {
            $newScript = DB::transaction(function () use ($originalScript, $userId, $credits): ScriptGenerate {

                $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

                if ($user->credits_balance < 1) {
                    throw new InsufficientCreditsException;
                }

                // Create a new script row — does NOT touch the original
                $newScript = $user->scriptGenerates()->create([
                    'brand_profile_id'       => $originalScript->brand_profile_id,
                    'custom_tone_id'         => $originalScript->custom_tone_id,
                    'brand_profile_snapshot' => $originalScript->brand_profile_snapshot,
                    'custom_tone_snapshot'   => $originalScript->custom_tone_snapshot,
                    'request_uuid'           => \Illuminate\Support\Str::uuid(),
                    'script_type'            => $originalScript->script_type,
                    'brief'                  => $originalScript->brief,
                    'input_data'             => $originalScript->input_data,
                    'status'                 => 'pending',
                ]);

                $credits->apply(
                    user: $user,
                    amount: -1,
                    type: CreditTransactionType::Generation,
                    idempotencyKey: 'script:'.$newScript->id.':charge',
                    description: 'Script regeneration',
                    referenceType: ScriptGenerate::class,
                    referenceId: $newScript->id,
                );

                return $newScript;
            });

        } catch (InsufficientCreditsException) {
            return back()->withErrors([
                'credits' => 'You do not have enough credits to regenerate this script.',
            ]);
        }

        // Build the same prompt from the original input data
        $prompt = $promptBuilder->build(
            inputData: $newScript->input_data ?? [],
            brandProfile: $newScript->brand_profile_snapshot,
            customTone: $newScript->custom_tone_snapshot,
        );

        try {
            $generatedText = $gemini->generate(
                contentType: $newScript->script_type,
                brief: $prompt,
                brandProfile: $newScript->brand_profile_snapshot,
                customTone: $newScript->custom_tone_snapshot,
            );

        } catch (GeminiGenerationException $exception) {

            // Refund and mark as failed (same as in store())
            DB::transaction(function () use ($credits, $newScript, $userId): void {
                $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
                $lockedScript = $user->scriptGenerates()->whereKey($newScript->id)->lockForUpdate()->firstOrFail();

                if ($lockedScript->status === 'pending') {
                    $lockedScript->update([
                        'status'          => 'failed',
                        'failure_message' => 'The AI provider could not regenerate the script.',
                    ]);

                    $credits->apply(
                        user: $user,
                        amount: 1,
                        type: CreditTransactionType::Refund,
                        idempotencyKey: 'script:'.$lockedScript->id.':refund',
                        description: 'Refund for failed script regeneration',
                        referenceType: ScriptGenerate::class,
                        referenceId: $lockedScript->id,
                    );
                }
            });

            return back()->withErrors(['generation' => $exception->userMessage()]);
        }

        // Save the generated text
        DB::transaction(function () use ($newScript, $generatedText, $userId): void {
            $lockedScript = User::query()
                ->findOrFail($userId)
                ->scriptGenerates()
                ->whereKey($newScript->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedScript->status !== 'pending') {
                throw ValidationException::withMessages([
                    'generation' => 'This regeneration is no longer available to complete.',
                ]);
            }

            $lockedScript->update([
                'status'               => 'completed',
                'generate_script_text' => $generatedText,
                'failure_message'      => null,
            ]);
        });

        return to_route('scripts.show', $newScript);
    }
}
