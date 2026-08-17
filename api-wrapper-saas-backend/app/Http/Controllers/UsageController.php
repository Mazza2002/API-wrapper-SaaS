<?php

namespace App\Http\Controllers;

use App\Models\Usage;
use Illuminate\Http\JsonResponse;

class UsageController extends Controller
{
    public function index(): JsonResponse
    {
        $usage = Usage::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(30)
            ->get();

        return response()->json([
            'data' => [
                'total_requests' => $usage->count(),
                'total_credits_used' => $usage->sum('credits_used'),
                'usage' => $usage,
            ],
        ]);
    }
}
