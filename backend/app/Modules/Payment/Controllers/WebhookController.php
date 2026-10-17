<?php

namespace App\Modules\Payment\Controllers;

use App\Modules\Payment\Services\StripeService;
use App\Modules\Payment\Services\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController
{
    /**
     * POST /api/v1/webhooks/stripe — Webhook Stripe.
     */
    public function stripe(Request $request, StripeService $stripeService): JsonResponse
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');

        try {
            $event = $stripeService->verifyWebhookSignature($payload, $sigHeader);
        } catch (\RuntimeException $e) {
            Log::warning('Stripe webhook signature invalid', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Signature invalide.'], 400);
        }

        $type = $event['type'] ?? '';
        $data = $event['data']['object'] ?? [];

        match ($type) {
            'checkout.session.completed' => $stripeService->handleCheckoutCompleted($data),
            default => Log::info("Stripe webhook ignoré : {$type}"),
        };

        return response()->json(['received' => true]);
    }

    /**
     * POST /api/v1/webhooks/paypal — Webhook PayPal.
     */
    public function paypal(Request $request, PayPalService $paypalService): JsonResponse
    {
        $event     = $request->all();
        $eventType = $event['event_type'] ?? '';
        $resource  = $event['resource'] ?? [];

        Log::info('PayPal webhook received', ['type' => $eventType]);

        match ($eventType) {
            'CHECKOUT.ORDER.APPROVED'   => $paypalService->handleOrderCaptured($resource),
            'PAYMENT.CAPTURE.COMPLETED' => Log::info('PayPal capture completed', $resource),
            default => Log::info("PayPal webhook ignoré : {$eventType}"),
        };

        return response()->json(['received' => true]);
    }
}
