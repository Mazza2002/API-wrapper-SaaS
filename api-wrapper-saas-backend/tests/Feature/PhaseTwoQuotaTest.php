<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Plan;
use App\Models\Usage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwoQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_get_free_plan_and_can_generate_within_quota(): void
    {
        $plan = Plan::create([
            'name' => 'Free',
            'monthly_credit_quota' => 100,
            'rate_limit_per_minute' => 10,
            'price' => 0,
        ]);

        $user = User::factory()->create(['plan_id' => $plan->id]);
        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => 'Test key',
            'key_hash' => password_hash('sk_test_123', PASSWORD_BCRYPT),
            'prefix' => 'sk_',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer sk_test_123')
            ->postJson('/api/v1/generate', ['prompt' => 'Test prompt']);

        $response->assertStatus(200)
            ->assertJsonPath('data.provider', 'demo-httpbin');

        $this->assertDatabaseHas('usages', [
            'user_id' => $user->id,
            'endpoint' => '/api/v1/generate',
        ]);
    }

    public function test_quota_exceeded_returns_429(): void
    {
        $plan = Plan::create([
            'name' => 'Free',
            'monthly_credit_quota' => 1,
            'rate_limit_per_minute' => 10,
            'price' => 0,
        ]);

        $user = User::factory()->create(['plan_id' => $plan->id]);
        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => 'Test key',
            'key_hash' => password_hash('sk_quota_123', PASSWORD_BCRYPT),
            'prefix' => 'sk_',
            'is_active' => true,
        ]);

        Usage::create([
            'user_id' => $user->id,
            'api_key_id' => $apiKey->id,
            'endpoint' => '/api/v1/generate',
            'status' => 'success',
            'credits_used' => 1,
            'latency_ms' => 100,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer sk_quota_123')
            ->postJson('/api/v1/generate', ['prompt' => 'Over quota']);

        $response->assertStatus(429)
            ->assertJsonPath('error.message', 'Monthly quota exceeded for this plan.');
    }
}
