@extends('layouts.app')

@section('title', 'Home - Blue Star Memory')

@section('content') 

<h2 class="font-semibold text-xl sm:text-2xl text-gray-800 leading-tight mb-6 text-center sm:text-left">
    Merchandise Catalog
</h2>

<div class="py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                
                @if ($merchandise->isEmpty())
                    <p class="text-gray-500">No merchandise available.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($merchandise as $item)
                            <div class="border rounded-lg overflow-hidden shadow-md hover:shadow-lg transition">
                                <!-- Image -->
                                <img src="{{ asset($item->image_path ?? 'images/placeholder.jpg') }}" 
                                     alt="{{ $item->name }}" 
                                     class="w-full h-48 sm:h-56 object-cover">

                                <!-- Card Content -->
                                <div class="p-4 flex flex-col">
                                    <h4 class="text-lg font-semibold truncate">{{ $item->name }}</h4>
                                    <p class="text-gray-600 font-medium">${{ number_format($item->price, 2) }}</p>
                                    <p class="text-gray-500 text-sm mt-1 line-clamp-2">{{ $item->description }}</p>
                                    <p class="text-gray-500 text-sm mt-1">In Stock: {{ $item->stock }}</p>
                                    
                                    <!-- Form -->
                                    <form action="{{ route('store.purchase_merchandise') }}" method="POST" class="mt-3 space-y-3">
                                        @csrf
                                        <input type="hidden" name="merchandise_id" value="{{ $item->id }}">

                                        <div>
                                            <label for="quantity-{{ $item->id }}" class="block text-sm font-medium text-gray-700">Quantity</label>
                                            <input type="number" 
                                                   name="quantity" 
                                                   id="quantity-{{ $item->id }}" 
                                                   value="1" 
                                                   min="1" 
                                                   max="{{ $item->stock }}" 
                                                   class="w-24 p-2 border rounded focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <div>
                                            <label for="shipping_address-{{ $item->id }}" class="block text-sm font-medium text-gray-700">Shipping Address</label>
                                            <input type="text" 
                                                   name="shipping_address" 
                                                   id="shipping_address-{{ $item->id }}" 
                                                   placeholder="Enter shipping address" 
                                                   class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <div>
                                            <label for="payment_method-{{ $item->id }}" class="block text-sm font-medium text-gray-700">Payment Method</label>
                                            <select name="payment_method" 
                                                    id="payment_method-{{ $item->id }}" 
                                                    class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                                                <option value="card">Credit Card</option>
                                                <option value="paypal">PayPal</option>
                                            </select>
                                        </div>

                                        <button type="submit" 
                                                class="w-full sm:w-auto bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 text-sm sm:text-base">
                                            Purchase
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="mt-8">
                        {{ $merchandise->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
