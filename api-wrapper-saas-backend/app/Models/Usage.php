<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Usage extends Model
{
    /** @use HasFactory<\Database\Factories\UsageFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'api_key_id',
        'endpoint',
        'status',
        'credits_used',
        'latency_ms',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }
}
