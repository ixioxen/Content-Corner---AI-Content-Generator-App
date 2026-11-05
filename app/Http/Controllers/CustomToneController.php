<?php

namespace App\Http\Controllers;

use App\Models\CustomTone;
use App\Models\CustomToneUndoAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CustomToneController extends Controller
{
    public function index(Request $request): Response
    {
        CustomToneUndoAction::query()->where('expires_at', '<=', now())->delete();

        return Inertia::render('Tones/Index', [
            'tones' => $request->user()->customTones()->latest()->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tones/Form', [
            'tone' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->customTones()->create($this->validatedTone($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tone created.']);

        return to_route('tones.index');
    }

    public function edit(Request $request, CustomTone $tone): Response
    {
        $tone = $this->findOwnedTone($request, $tone);

        return Inertia::render('Tones/Form', [
            'tone' => $tone,
        ]);
    }

    public function update(Request $request, CustomTone $tone): RedirectResponse
    {
        $tone = $this->findOwnedTone($request, $tone);
        $tone->update($this->validatedTone($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tone updated.']);

        return to_route('tones.index');
    }

    public function destroy(Request $request, CustomTone $tone): RedirectResponse
    {
        $user = $request->user();
        $ownedTone = $this->findOwnedTone($request, $tone);
        $token = DB::transaction(function () use ($user, $ownedTone): string {
            $tone = $user->customTones()->whereKey($ownedTone->id)->lockForUpdate()->firstOrFail();
            $tone->delete();

            $user->customToneUndoActions()->where('custom_tone_id', $tone->id)->delete();
            $token = Str::random(64);
            $user->customToneUndoActions()->create([
                'token_hash' => hash('sha256', $token),
                'custom_tone_id' => $tone->id,
                'expires_at' => now()->addSeconds((int) config('brand_profiles.undo_seconds', 5)),
            ]);

            return $token;
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Tone deleted.',
            'undoUrl' => route('tones.undo', $token, false),
        ]);

        return to_route('tones.index');
    }

    public function undo(Request $request, string $token): RedirectResponse
    {
        DB::transaction(function () use ($request, $token): void {
            $undo = $request->user()->customToneUndoActions()
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if (! $undo || $undo->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'undo' => 'This tone can no longer be restored.',
                ]);
            }

            $tone = $request->user()->customTones()
                ->withTrashed()
                ->whereKey($undo->custom_tone_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $tone->trashed()) {
                throw ValidationException::withMessages([
                    'undo' => 'This tone has already been restored.',
                ]);
            }

            $tone->restore();
            $undo->delete();
        });

        Inertia::flash('toast', ['type' => 'info', 'message' => 'Tone deletion undone.']);

        return to_route('tones.index');
    }

    private function findOwnedTone(Request $request, CustomTone $tone): CustomTone
    {
        return $request->user()->customTones()->findOrFail($tone->id);
    }

    /**
     * @return array{name: string, description: string, sample_content: string}
     */
    private function validatedTone(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:5000'],
            'sample_content' => ['required', 'string', 'max:10000'],
        ]);
    }
}
