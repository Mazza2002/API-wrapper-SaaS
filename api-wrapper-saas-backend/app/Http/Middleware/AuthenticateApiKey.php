<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization');

        if (! is_string($header) || ! preg_match('/^Bearer\s+sk_[A-Za-z0-9_.-]+$/', $header)) {
            return response()->json([
                'error' => 'API key missing or invalid.',
            ], 401);
        }

        $token = trim(str_replace('Bearer ', '', $header));

        $apiKey = ApiKey::query()
            ->with('user')
            ->where('is_active', true)
            ->get()
            ->first(function (ApiKey $key) use ($token) {
                return Hash::check($token, $key->key_hash);
            });

        if (! $apiKey || ! $apiKey->user) {
            return response()->json([
                'error' => 'Invalid API key.',
            ], 401);
        }

        $request->setUserResolver(fn () => $apiKey->user);
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
