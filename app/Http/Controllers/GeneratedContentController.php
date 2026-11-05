<?php

namespace App\Http\Controllers;

use App\Models\GeneratedContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeneratedContentController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Generations/Index', [
            'generations' => $request->user()->generatedContents()
                ->where('status', 'completed')
                ->latest()
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function show(Request $request, GeneratedContent $generation): Response
    {
        return Inertia::render('Generations/Show', [
            'generation' => $this->findOwnedGeneration($request, $generation),
        ]);
    }

    public function destroy(Request $request, GeneratedContent $generation): RedirectResponse
    {
        $this->findOwnedGeneration($request, $generation)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Generated content deleted.']);

        return to_route('generations.index');
    }

    private function findOwnedGeneration(Request $request, GeneratedContent $generation): GeneratedContent
    {
        return $request->user()->generatedContents()
            ->where('status', 'completed')
            ->findOrFail($generation->id);
    }
}
