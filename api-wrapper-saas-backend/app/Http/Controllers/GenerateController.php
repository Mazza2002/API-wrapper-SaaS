<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRequest;
use App\Services\ExternalApiClient;
use App\Services\QuotaService;
use App\Services\RateLimitService;
use Illuminate\Http\JsonResponse;

class GenerateController extends Controller
{
    public function __construct(
        protected ExternalApiClient $externalApiClient,
        protected QuotaService $quotaService,
        protected RateLimitService $rateLimitService,
    ) {}

    public function __invoke(GenerateRequest $request): JsonResponse
    {
        $user = $request->user();
        $apiKey = $request->attributes->get('api_key');

        $this->quotaService->ensureUserCanGenerate($user);
        $this->rateLimitService->check($apiKey);

        $result = $this->externalApiClient->generate($request->input('prompt'));

        $user->usages()->create([
            'api_key_id' => $request->attributes->get('api_key')?->id,
            'endpoint' => '/api/v1/generate',
            'status' => $result['status'] ?? 'success',
            'credits_used' => 1,
            'latency_ms' => 150,
        ]);

        return response()->json([
            'data' => $result,
        ]);
    }
}
