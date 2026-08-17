<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $plan = Plan::findOrFail($request->plan_id);

        Stripe::setApiKey(config('services.stripe.secret'));

        $customer = \Stripe\Customer::create([
            'email' => $request->user()->email,
            'name' => $request->user()->name,
        ]);

        $subscription = \Stripe\Subscription::create([
            'customer' => $customer->id,
            'items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => ['name' => $plan->name],
                    'unit_amount' => (int) round($plan->price * 100),
                    'recurring' => ['interval' => 'month'],
                ],
            ]],
        ]);

        Subscription::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'plan_id' => $plan->id,
                'stripe_customer_id' => $customer->id,
                'stripe_subscription_id' => $subscription->id,
                'status' => $subscription->status,
            ]
        );

        return response()->json([
            'data' => [
                'customer_id' => $customer->id,
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
            ],
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid webhook signature.'], 400);
        }

        if ($event->type === 'customer.subscription.updated' || $event->type === 'customer.subscription.deleted') {
            $subscription = $event->data->object;
            $record = Subscription::query()->where('stripe_subscription_id', $subscription->id)->first();

            if ($record) {
                $record->update(['status' => $subscription->status]);

                $record->user()->update([
                    'plan_id' => $record->plan_id,
                ]);
            }
        }

        return response()->json(['data' => ['received' => true]]);
    }
}
