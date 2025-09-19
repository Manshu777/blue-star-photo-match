@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8 sm:py-12">
        <h1 class="text-3xl sm:text-4xl font-bold mb-8 sm:mb-10 text-center text-gray-800">Checkout</h1>

        <form action="{{ route('checkout.store') }}" method="POST" class="space-y-8 sm:space-y-10">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-10">
                <!-- Billing Information -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg">
                    <h2 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 text-gray-800">Billing Information</h2>

                    <div class="space-y-4 sm:space-y-5">
                        <div>
                            <label for="billing_name" class="block text-sm font-medium text-gray-600">Full Name</label>
                            <input type="text" id="billing_name" name="billing_name"
                                class="w-full border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label for="billing_email" class="block text-sm font-medium text-gray-600">Email</label>
                            <input type="email" id="billing_email" name="billing_email"
                                class="w-full border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label for="billing_address" class="block text-sm font-medium text-gray-600">Address</label>
                            <textarea id="billing_address" name="billing_address" rows="3"
                                class="w-full border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="billing_city" class="block text-sm font-medium text-gray-600">City</label>
                                <input type="text" id="billing_city" name="billing_city"
                                    class="w-full border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="billing_state" class="block text-sm font-medium text-gray-600">State</label>
                                <input type="text" id="billing_state" name="billing_state"
                                    class="w-full border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="billing_zip" class="block text-sm font-medium text-gray-600">ZIP Code</label>
                                <input type="text" id="billing_zip" name="billing_zip"
                                    class="w-full border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="billing_phone" class="block text-sm font-medium text-gray-600">Phone</label>
                                <input type="text" id="billing_phone" name="billing_phone"
                                    class="w-full border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg">
                    <h2 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 text-gray-800">Order Summary</h2>

                    <div class="divide-y divide-gray-200">
                        @foreach ($cartItems as $item)
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between py-3 sm:py-4">
                                <div class="mb-2 sm:mb-0">
                                    <p class="font-medium text-gray-800 text-sm sm:text-base">{{ $item->product->name }}</p>
                                    <p class="text-xs sm:text-sm text-gray-500">Qty: {{ $item->quantity }}</p>
                                </div>
                                <span class="font-semibold text-gray-700 text-sm sm:text-base">
                                    ${{ number_format($item->price * $item->quantity, 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <hr class="my-4 sm:my-6">

                    <div class="flex justify-between text-base sm:text-lg font-semibold text-gray-900">
                        <span>Total</span>
                        <span>${{ number_format($total, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Payment Section -->
            <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-lg">
                <h2 class="text-xl sm:text-2xl font-semibold mb-4 sm:mb-6 text-gray-800">Payment Information</h2>

                <!-- Stripe Elements -->
                <div id="card-element" class="border border-gray-300 rounded-lg px-3 sm:px-4 py-2 sm:py-3 mb-4 sm:mb-6 shadow-sm"></div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-base sm:text-lg font-semibold px-4 sm:px-6 py-3 sm:py-4 rounded-xl shadow-lg hover:from-blue-700 hover:to-indigo-700 transition transform hover:-translate-y-0.5">
                    Pay Securely Now
                </button>
            </div>
        </form>

        <!-- Stripe JS -->
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            const stripe = Stripe('{{ env('STRIPE_KEY') }}');
            const elements = stripe.elements();
            const card = elements.create('card', { style: { base: { fontSize: '16px', color: '#32325d' } } });
            card.mount('#card-element');
        </script>
    </div>
@endsection
