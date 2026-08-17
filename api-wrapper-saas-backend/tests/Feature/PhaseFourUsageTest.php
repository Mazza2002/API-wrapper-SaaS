<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PhaseFourUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_usage_logging_job_is_dispatched_and_usage_stats_endpoint_returns_data(): void
    {
        Queue::fake();

        $plan = Plan::create([
            'name' => 'Free',
            'monthly_credit_quota' => 100,
            'rate_limit_per_minute' => 10,
            'price' => 0,
        ]);

        $user = User::factory()->create(['plan_id' => $plan->id]);
        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => 'Usage key',
            'key_hash' => password_hash('sk_usage_123', PASSWORD_BCRYPT),
            'prefix' => 'sk_',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer sk_usage_123')
            ->postJson('/api/v1/generate', ['prompt' => 'Store usage'])
            ->assertStatus(200);

        Queue::assertPushed(\App\Jobs\LogApiUsage::class);

        $this->actingAs($user, 'web')
            ->getJson('/api/v1/usage')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_requests',
                    'total_credits_used',
                    'usage',
                ],
            ]);
    }
}
