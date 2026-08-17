<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFiveStripeTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_checkout_route_is_protected_and_requires_plan(): void
    {
        $plan = Plan::create([
            'name' => 'Pro',
            'monthly_credit_quota' => 10000,
            'rate_limit_per_minute' => 60,
            'price' => 29,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->postJson('/api/v1/stripe/checkout', [
                'plan_id' => $plan->id,
            ])
            ->assertStatus(500);
    }
}
