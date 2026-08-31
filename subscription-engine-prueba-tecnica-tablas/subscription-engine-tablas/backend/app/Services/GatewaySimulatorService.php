<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class GatewaySimulatorService
{
    public function charge(int $paymentAttemptId, ?string $forcedResult = null): array
    {
        $result = $forcedResult ?: env('GATEWAY_RESULT', 'random');

        if ($result === 'random') {
            $n = random_int(1, 100);
            $result = $n <= 60 ? 'approved' : ($n <= 90 ? 'failed' : 'timeout');
        }

        if ($result === 'timeout') {
            return ['status'=>'timeout','webhook_sent'=>false];
        }

        $payload = [
            'payment_attempt_id'=>$paymentAttemptId,
            'result'=>$result,
            'gateway_reference'=>'SIM-'.strtoupper(bin2hex(random_bytes(5)))
        ];

        $url = env('GATEWAY_WEBHOOK_URL');
        if ($url) Http::timeout(5)->post($url, $payload);

        return ['status'=>$result,'webhook_sent'=>(bool)$url,'payload'=>$payload];
    }
}