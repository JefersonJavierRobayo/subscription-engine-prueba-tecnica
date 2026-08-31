<?php
namespace App\Http\Controllers;

use App\Models\PaymentAttempt;
use App\Services\GatewaySimulatorService;
use Illuminate\Http\Request;

class GatewayController
{
    public function charge(Request $request, GatewaySimulatorService $gateway)
    {
        $data = $request->validate([
            'payment_attempt_id'=>'required|integer|exists:payment_attempts,id',
            'result'=>'nullable|in:approved,failed,timeout,random'
        ]);
        return response()->json($gateway->charge((int)$data['payment_attempt_id'],$data['result'] ?? null));
    }

    public function webhook(Request $request)
    {
        $data = $request->validate([
            'payment_attempt_id'=>'required|integer|exists:payment_attempts,id',
            'result'=>'required|in:approved,failed',
            'gateway_reference'=>'nullable|string|max:100'
        ]);

        $attempt = PaymentAttempt::with('subscription')->findOrFail($data['payment_attempt_id']);

        if ($attempt->status !== 'pending') {
            return response()->json(['message'=>'El intento ya fue procesado.','attempt'=>$attempt]);
        }

        $subscription = $attempt->subscription;

        if ($data['result'] === 'approved') {
            $attempt->update([
                'status'=>'successful',
                'next_retry_at'=>null,
                'response_payload'=>$data
            ]);

            $next = $subscription->periodicity === 'yearly'
                ? now()->addYear()
                : now()->addMonth();

            $subscription->update([
                'last_billed_at'=>now(),
                'next_billing_at'=>$next
            ]);
        } else {
            $nextRetry = $attempt->retry_count < 3 ? now()->addDay() : null;

            $attempt->update([
                'status'=>'failed',
                'next_retry_at'=>$nextRetry,
                'response_payload'=>$data
            ]);

            if ($attempt->retry_count >= 3) $subscription->update(['status'=>'paused']);
        }

        return response()->json([
            'message'=>'Webhook procesado correctamente.',
            'attempt'=>$attempt->fresh(),
            'subscription'=>$subscription->fresh()
        ]);
    }
}