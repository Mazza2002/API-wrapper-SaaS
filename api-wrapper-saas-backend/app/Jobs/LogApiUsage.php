<?php

namespace App\Jobs;

use App\Models\Usage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class LogApiUsage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public ?int $apiKeyId,
        public string $endpoint,
        public string $status,
        public int $creditsUsed,
        public int $latencyMs,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Usage::create([
            'user_id' => $this->userId,
            'api_key_id' => $this->apiKeyId,
            'endpoint' => $this->endpoint,
            'status' => $this->status,
            'credits_used' => $this->creditsUsed,
            'latency_ms' => $this->latencyMs,
        ]);
    }
}
