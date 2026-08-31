<?php

namespace App\Http\Controllers;

use App\Services\BillingEngineService;
use Illuminate\Http\Request;

class BillingController
{
    public function run(Request $request, BillingEngineService $engine)
    {
        $data = $request->validate([
            'result' => 'nullable|string|in:approved,failed,timeout,random',
        ]);

        $result = $data['result'] ?? 'random';

        return response()->json($engine->run($result));
    }
}