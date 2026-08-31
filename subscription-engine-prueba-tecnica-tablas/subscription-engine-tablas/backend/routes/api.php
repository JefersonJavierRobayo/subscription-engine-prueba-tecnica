<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\GatewayController;

Route::apiResource('customers', CustomerController::class);
Route::apiResource('subscriptions', SubscriptionController::class)->except(['create','edit']);
Route::patch('subscriptions/{subscription}/status', [SubscriptionController::class,'updateStatus']);

Route::get('subscriptions/{subscription}/payment-attempts', function ($subscription) {
    return \App\Models\Subscription::findOrFail($subscription)
        ->paymentAttempts()->latest()->get();
});

Route::post('billing/run', [BillingController::class,'run']);
Route::post('gateway/charge', [GatewayController::class,'charge']);
Route::post('webhooks/gateway', [GatewayController::class,'webhook']);
