<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApiKeyRequest;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function index(): JsonResponse
    {
        $keys = auth()->user()->apiKeys()->latest()->get();

        return response()->json([
            'data' => $keys->map(fn (ApiKey $key) => [
                'id' => $key->id,
                'name' => $key->name,
                'prefix' => $key->prefix,
                'last_used_at' => $key->last_used_at,
                'is_active' => $key->is_active,
                'created_at' => $key->created_at,
            ]),
        ]);
    }

    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        $plainKey = 'sk_' . Str::random(32);

        $apiKey = ApiKey::create([
            'user_id' => $request->user()->id,
            'name' => $request->string('name')->trim(),
            'key_hash' => Hash::make($plainKey),
            'prefix' => 'sk_',
            'is_active' => true,
        ]);

        return response()->json([
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'prefix' => $apiKey->prefix,
                'key' => $plainKey,
                'created_at' => $apiKey->created_at,
            ],
        ], 201);
    }

    public function destroy(ApiKey $apiKey): JsonResponse
    {
        abort_unless($apiKey->user_id === auth()->id(), 403, 'Unauthorized.');

        $apiKey->update(['is_active' => false]);

        return response()->json([
            'data' => [
                'message' => 'API key revoked successfully.',
            ],
        ]);
    }
}
