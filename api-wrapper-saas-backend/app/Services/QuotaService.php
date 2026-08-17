<?php

namespace App\Services;

use App\Models\Usage;
use App\Models\User;
use Carbon\Carbon;

class QuotaService
{
    public function ensureUserCanGenerate(User $user): void
    {
        $plan = $user->plan()->first() ?? $user->getPlan();

        $monthlyUsage = Usage::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('credits_used');

        if ($monthlyUsage >= $plan->monthly_credit_quota) {
            abort(response()->json([
                'error' => [
                    'message' => 'Monthly quota exceeded for this plan.',
                    'quota_remaining' => 0,
                ],
            ], 429));
        }
    }

    public function usageRemaining(User $user): int
    {
        $plan = $user->plan()->first() ?? $user->getPlan();

        $monthlyUsage = Usage::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->sum('credits_used');

        return max(0, $plan->monthly_credit_quota - (int) $monthlyUsage);
    }
}
