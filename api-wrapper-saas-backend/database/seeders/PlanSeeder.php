<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::query()->updateOrCreate(
            ['name' => 'Free'],
            [
                'monthly_credit_quota' => 100,
                'rate_limit_per_minute' => 10,
                'price' => 0,
            ]
        );

        Plan::query()->updateOrCreate(
            ['name' => 'Pro'],
            [
                'monthly_credit_quota' => 10000,
                'rate_limit_per_minute' => 60,
                'price' => 29,
            ]
        );
    }
}
