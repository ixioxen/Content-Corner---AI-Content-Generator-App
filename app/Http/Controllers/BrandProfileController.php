<?php

namespace App\Http\Controllers;

use App\Models\BrandProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BrandProfileController extends Controller
{
    public function index(Request $request): Response
    {
        $profiles = $request->user()->brandProfiles()->latest()->get();
        $limit = $this->profileLimit();

        return Inertia::render('BrandProfiles/Index', [
            'profiles' => $profiles,
            'profileLimit' => $limit,
            'canCreate' => $profiles->count() < $limit,
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()->brandProfiles()->count() >= $this->profileLimit()) {
            return to_route('brands.index')->withErrors([
                'limit' => 'You have reached the maximum of '.$this->profileLimit().' brand profiles.',
            ]);
        }

        return Inertia::render('BrandProfiles/Form', [
            'profile' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedProfile($request);
        $userId = $request->user()->getKey();

        DB::transaction(function () use ($data, $userId): void {
            $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

            if ($user->brandProfiles()->count() >= $this->profileLimit()) {
                throw ValidationException::withMessages([
                    'limit' => 'You have reached the maximum of '.$this->profileLimit().' brand profiles.',
                ]);
            }

            $user->brandProfiles()->create($data);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Brand profile created.']);

        return to_route('brands.index');
    }

    public function edit(Request $request, BrandProfile $brand): Response
    {
        return Inertia::render('BrandProfiles/Form', [
            'profile' => $this->findOwnedProfile($request, $brand),
        ]);
    }

    public function update(Request $request, BrandProfile $brand): RedirectResponse
    {
        $profile = $this->findOwnedProfile($request, $brand);
        $profile->update($this->validatedProfile($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Brand profile updated.']);

        return to_route('brands.index');
    }

    public function destroy(Request $request, BrandProfile $brand): RedirectResponse
    {
        $this->findOwnedProfile($request, $brand)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Brand profile deleted.']);

        return to_route('brands.index');
    }

    private function findOwnedProfile(Request $request, BrandProfile $profile): BrandProfile
    {
        return $request->user()->brandProfiles()->findOrFail($profile->id);
    }

    private function profileLimit(): int
    {
        return (int) config('brand_profiles.max_per_user', 3);
    }

    /**
     * @return array{
     *     brand_name: string,
     *     industry: ?string,
     *     target_audience: ?string,
     *     personality: ?string,
     *     writing_style: ?string,
     *     preferred_words: array<int, string>,
     *     avoid_words: array<int, string>,
     *     emoji_usage: string,
     *     language: string,
     *     main_platform: ?string,
     *     pain_points: ?string,
     *     additional_instructions: ?string
     * }
     */
    private function validatedProfile(Request $request): array
    {
        $validated = $request->validate([
            'brand_name' => ['required', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:100'],
            'target_audience' => ['nullable', 'string', 'max:5000'],
            'personality' => ['nullable', 'string', 'max:5000'],
            'writing_style' => ['nullable', 'string', 'max:5000'],
            'preferred_words' => ['nullable', 'array', 'max:50'],
            'preferred_words.*' => ['required', 'string', 'max:80', 'distinct:ignore_case'],
            'avoid_words' => ['nullable', 'array', 'max:50'],
            'avoid_words.*' => ['required', 'string', 'max:80', 'distinct:ignore_case'],
            'emoji_usage' => ['required', Rule::in(['never', 'minimal', 'moderate', 'frequent'])],
            'language' => ['required', 'string', 'max:100'],
            'main_platform' => ['nullable', 'string', 'max:100'],
            'pain_points' => ['nullable', 'string', 'max:5000'],
            'additional_instructions' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['preferred_words'] ??= [];
        $validated['avoid_words'] ??= [];
        $validated['industry'] ??= null;
        $validated['target_audience'] ??= null;
        $validated['personality'] ??= null;
        $validated['writing_style'] ??= null;
        $validated['main_platform'] ??= null;
        $validated['pain_points'] ??= null;
        $validated['additional_instructions'] ??= null;

        return $validated;
    }
}
