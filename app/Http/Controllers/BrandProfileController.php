<?php

namespace App\Http\Controllers;

use App\Models\BrandProfile;
use App\Models\BrandProfileUndoAction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BrandProfileController extends Controller
{
    public function index(Request $request): Response
    {
        BrandProfileUndoAction::query()->where('expires_at', '<=', now())->delete();

        $user = $request->user();
        $profiles = $request->user()->brandProfiles()->latest()->get();
        $limit = $this->profileLimit();
        $reservedProfiles = $profiles->count() + $this->pendingDeleteCount($user);

        return Inertia::render('BrandProfiles/Index', [
            'profiles' => $profiles,
            'profileLimit' => $limit,
            'canCreate' => $reservedProfiles < $limit,
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        BrandProfileUndoAction::query()->where('expires_at', '<=', now())->delete();

        $user = $request->user();

        if ($user->brandProfiles()->count() + $this->pendingDeleteCount($user) >= $this->profileLimit()) {
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

        [, $undoToken] = DB::transaction(function () use ($data, $userId): array {
            $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();

            if ($user->brandProfiles()->count() + $this->pendingDeleteCount($user) >= $this->profileLimit()) {
                throw ValidationException::withMessages([
                    'limit' => 'You have reached the maximum of '.$this->profileLimit().' brand profiles.',
                ]);
            }

            $profile = $user->brandProfiles()->create($data);
            $undoToken = $this->recordUndo($user, $profile, 'create', null);

            return [$profile, $undoToken];
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Brand profile created.',
            'undoUrl' => route('brands.undo', $undoToken, false),
        ]);

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
        $data = $this->validatedProfile($request);
        $userId = $request->user()->getKey();
        [, $undoToken] = DB::transaction(function () use ($brand, $data, $userId): array {
            $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
            $profile = $user->brandProfiles()->whereKey($brand->id)->lockForUpdate()->firstOrFail();
            $snapshot = $profile->getRawOriginal();
            $profile->update($data);

            return [
                $profile,
                $this->recordUndo($user, $profile, 'update', $snapshot),
            ];
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Brand profile updated.',
            'undoUrl' => route('brands.undo', $undoToken, false),
        ]);

        return to_route('brands.index');
    }

    public function destroy(Request $request, BrandProfile $brand): RedirectResponse
    {
        $userId = $request->user()->getKey();
        $undoToken = DB::transaction(function () use ($brand, $userId): string {
            $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
            $profile = $user->brandProfiles()->whereKey($brand->id)->lockForUpdate()->firstOrFail();
            $snapshot = $profile->getRawOriginal();
            $profile->delete();

            return $this->recordUndo($user, $profile, 'delete', $snapshot);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Brand profile deleted.',
            'undoUrl' => route('brands.undo', $undoToken, false),
        ]);

        return to_route('brands.index');
    }

    public function undo(Request $request, string $token): RedirectResponse
    {
        $userId = $request->user()->getKey();
        $tokenHash = hash('sha256', $token);

        DB::transaction(function () use ($tokenHash, $userId): void {
            $user = User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
            $undo = $user->brandProfileUndoActions()
                ->where('token_hash', $tokenHash)
                ->lockForUpdate()
                ->first();

            if (! $undo || $undo->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'undo' => 'This change can no longer be undone.',
                ]);
            }

            if ($undo->action === 'create') {
                $deleted = $user->brandProfiles()->whereKey($undo->profile_id)->delete();

                if ($deleted !== 1) {
                    throw ValidationException::withMessages([
                        'undo' => 'This brand profile has changed and can no longer be undone.',
                    ]);
                }
            } elseif ($undo->action === 'update') {
                if (! $undo->snapshot || ! $user->brandProfiles()->whereKey($undo->profile_id)->exists()) {
                    throw ValidationException::withMessages([
                        'undo' => 'This brand profile has changed and can no longer be undone.',
                    ]);
                }

                DB::table('brand_profiles')
                    ->where('user_id', $user->id)
                    ->where('id', $undo->profile_id)
                    ->update($undo->snapshot);
            } elseif ($undo->action === 'delete') {
                if (! $undo->snapshot) {
                    throw ValidationException::withMessages([
                        'undo' => 'This brand profile can no longer be restored.',
                    ]);
                }

                $otherPendingDeletes = $this->pendingDeleteCount($user, $undo->id);
                if ($user->brandProfiles()->count() + $otherPendingDeletes >= $this->profileLimit()) {
                    throw ValidationException::withMessages([
                        'undo' => 'There is no room to restore this profile. Remove another profile and try again.',
                    ]);
                }

                DB::table('brand_profiles')->insert($undo->snapshot);
            } else {
                throw ValidationException::withMessages([
                    'undo' => 'This change can no longer be undone.',
                ]);
            }

            $undo->delete();
        });

        Inertia::flash('toast', ['type' => 'info', 'message' => 'Brand profile change undone.']);

        return to_route('brands.index');
    }

    private function recordUndo(User $user, BrandProfile $profile, string $action, ?array $snapshot): string
    {
        $user->brandProfileUndoActions()->where('profile_id', $profile->id)->delete();

        $token = Str::random(64);
        $user->brandProfileUndoActions()->create([
            'token_hash' => hash('sha256', $token),
            'action' => $action,
            'profile_id' => $profile->id,
            'snapshot' => $snapshot,
            'expires_at' => now()->addSeconds((int) config('brand_profiles.undo_seconds', 5)),
        ]);

        return $token;
    }

    private function findOwnedProfile(Request $request, BrandProfile $profile): BrandProfile
    {
        return $request->user()->brandProfiles()->findOrFail($profile->id);
    }

    private function pendingDeleteCount(User $user, ?int $exceptActionId = null): int
    {
        return $user->brandProfileUndoActions()
            ->where('action', 'delete')
            ->where('expires_at', '>', now())
            ->when($exceptActionId, fn ($query) => $query->where('id', '!=', $exceptActionId))
            ->count();
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
