<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ExternalApiClient
{
    public function generate(string $prompt): array
    {
        try {
            $response = Http::timeout(5)->post('https://httpbin.org/post', [
                'prompt' => $prompt,
                'provider' => 'demo-httpbin',
            ]);

            if ($response->failed()) {
                return [
                    'provider' => 'demo-httpbin',
                    'status' => 'failed',
                    'result' => $response->json('json') ?? ['error' => 'External provider request failed.'],
                ];
            }

            return [
                'provider' => 'demo-httpbin',
                'status' => 'success',
                'result' => $response->json('json') ?? ['prompt' => $prompt],
            ];
        } catch (ConnectionException $e) {
            return [
                'provider' => 'demo-httpbin',
                'status' => 'degraded',
                'result' => [
                    'prompt' => $prompt,
                    'warning' => 'Upstream provider unavailable; returning placeholder response.',
                ],
            ];
        }
    }
}
