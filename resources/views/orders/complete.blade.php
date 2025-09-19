@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6 sm:py-8 text-center">
    <h1 class="text-2xl sm:text-3xl font-bold mb-4 sm:mb-6">Order Complete!</h1>
    <p class="text-base sm:text-lg mb-6 sm:mb-8">
        Thank you for your purchase. Your order #{{ $order->id }} has been placed successfully.
    </p>

    <!-- Order Details -->
    <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md mb-6 sm:mb-8 text-left">
        <h2 class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4">Order Summary</h2>
        <div class="space-y-2">
            @foreach ($order->items as $item)
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center text-sm sm:text-base">
                    <span class="mb-1 sm:mb-0 font-medium text-gray-700">{{ $item->product->name }} × {{ $item->quantity }}</span>
                    <span class="font-semibold text-gray-800">${{ number_format($item->price * $item->quantity, 2) }}</span>
                </div>
            @endforeach
        </div>
        <hr class="my-3 sm:my-4">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center text-lg sm:text-xl font-bold">
            <span>Total</span>
            <span>${{ number_format($order->total, 2) }}</span>
        </div>
    </div>

    <!-- Download Links for Digital Products -->
    @if ($order->items->contains(function ($item) { return in_array($item->product->type, ['photo', 'video']); }))
        <div class="mb-6 sm:mb-8 text-left">
            <h2 class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4">Downloads</h2>
            <div class="space-y-2">
                @foreach ($order->items as $item)
                    @if (in_array($item->product->type, ['photo', 'video']))
                        <a href="{{ route('downloads.product', $item->product->id) }}"
                           class="block text-blue-600 hover:underline text-sm sm:text-base">
                           Download {{ $item->product->name }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <!-- Shipping Info for Physical -->
    @if ($order->items->contains(function ($item) { return $item->product->type === 'merchandise'; }))
        <div class="mb-6 sm:mb-8 text-left">
            <h2 class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4">Shipping Information</h2>
            <p class="text-sm sm:text-base text-gray-700">
                Your items will be shipped to:
                <span class="font-medium text-gray-900">
                    {{ $order->shipping_address['address'] ?? $order->billing_address['address'] }}
                </span>
            </p>
            <!-- Tracking integration goes here -->
        </div>
    @endif

    <a href="{{ route('shop.index') }}"
       class="inline-block bg-blue-600 text-white text-sm sm:text-base px-5 sm:px-6 py-2.5 sm:py-3 rounded-lg shadow hover:bg-blue-700 transition">
       Continue Shopping
    </a>
</div>
@endsection
