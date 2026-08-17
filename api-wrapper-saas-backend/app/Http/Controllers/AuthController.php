<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $plan = \App\Models\Plan::query()->firstOrCreate(
            ['name' => 'Free'],
            [
                'monthly_credit_quota' => 100,
                'rate_limit_per_minute' => 10,
                'price' => 0,
            ]
        );

        $user = User::create([
            'name' => $request->string('name')->trim(),
            'email' => $request->string('email')->trim(),
            'password' => $request->string('password'),
            'plan_id' => $plan->id,
        ]);

        $token = $user->createToken('dashboard')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ], 201);
    }

    public function login(LoginUserRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Invalid credentials.',
            ], 401);
        }

        $token = $user->createToken('dashboard')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        $user = Auth::user();
        if ($user) {
            $user->currentAccessToken()?->delete();
        }

        return response()->json([
            'data' => [
                'message' => 'Logged out successfully.',
            ],
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'data' => Auth::user(),
        ]);
    }
}
