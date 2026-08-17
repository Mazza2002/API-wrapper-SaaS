<?php

namespace App\Services;

use App\Models\ApiKey;
use Illuminate\Support\Facades\Cache;

class RateLimitService
{
    public function check(ApiKey $apiKey): void
    {
        $plan = $apiKey->user->plan()->first() ?? $apiKey->user->getPlan();
        $limit = max(1, $plan->rate_limit_per_minute);
        $key = 'api-rate-limit:' . $apiKey->id;

        $count = Cache::store('array')->get($key, 0);

        if ($count >= $limit) {
            abort(response()->json([
                'error' => [
                    'message' => 'Rate limit exceeded. Please try again later.',
                ],
            ], 429)->header('Retry-After', 60));
        }

        Cache::store('array')->put($key, $count + 1, 60);
    }
}
