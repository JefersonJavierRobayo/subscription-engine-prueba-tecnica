<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController
{
    public function index() { return Customer::with('subscriptions')->latest()->get(); }

    public function store(Request $request) {
        $data = $request->validate([
            'name'=>'required|string|max:255',
            'email'=>'required|email|max:255|unique:customers,email',
            'document'=>'required|string|max:50',
            'phone'=>'required|string|max:50'
        ]);
        return response()->json(Customer::create($data), 201);
    }

    public function show(Customer $customer) { return $customer->load('subscriptions'); }

    public function update(Request $request, Customer $customer) {
        $data = $request->validate([
            'name'=>'required|string|max:255',
            'email'=>['required','email','max:255',Rule::unique('customers','email')->ignore($customer->id)],
            'document'=>'required|string|max:50',
            'phone'=>'required|string|max:50'
        ]);
        $customer->update($data);
        return $customer->fresh('subscriptions');
    }

    public function destroy(Customer $customer) {
        $customer->delete();
        return response()->json(['message'=>'Cliente eliminado correctamente.']);
    }
}