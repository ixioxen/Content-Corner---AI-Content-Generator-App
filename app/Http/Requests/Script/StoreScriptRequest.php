<?php

namespace App\Http\Requests\Script;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScriptRequest extends FormRequest
{
    /**
     * Decide who is allowed to make this request.
     *
     * Returning true means: any logged-in user can submit the form.
     * The auth middleware on the route already ensures the user is logged in,
     * so we just return true here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define all the validation rules for the form.
     *
     * Laravel checks every field against these rules automatically
     * before the controller even runs. If anything fails, it sends
     * the errors back to the form without us writing any extra code.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [

            // -------------------------------------------------------
            // Idempotency key — prevents duplicate submissions.
            // The frontend generates a UUID once per form load.
            // If the same UUID is sent twice, the backend will detect it.
            // -------------------------------------------------------
            'request_uuid' => ['required', 'uuid'],

            // -------------------------------------------------------
            // Script Type — what kind of script is this?
            // 'in' means it must be one of these exact values only.
            // -------------------------------------------------------
            'script_type' => [
                'required',
                'string',
                Rule::in([
                    'product_promotion',
                    'product_selling',
                    'education',
                    'brand_awareness',
                    'storytelling',
                    'audience_engagement',
                    'business_service',
                    'content_blog',
                ]),
            ],

            // -------------------------------------------------------
            // Platform — where will this video be posted?
            // -------------------------------------------------------
            'platform' => [
                'required',
                'string',
                Rule::in([
                    'tiktok',
                    'facebook_reels',
                    'youtube_shorts',
                ]),
            ],

            // -------------------------------------------------------
            // Tone — what style/mood should the script have?
            // -------------------------------------------------------
            'tone' => [
                'required',
                'string',
                Rule::in([
                    'professional',
                    'friendly',
                    'casual',
                    'persuasive',
                    'educational',
                    'funny',
                    'storytelling',
                    'custom',
                ]),
            ],

            // -------------------------------------------------------
            // Language — which language should the script be written in?
            // -------------------------------------------------------
            'language' => [
                'required',
                'string',
                Rule::in(['english', 'burmese']),
            ],

            // -------------------------------------------------------
            // Duration — how long is the video in seconds?
            // 'in' checks against specific allowed values.
            // -------------------------------------------------------
            'duration' => [
                'required',
                'integer',
                Rule::in([15, 30, 60, 90, 120,150,180]),
            ],

            // -------------------------------------------------------
            // Target Audience — who is this video for? (required)
            // -------------------------------------------------------
            'target_audience' => ['required', 'string', 'max:500'],

            // -------------------------------------------------------
            // Audience Pain Points — what problem does the product solve?
            // 'nullable' means the user can leave this empty.
            // -------------------------------------------------------
            'pain_points' => ['nullable', 'string', 'max:500'],

            // -------------------------------------------------------
            // Product / Service Name (required)
            // -------------------------------------------------------
            'product_name' => ['required', 'string', 'max:255'],

            // -------------------------------------------------------
            // Product Description (required)
            // -------------------------------------------------------
            'product_description' => ['required', 'string', 'max:2000'],

            // -------------------------------------------------------
            // Product Benefits — why should the customer choose this?
            // -------------------------------------------------------
            'product_benefits' => ['nullable', 'string', 'max:1000'],

            // -------------------------------------------------------
            // Additional Instructions — any extra notes from the user.
            // -------------------------------------------------------
            'additional_instructions' => ['nullable', 'string', 'max:1000'],

            // -------------------------------------------------------
            // Brand Profile ID — optional, must belong to the logged-in user.
            // Rule::exists() checks the database to make sure it exists
            // AND the user_id column matches the current user's ID.
            // This prevents a user from using someone else's brand profile.
            // -------------------------------------------------------
            'brand_profile_id' => [
                'nullable',
                'integer',
                Rule::exists('brand_profiles', 'id')
                    ->where('user_id', $this->user()?->id),
            ],

            // -------------------------------------------------------
            // Custom Tone ID — optional, must belong to the logged-in user.
            // Same security check as brand_profile_id above.
            // -------------------------------------------------------
            'custom_tone_id' => [
                'nullable',
                'integer',
                Rule::exists('custom_tones', 'id')
                    ->where('user_id', $this->user()?->id),
            ],
        ];
    }

    /**
     * Custom error messages shown to the user when validation fails.
     *
     * The key format is: 'field_name.rule_name' => 'Your message'
     * This overrides Laravel's default (sometimes confusing) messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'request_uuid.required' => 'A unique request ID is required.',
            'request_uuid.uuid'     => 'The request ID must be a valid UUID.',
            'script_type.in'        => 'Please select a valid script objective.',
            'platform.in'           => 'Please select a valid platform.',
            'tone.in'               => 'Please select a valid tone.',
            'language.in'           => 'Please select a valid language.',
            'duration.in'           => 'Please select a valid duration (15, 30, 60, 90, or 120 seconds).',
            'target_audience.required' => 'Target audience is required.',
            'product_name.required'    => 'Product or service name is required.',
            'product_description.required' => 'Product description is required.',
            'brand_profile_id.exists'  => 'The selected brand profile does not exist or does not belong to you.',
            'custom_tone_id.exists'    => 'The selected custom tone does not exist or does not belong to you.',
        ];
    }
}
