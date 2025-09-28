<!-- resources/views/components/header.blade.php -->
<nav x-data="{ menuOpen: false }" class="bg-gradient-to-br from-blue-600  to-blue-500 absolute w-full z-50">
    <div class="container mx-auto flex justify-between items-center py-4 px-4 lg:px-8">
        <!-- Logo -->
        <a href="{{ route('home') }}" class="flex items-center space-x-3">
            <div
                class="w-12 h-12 bg-gradient-to-b rounded-xl flex items-center justify-center shadow-lg">
               <x-heroicon-s-star class="w-9 h-9 text-white" />


            </div>
            <div>
                <h1
                    class="text-2xl font-bold text-white text-transparent">
                    Blue Star Memory
                </h1>
                <p class="text-sm text-white font-medium">AI Photo Organization</p>
            </div>
        </a>

        <!-- Desktop Menu -->
        <div class="hidden lg:flex items-center space-x-6">
            <a href="{{ route('home') }}" class="text-white font-semibold hover:text-blue-600">Home</a>
            <a href="/product" class="text-white font-semibold hover:text-blue-600">Products</a>
            <a href="{{ route('about') }}" class="text-white font-semibold hover:text-blue-600">About</a>
            <a href="{{ route('pricing') }}" class="text-white font-semibold hover:text-blue-600">Pricing</a>
            <a href="{{ route('contact') }}" class="text-white font-semibold hover:text-blue-600">Contact</a>

            @auth
                <a href="{{ route('upload') }}" class="text-white font-semibold hover:text-blue-600">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-white font-semibold hover:text-blue-600">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-white font-semibold hover:text-blue-600">Login</a>
                <a href="{{ route('signup') }}"
                    class="px-4 py-2 rounded-lg bg-blue-800 text-white shadow hover:opacity-90">Sign
                    Up</a>
            @endauth
        </div>

        <!-- Mobile Toggle -->
        <button @click="menuOpen = !menuOpen" class="lg:hidden focus:outline-none">
            <svg x-show="!menuOpen" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-gray-700" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg x-show="menuOpen" xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-gray-700" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div x-show="menuOpen" x-transition class="lg:hidden bg-white shadow-md px-4 pb-4 space-y-3">
        <a href="{{ route('home') }}" class="block text-white font-semibold hover:text-blue-600">Home</a>
        <a href="/product" class="text-white font-semibold hover:text-blue-600">Products</a>
        <a href="{{ route('about') }}" class="block text-white font-semibold hover:text-blue-600">About</a>
        <a href="{{ route('pricing') }}" class="block text-white font-semibold hover:text-blue-600">Pricing</a>
        <a href="{{ route('contact') }}" class="block text-white font-semibold hover:text-blue-600">Contact</a>

        @auth
            <a href="{{ route('upload') }}" class="block text-white font-semibold hover:text-blue-600">Dashboard</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full text-left text-white font-semibold hover:text-blue-600">Logout</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="block text-white font-semibold hover:text-blue-600">Login</a>
            <a href="{{ route('signup') }}"
                class="block px-4 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow hover:opacity-90">Sign
                Up</a>
        @endauth
    </div>
</nav>