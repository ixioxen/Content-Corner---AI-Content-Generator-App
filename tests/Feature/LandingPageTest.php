<?php

namespace Tests\Feature;

use App\Models\CreditPackage;
use App\Models\GeneratedContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_displays_live_project_stats_and_active_credit_packages(): void
    {
        $user = User::factory()->create();
        User::factory()->create();

        $user->brandProfiles()->create([
            'brand_name' => 'Example brand',
        ]);

        GeneratedContent::query()->create([
            'user_id' => $user->id,
            'request_uuid' => '0fa7f8d4-f551-4335-81e2-dba138b8d4da',
            'content_type' => 'social',
            'brief' => 'Write a product announcement.',
            'generated_text' => 'A new product is here.',
            'status' => 'completed',
        ]);

        GeneratedContent::query()->create([
            'user_id' => $user->id,
            'request_uuid' => '0fa7f8d4-f551-4335-81e2-dba138b8d4db',
            'content_type' => 'social',
            'brief' => 'Write another product announcement.',
            'status' => 'pending',
        ]);

        CreditPackage::query()->create([
            'name' => 'Starter',
            'credits_amount' => 100,
            'price_amount' => 4.99,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        CreditPackage::query()->create([
            'name' => 'Hidden package',
            'credits_amount' => 500,
            'price_amount' => 19.99,
            'currency' => 'USD',
            'is_active' => false,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->where('stats.creators', 2)
            ->where('stats.generatedContents', 1)
            ->where('stats.brandProfiles', 1)
            ->has('creditPackages', 1)
            ->where('creditPackages.0.name', 'Starter')
            ->where('creditPackages.0.credits_amount', 100)
        );
    }
}
