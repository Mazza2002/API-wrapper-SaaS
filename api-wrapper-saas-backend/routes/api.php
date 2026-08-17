<?php

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GenerateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/v1/keys', [ApiKeyController::class, 'index']);
    Route::post('/v1/keys', [ApiKeyController::class, 'store']);
    Route::delete('/v1/keys/{apiKey}', [ApiKeyController::class, 'destroy']);
});

Route::middleware(['api.key'])->group(function () {
    Route::post('/v1/generate', GenerateController::class);
});

Route::get('/health', function (Request $request) {
    return response()->json([
        'data' => ['status' => 'ok'],
    ]);
});
