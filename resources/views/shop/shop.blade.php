@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-lg">
    <h1 class="text-3xl sm:text-4xl font-extrabold mb-8 text-center text-gray-800 tracking-tight">
        Discover Our Shop
    </h1>

    <!-- Filters and Search -->
    <div class="mb-10 bg-white p-6 rounded-lg shadow-md">
        <form action="{{ route('shop.index') }}" method="GET" 
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            
            <!-- Type -->
            <select name="type"
                class="border border-gray-300 rounded-md px-4 py-2 sm:py-3 bg-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Types</option>
                <option value="photo" {{ request('type') == 'photo' ? 'selected' : '' }}>Photos</option>
                <option value="video" {{ request('type') == 'video' ? 'selected' : '' }}>Videos</option>
                <option value="merchandise" {{ request('type') == 'merchandise' ? 'selected' : '' }}>Merchandise</option>
            </select>

            <!-- Category -->
            <select name="category"
                class="border border-gray-300 rounded-md px-4 py-2 sm:py-3 bg-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <!-- Sort -->
            <select name="sort"
                class="border border-gray-300 rounded-md px-4 py-2 sm:py-3 bg-white shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Sort By</option>
                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Name: A to Z</option>
            </select>

            <!-- Search -->
            <input type="text" name="search" placeholder="Search products..." value="{{ request('search') }}"
                class="border border-gray-300 rounded-md px-4 py-2 sm:py-3 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:col-span-2 lg:col-span-1">

            <!-- Button -->
            <button type="submit"
                class="bg-blue-600 text-white px-6 py-3 rounded-md shadow hover:bg-blue-700 transition duration-200">
                Apply Filters
            </button>
        </form>
    </div>

    <!-- Product Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse ($products as $product)
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                <!-- Product Preview -->
                <div class="relative">
                    @if ($product->type === 'photo' || $product->type === 'merchandise')
                        <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                            class="w-full h-48 sm:h-56 object-cover">
                    @elseif ($product->type === 'video')
                        <video src="{{ $product->preview_url }}" class="w-full h-48 sm:h-56 object-cover" controls muted loop></video>
                    @endif
                    <span class="absolute top-2 right-2 bg-blue-500 text-white text-xs px-2 py-1 rounded-full">
                        {{ ucfirst($product->type) }}
                    </span>
                </div>

                <!-- Product Details -->
                <div class="p-5 flex flex-col justify-between h-full">
                    <div>
                        <h2 class="text-lg sm:text-xl font-semibold mb-2 text-gray-800 line-clamp-1">{{ $product->name }}</h2>
                        <p class="text-gray-600 mb-4 text-sm sm:text-base line-clamp-2">{{ $product->description }}</p>
                    </div>
                    <div class="flex justify-between items-center mt-auto">
                        <span class="text-base sm:text-lg font-bold text-green-600">${{ number_format($product->price, 2) }}</span>
                        <a href="{{ route('products.show', $product->slug) }}"
                           class="bg-green-500 text-white px-3 sm:px-4 py-2 rounded-md hover:bg-green-600 transition duration-200 text-sm sm:text-base">
                           View Details
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center text-gray-500 col-span-full">No products found.</p>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-10 flex justify-center">
        {{ $products->links('pagination::tailwind') }}
    </div>

    <!-- Featured Products -->
    <div class="mt-12">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Featured Products</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            @foreach ($featuredProducts ?? [] as $featured)
                <div class="bg-white rounded-xl shadow-md p-4">
                    <img src="{{ Storage::url($featured->image) }}" alt="{{ $featured->name }}"
                        class="w-full h-40 object-cover rounded mb-4">
                    <h3 class="text-lg font-semibold line-clamp-1">{{ $featured->name }}</h3>
                    <p class="text-sm text-gray-600 mb-2 line-clamp-2">{{ $featured->description }}</p>
                    <a href="{{ route('products.show', $featured->slug) }}" class="text-blue-500 hover:underline text-sm">View</a>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
