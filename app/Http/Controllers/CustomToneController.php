<?php

namespace App\Http\Controllers;

use App\Models\CustomTone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomToneController extends Controller
{
    public function index(Request $request): Response
    {
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
        $this->findOwnedTone($request, $tone)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tone deleted.']);

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
