<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PhaseOneApiSaasTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_auth_flow_works(): void
    {
        $payload = [
            'name' => 'Jane Developer',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ];

        $register = $this->postJson('/api/register', $payload);
        $register->assertStatus(201)
            ->assertJsonPath('data.user.email', 'jane@example.com')
            ->assertJsonPath('data.token', fn ($token) => is_string($token) && $token !== '');

        $login = $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ]);

        $login->assertStatus(200)
            ->assertJsonPath('data.user.email', 'jane@example.com')
            ->assertJsonPath('data.token', fn ($token) => is_string($token) && $token !== '');

        $token = $login->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me')
            ->assertStatus(200)
            ->assertJsonPath('data.email', 'jane@example.com');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout')
            ->assertStatus(200)
            ->assertJsonPath('data.message', 'Logged out successfully.');
    }

    public function test_api_key_lifecycle_and_generate_endpoint_work(): void
    {
        $user = \App\Models\User::factory()->create();

        $createResponse = $this->actingAs($user, 'web')
            ->postJson('/api/v1/keys', [
                'name' => 'Production Key',
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'Production Key')
            ->assertJsonPath('data.key', fn ($key) => is_string($key) && str_starts_with($key, 'sk_'));

        $key = $createResponse->json('data.key');
        $createdKey = ApiKey::query()->first();
        $this->assertNotNull($createdKey);
        $this->assertTrue($createdKey->is_active);

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->getJson('/api/v1/keys')
            ->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Production Key');

        $this->withHeader('Authorization', 'Bearer ' . $key)
            ->postJson('/api/v1/generate', [
                'prompt' => 'Create a greeting for an API-wrapper SaaS',
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['provider', 'status', 'result'],
            ]);

        $this->actingAs($user, 'web')
            ->deleteJson('/api/v1/keys/' . $createdKey->id)
            ->assertStatus(200)
            ->assertJsonPath('data.message', 'API key revoked successfully.');
    }
}
