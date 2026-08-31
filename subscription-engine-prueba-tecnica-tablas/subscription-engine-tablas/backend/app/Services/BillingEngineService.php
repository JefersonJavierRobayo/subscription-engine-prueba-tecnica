<?php
namespace App\Services;

use App\Models\PaymentAttempt;
use App\Models\Subscription;
use Illuminate\Support\Carbon;

class BillingEngineService
{
    public function run(?string $forcedResult = null): array
    {
        $now = Carbon::now();
        $processed = 0;
        $skipped = 0;

        Subscription::where('status','active')->orderBy('id')->each(function($subscription) use ($now,$forcedResult,&$processed,&$skipped) {
            if (!$this->isDue($subscription,$now)) { $skipped++; return; }

            $pending = $subscription->paymentAttempts()->where('status','pending')->latest()->first();
            if ($pending) { $skipped++; return; }

            $latest = $subscription->paymentAttempts()->latest()->first();

            if ($latest && $latest->status === 'failed') {
                if ($latest->retry_count >= 3) {
                    $subscription->update(['status'=>'paused']);
                    $skipped++;
                    return;
                }
                if ($latest->next_retry_at && $latest->next_retry_at->isFuture()) {
                    $skipped++;
                    return;
                }
            }

            $retry = ($latest && $latest->status === 'failed') ? $latest->retry_count + 1 : 0;

            $attempt = PaymentAttempt::create([
                'subscription_id'=>$subscription->id,
                'status'=>'pending',
                'retry_count'=>$retry,
                'next_retry_at'=>null,
                'response_payload'=>null
            ]);

            $processed++;
            app(GatewaySimulatorService::class)->charge($attempt->id,$forcedResult);
        });

        return ['processed'=>$processed,'skipped'=>$skipped,'executed_at'=>$now->toIso8601String()];
    }

    private function isDue(Subscription $subscription, Carbon $now): bool
    {
        if ($subscription->next_billing_at) return $subscription->next_billing_at->lessThanOrEqualTo($now);
        if (!$subscription->last_billed_at) return true;

        $next = $subscription->periodicity === 'yearly'
            ? $subscription->last_billed_at->copy()->addYear()
            : $subscription->last_billed_at->copy()->addMonth();

        return $next->lessThanOrEqualTo($now);
    }
}