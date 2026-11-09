<?php

namespace App\Http\Controllers;

use App\Models\BrandProfile;
use App\Models\CreditPackage;
use App\Models\GeneratedContent;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'stats' => [
                'creators' => User::query()->count(),
                'generatedContents' => GeneratedContent::query()->where('status', 'completed')->count(),
                'brandProfiles' => BrandProfile::query()->count(),
            ],
            'creditPackages' => CreditPackage::query()
                ->where('is_active', true)
                ->orderBy('price_amount')
                ->get(['id', 'name', 'credits_amount', 'price_amount', 'currency']),
        ]);
    }
}
