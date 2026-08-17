<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseThreeRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_key_rate_limit_returns_429_with_retry_after_header(): void
    {
        $plan = Plan::create([
            'name' => 'Free',
            'monthly_credit_quota' => 100,
            'rate_limit_per_minute' => 2,
            'price' => 0,
        ]);

        $user = User::factory()->create(['plan_id' => $plan->id]);
        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => 'Rate-limited key',
            'key_hash' => password_hash('sk_rate_123', PASSWORD_BCRYPT),
            'prefix' => 'sk_',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer sk_rate_123')
            ->postJson('/api/v1/generate', ['prompt' => 'First request'])
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer sk_rate_123')
            ->postJson('/api/v1/generate', ['prompt' => 'Second request'])
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer sk_rate_123')
            ->postJson('/api/v1/generate', ['prompt' => 'Third request'])
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonPath('error.message', 'Rate limit exceeded. Please try again later.');
    }
}
