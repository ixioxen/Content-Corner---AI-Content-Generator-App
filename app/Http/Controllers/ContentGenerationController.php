<?php

namespace App\Http\Controllers;

use App\Enums\CreditTransactionType;
use App\Exceptions\GeminiGenerationException;
use App\Exceptions\InsufficientCreditsException;
use App\Models\GeneratedContent;
use App\Models\User;
use App\Services\CreditService;
use App\Services\GeminiService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContentGenerationController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Generations/Create', [
            'creditsBalance' => $request->user()->credits_balance,
            'providerConfigured' => is_string(config('services.gemini.api_key'))
                && config('services.gemini.api_key') !== '',
            'brandProfiles' => $request->user()->brandProfiles()
                ->latest()
                ->get(['id', 'brand_name', 'industry', 'language']),
            'tones' => $request->user()->customTones()
                ->latest()
                ->get(['id', 'name', 'description']),
        ]);
    }

    public function store(Request $request, GeminiService $gemini, CreditService $credits): RedirectResponse
    {
        $validated = $request->validate([
            'request_uuid' => ['required', 'uuid'],
            'content_type' => ['required', Rule::in(['social_post', 'marketing_copy', 'blog_outline'])],
            'brief' => ['required', 'string', 'min:10', 'max:5000'],
            'brand_profile_id' => [
                'nullable',
                'integer',
                Rule::exists('brand_profiles', 'id')->where('user_id', $request->user()->id),
            ],
            'custom_tone_id' => [
                'nullable',
                'integer',
                Rule::exists('custom_tones', 'id')->where('user_id', $request->user()->id),
            ],
        ]);

        if (! is_string(config('services.gemini.api_key')) || config('services.gemini.api_key') === '') {
            return back()->withErrors([
                'generation' => 'Gemini is not configured. Add GEMINI_API_KEY to the server environment before generating content.',
            ]);
        }

        $userId = $request->user()->getKey();

        try {
            $generation = DB::transaction(function () use ($validated, $userId, $credits): GeneratedContent {
                $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
                $existing = $user->generatedContents()
                    ->where('request_uuid', $validated['request_uuid'])
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    if ($existing->status === 'completed') {
                        return $existing;
                    }

                    throw ValidationException::withMessages([
                        'generation' => $existing->status === 'failed'
                            ? 'This request already failed and was refunded. Submit again to retry.'
                            : 'This request is already being processed. Please wait.',
                    ]);
                }

                if ($user->credits_balance < 1) {
                    throw new InsufficientCreditsException;
                }

                $brandProfile = isset($validated['brand_profile_id'])
                    ? $user->brandProfiles()->findOrFail($validated['brand_profile_id'])
                    : null;
                $customTone = isset($validated['custom_tone_id'])
                    ? $user->customTones()->findOrFail($validated['custom_tone_id'])
                    : null;

                $content = $user->generatedContents()->create([
                    'brand_profile_id' => $brandProfile?->id,
                    'custom_tone_id' => $customTone?->id,
                    'request_uuid' => $validated['request_uuid'],
                    'content_type' => $validated['content_type'],
                    'brief' => $validated['brief'],
                    'brand_profile_snapshot' => $brandProfile?->only([
                        'brand_name',
                        'industry',
                        'target_audience',
                        'personality',
                        'writing_style',
                        'preferred_words',
                        'avoid_words',
                        'emoji_usage',
                        'language',
                        'main_platform',
                        'pain_points',
                        'additional_instructions',
                    ]),
                    'custom_tone_snapshot' => $customTone?->only([
                        'name',
                        'description',
                        'sample_content',
                    ]),
                    'status' => 'pending',
                ]);

                $credits->apply(
                    user: $user,
                    amount: -1,
                    type: CreditTransactionType::Generation,
                    idempotencyKey: 'generation:'.$content->id.':charge',
                    description: 'Content generation',
                    referenceType: GeneratedContent::class,
                    referenceId: $content->id,
                );

                return $content;
            });
        } catch (InsufficientCreditsException) {
            return back()->withErrors([
                'credits' => 'You do not have enough credits to generate content.',
            ]);
        } catch (UniqueConstraintViolationException) {
            $existing = $request->user()->generatedContents()
                ->where('request_uuid', $validated['request_uuid'])
                ->firstOrFail();

            if ($existing->status === 'completed') {
                return to_route('generations.show', $existing);
            }

            return back()->withErrors([
                'generation' => 'This request is already being processed. Please wait before trying again.',
            ]);
        }

        if ($generation->status === 'completed') {
            return to_route('generations.show', $generation);
        }

        $brandProfile = $generation->brand_profile_snapshot;
        $customTone = $generation->custom_tone_snapshot;

        try {
            $generatedText = $gemini->generate(
                contentType: $generation->content_type,
                brief: $generation->brief,
                brandProfile: $brandProfile,
                customTone: $customTone,
            );
        } catch (GeminiGenerationException $exception) {
            DB::transaction(function () use ($credits, $generation, $userId): void {
                $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
                $lockedGeneration = $user->generatedContents()
                    ->whereKey($generation->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedGeneration->status === 'pending') {
                    $lockedGeneration->update([
                        'status' => 'failed',
                        'failure_message' => 'The AI provider could not generate content.',
                    ]);

                    $credits->apply(
                        user: $user,
                        amount: 1,
                        type: CreditTransactionType::Refund,
                        idempotencyKey: 'generation:'.$lockedGeneration->id.':refund',
                        description: 'Refund for failed content generation',
                        referenceType: GeneratedContent::class,
                        referenceId: $lockedGeneration->id,
                    );
                }
            });

            return back()->withErrors(['generation' => $exception->userMessage()]);
        }

        DB::transaction(function () use ($generation, $generatedText, $userId): void {
            $lockedGeneration = User::query()
                ->findOrFail($userId)
                ->generatedContents()
                ->whereKey($generation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedGeneration->status !== 'pending') {
                throw ValidationException::withMessages([
                    'generation' => 'This generation is no longer available to complete.',
                ]);
            }

            $lockedGeneration->update([
                'status' => 'completed',
                'generated_text' => $generatedText,
                'failure_message' => null,
            ]);
        });

        return to_route('generations.show', $generation);
    }
}
