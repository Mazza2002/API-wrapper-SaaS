<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateRequest;
use App\Jobs\LogApiUsage;
use App\Services\ExternalApiClient;
use App\Services\QuotaService;
use App\Services\RateLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;

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

        $startedAt = microtime(true);
        $result = $this->externalApiClient->generate($request->input('prompt'));
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        dispatch(new LogApiUsage(
            userId: $user->id,
            apiKeyId: $request->attributes->get('api_key')?->id,
            endpoint: '/api/v1/generate',
            status: $result['status'] ?? 'success',
            creditsUsed: 1,
            latencyMs: $durationMs,
        ));

        return response()->json([
            'data' => $result,
        ]);
    }
}
