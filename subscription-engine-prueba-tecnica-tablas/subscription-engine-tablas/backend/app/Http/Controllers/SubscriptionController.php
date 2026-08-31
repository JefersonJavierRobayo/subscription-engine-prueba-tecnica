<?php
namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController
{
    public function index(Request $request) {
        $query = Subscription::with('customer')->latest();
        if ($request->filled('status')) $query->where('status', $request->status);
        return $query->get();
    }

    public function store(Request $request) {
        $data = $request->validate([
            'customer_id'=>'required|integer|exists:customers,id',
            'name'=>'required|string|max:255',
            'description'=>'nullable|string',
            'price'=>'required|numeric|min:0',
            'periodicity'=>['required',Rule::in(['monthly','yearly'])],
            'status'=>['nullable',Rule::in(['active','paused','canceled'])],
            'last_billed_at'=>'nullable|date',
            'next_billing_at'=>'nullable|date'
        ]);
        $data['status'] = $data['status'] ?? 'active';
        return response()->json(Subscription::create($data)->load('customer'), 201);
    }

    public function show(Subscription $subscription) {
        return $subscription->load(['customer','paymentAttempts'=>fn($q)=>$q->latest()]);
    }

    public function update(Request $request, Subscription $subscription) {
        $data = $request->validate([
            'customer_id'=>'required|integer|exists:customers,id',
            'name'=>'required|string|max:255',
            'description'=>'nullable|string',
            'price'=>'required|numeric|min:0',
            'periodicity'=>['required',Rule::in(['monthly','yearly'])],
            'status'=>['required',Rule::in(['active','paused','canceled'])],
            'last_billed_at'=>'nullable|date',
            'next_billing_at'=>'nullable|date'
        ]);
        $subscription->update($data);
        return $subscription->fresh('customer');
    }

    public function updateStatus(Request $request, Subscription $subscription) {
        $data = $request->validate(['status'=>['required',Rule::in(['active','paused','canceled'])]]);
        $subscription->update($data);
        return $subscription->fresh('customer');
    }

    public function destroy(Subscription $subscription) {
        $subscription->delete();
        return response()->json(['message'=>'Suscripción eliminada correctamente.']);
    }
}