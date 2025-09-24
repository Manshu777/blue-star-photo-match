<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>User Panel Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/exif-js"></script>
    <script src="https://cdn.jsdelivr.net/npm/video.js@7/dist/video.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/video.js@7/dist/video-js.min.css" rel="stylesheet">
    <style>
        .drag-drop-zone {
            border: 2px dashed #ccc;
            transition: border-color 0.3s ease, background-color 0.3s ease;
        }

        .drag-drop-zone.dragover {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .preview-canvas,
        .preview-video {
            max-height: 400px;
            width: 100%;
            object-fit: contain;
            border-radius: 0.5rem;
        }

        .edit-tools button {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .pro-badge {
            background-color: #3b82f6;
            color: #fff;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }

        html.dark .bg-gray-50 {
            background-color: #1f2937;
        }

        html.dark .text-gray-800 {
            color: #f3f4f6;
        }

        html.dark .bg-white {
            background-color: #374151;
        }

        html.dark .text-gray-700 {
            color: #d1d5db;
        }

        html.dark .bg-gray-200 {
            background-color: #4b5563;
        }

        html.dark .text-gray-500 {
            color: #9ca3af;
        }

        html.dark .border-gray-300 {
            border-color: #4b5563;
        }

        html.dark .bg-blue-100 {
            background-color: #1e3a8a;
        }

        html.dark .text-blue-700 {
            color: #60a5fa;
        }

        html.dark .pro-badge {
            background-color: #1e40af;
            color: #fff;
        }

        .sidebar {
            transition: width 0.3s ease-in-out;
        }

        .overlay {
            transition: opacity 0.3s ease-in-out;
        }

        .form-input {
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .gallery-button {
            transition: opacity 0.3s ease;
        }

        .group:hover .gallery-button {
            opacity: 100;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen font-sans antialiased" x-data="{
    tab: localStorage.getItem('activeTab') || 'dashboard',
    sidebarOpen: false,
    isMobile: window.innerWidth < 768,
    darkMode: false,
    selectedPhoto: null,
    albums: {}
}" x-init="$watch('tab', value => localStorage.setItem('activeTab', value))">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Overlay for Mobile -->
        <div x-show="isMobile && sidebarOpen" class="overlay fixed inset-0 bg-black bg-opacity-50 z-30"
            @click="sidebarOpen = false"></div>
        <!-- Sidebar -->
        <aside class="sidebar bg-white dark:bg-gray-800 shadow-lg flex flex-col overflow-hidden"
            :class="{ 'w-64': sidebarOpen, 'w-0': !sidebarOpen, 'fixed h-full z-40': isMobile, 'relative': !isMobile }">
            <div class="flex items-center justify-between p-2 border-b dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">User Dashboard</h2>
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-600 dark:text-gray-300 md:hidden">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="flex-grow overflow-y-auto p-2">
                <ul class="space-y-2">
                    <li>
                        <button @click="tab = 'dashboard'; if(isMobile) sidebarOpen = false"
                            class="w-full text-left px-4 py-2 rounded-lg hover:bg-blue-100 dark:hover:bg-gray-600 transition"
                            :class="{ 'bg-blue-600 text-white': tab === 'dashboard', 'text-gray-800 dark:text-white': tab !== 'dashboard' }">
                            Dashboard
                        </button>
                    </li>
                    <li>
                        <button @click="tab = 'upload'; if(isMobile) sidebarOpen = false"
                            class="w-full text-left px-4 py-2 rounded-lg hover:bg-blue-100 dark:hover:bg-gray-600 transition"
                            :class="{ 'bg-blue-600 text-white': tab === 'upload', 'text-gray-800 dark:text-white': tab !== 'upload' }">
                            Upload Media
                        </button>
                    </li>
                    <li>
                        <button @click="tab = 'album'; if(isMobile) sidebarOpen = false"
                            class="w-full text-left px-4 py-2 rounded-lg hover:bg-blue-100 dark:hover:bg-gray-600 transition"
                            :class="{ 'bg-blue-600 text-white': tab === 'album', 'text-gray-800 dark:text-white': tab !== 'album' }">
                            Photos Album
                        </button>
                    </li>


                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="w-full text-left px-4 py-2 rounded-lg hover:bg-red-100 dark:hover:bg-red-600 transition text-gray-800 dark:text-white">
                                Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </nav>
            <div class="p-2 border-t dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <span class="text-gray-700 dark:text-gray-300">Dark Mode</span>
                    <button @click="darkMode = !darkMode; document.documentElement.classList.toggle('dark', darkMode)"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-gray-200 dark:bg-gray-600 transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        :class="{ 'bg-blue-600': darkMode }">
                        <span class="sr-only">Toggle dark mode</span>
                        <span
                            class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                            :class="{ 'translate-x-5': darkMode, 'translate-x-0': !darkMode }"></span>
                    </button>
                </div>
            </div>
        </aside>
        <!-- Main Content -->
        <main class="flex-grow overflow-y-auto" :class="{ '': sidebarOpen && !isMobile }">
            <div class="container mx-auto px-4 py-8">
                <div class="flex items-center justify-between mb-8">
                    <h1 class="text-2xl md:text-4xl font-bold text-gray-800 dark:text-white">User Panel Dashboard</h1>
                    <button @click="sidebarOpen = true" class="text-gray-600 dark:text-gray-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
                <!-- Dashboard Section -->
                <div x-show="tab === 'dashboard'" class="bg-gray-100 rounded-2xl shadow-xl p-8 text-gray-800">
                    <!-- Dashboard Header -->
                    <h2 class="text-3xl font-extrabold text-center mb-10 flex items-center justify-center gap-2">
                        <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 12l2-2m0 0l7-7 7 7M13 5v6h6m-6 0v6m0-6H7"></path>
                        </svg>
                        Dashboard Overview
                    </h2>
                    <!-- Top Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <!-- Total Uploads -->
                        <div class="bg-white p-6 rounded-xl shadow hover:scale-105 transition transform duration-300">
                            <div class="flex items-center justify-center gap-3">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 7l9-4 9 4-9 4-9-4zm0 10l9 4 9-4m-18-6l9 4 9-4"></path>
                                </svg>
                                <h3 class="text-lg font-semibold">Total Uploads</h3>
                            </div>
                            <p class="text-3xl font-bold mt-2">{{ $totalUploads }}</p>
                            <p class="text-sm mt-2">Events/Albums: {{ $totalEvents }}</p>
                            <p class="text-sm">Featured: {{ $featuredPhotos }}</p>
                            <p class="text-sm">With Faces: {{ $photosWithFaces }}</p>
                        </div>
                        <!-- Storage Used -->
                        <div class="bg-white p-6 rounded-xl shadow hover:scale-105 transition transform duration-300">
                            <div class="flex items-center justify-center gap-3">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <h3 class="text-lg font-semibold">Storage Used</h3>
                            </div>
                            <p class="text-3xl font-bold mt-2">{{ $storageUsedGB }} GB /
                                {{ $storageLimitGB > 0 ? $storageLimitGB . ' GB' : 'Unlimited' }}</p>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mt-3">
                                <div class="bg-gray-600 h-2.5 rounded-full transition-all duration-500"
                                    style="width: {{ $storageUsagePercent }}%"></div>
                            </div>
                            <p class="text-sm mt-2">{{ $storageUsagePercent }}% Used</p>
                        </div>
                        <!-- Active Plan -->
                        <div class="bg-white p-6 rounded-xl shadow hover:scale-105 transition transform duration-300">
                            <div class="flex items-center justify-center gap-3">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <h3 class="text-lg font-semibold">Active Plan</h3>
                            </div>
                            <p class="text-3xl font-bold mt-2">{{ $plan->name ?? 'Free' }}</p>
                            <p class="text-sm mt-2">Daily Upload Limit:
                                {{ $dailyUploadLimit > 0 ? $dailyUploadLimit : 'Unlimited' }}</p>
                            <p class="text-sm">Facial Recognition:
                                {{ $plan->facial_recognition_enabled ? 'Enabled' : 'Disabled' }}</p>
                        </div>
                    </div>
                    <!-- Daily Uploads -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-white p-6 rounded-xl shadow hover:scale-105 transition transform duration-300">
                            <div class="flex items-center justify-center gap-3">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 7V3m8 4V3M4 11h16M4 19h16"></path>
                                </svg>
                                <h3 class="text-lg font-semibold">Today's Uploads</h3>
                            </div>
                            <p class="text-3xl font-bold mt-2">{{ $todayUploads }} /
                                {{ $dailyUploadLimit > 0 ? $dailyUploadLimit : 'Unlimited' }}</p>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mt-3">
                                <div class="bg-gray-600 h-2.5 rounded-full transition-all duration-500"
                                    style="width: {{ $dailyUploadUsagePercent }}%"></div>
                            </div>
                            <p class="text-sm mt-2">{{ $dailyUploadUsagePercent }}% of Daily Limit</p>
                        </div>
                    </div>
                    <!-- Recent Activity -->
                    <div class="bg-white p-6 rounded-xl mb-8 shadow">
                        <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3"></path>
                            </svg>
                            Recent Activity
                        </h3>
                        <ul class="space-y-3">
                            @forelse ($recentUploads as $upload)
                                <li
                                    class="flex items-center space-x-4 bg-gray-100 p-3 rounded-lg hover:bg-gray-200 transition">
                                    <img src="{{ $upload['url'] }}" alt="{{ $upload['title'] }}"
                                        class="w-12 h-12 rounded-md object-cover">
                                    <div>
                                        <p class="font-medium">{{ $upload['title'] }}</p>
                                        <p class="text-sm text-gray-500">{{ $upload['date'] }} -
                                            {{ $upload['location'] }}</p>
                                        <p class="text-sm text-gray-500">Tags: {{ implode(', ', $upload['tags']) }}
                                        </p>
                                    </div>
                                </li>
                            @empty
                                <li class="text-gray-500">No recent uploads.</li>
                            @endforelse
                        </ul>
                    </div>
                    <!-- Photos by Event -->
                    <div class="bg-white p-6 rounded-xl shadow">
                        <h3 class="text-xl font-semibold mb-4 flex items-center gap-2">
                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18">
                                </path>
                            </svg>
                            All Photos by Event
                        </h3>
                        @forelse ($photos as $event => $eventPhotos)
                            <div class="mb-6">
                                <h4 class="text-lg font-medium mb-3">{{ $event ?: 'Uncategorized' }}</h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    @foreach ($eventPhotos as $photos)
                                        <div class="relative group overflow-hidden rounded-lg shadow">
                                            <img src="{{ $photos['url'] }}" alt="{{ $photos['title'] }}"
                                                class="w-full h-32 object-cover group-hover:scale-110 transition duration-500">
                                            <p
                                                class="absolute bottom-0 left-0 w-full bg-black/40 text-white text-xs px-2 py-1 opacity-90 group-hover:opacity-100 transition">
                                                {{ $photos['title'] }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500">No photos available.</p>
                        @endforelse
                    </div>
                </div>
                <!-- Upload Media Section -->

                <div x-show="tab === 'upload'" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
                    <h2 class="text-3xl font-bold text-center mb-8 text-gray-800 dark:text-white">Upload Media</h2>
                    @if (session('success'))
                        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show"
                            class="bg-blue-100 dark:bg-blue-900 border-l-4 border-blue-500 text-blue-700 dark:text-blue-200 p-2 mb-6 rounded"
                            role="alert">
                            <span x-text="session('success')"></span>
                            @if (session('urls'))
                                <br>
                                @foreach (session('urls') as $url)
                                    <a :href="'{{ $url }}'" class="underline font-medium"
                                        target="_blank">View Media</a><br>
                                @endforeach
                            @endif
                        </div>
                    @endif
                    @if (session('error'))
                        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show"
                            class="bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-200 p-2 mb-6 rounded"
                            role="alert">
                            <span x-text="session('error')"></span>
                        </div>
                    @endif
                    @if ($errors->any())
                        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show"
                            class="bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-200 p-2 mb-6 rounded"
                            role="alert">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li x-text="`{{ $error }}`"></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('upload') }}" method="POST" enctype="multipart/form-data"
                        @submit.prevent="handleSubmit" x-data="uploadFormData()" x-on:submit="showSweetAlert($event)">
                        @csrf
                        <!-- Media Selection -->
                        <div class="mb-6 relative group">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Select Photos/Videos
                                <span class="text-xs text-gray-500 dark:text-gray-400">(JPEG, PNG, MP4, MOV, max
                                    5MB)</span>
                            </label>
                            <div class="drag-drop-zone rounded-lg p-6 text-center cursor-pointer bg-gray-50 dark:bg-gray-700 border-2 border-dashed"
                                :class="{ 'dragover': isDragging }" @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false" @drop.prevent="handleDrop($event)">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-2" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16V8m0 0l-4 4m4-4l4 4m6-4v8m0 0h-4m4 0h4" />
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400">Drag & drop files here, or click to select
                                    (multiple allowed)</p>
                                <input type="file" name="files[]" id="file" class="hidden"
                                    accept="image/jpeg,image/png,video/mp4,video/quicktime" multiple
                                    @change.debounce.500ms="handleFileChange($event)" x-ref="fileInput">
                                <div class="flex justify-center space-x-2 mt-2">
                                    <button type="button"
                                        class="bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 py-1 px-3 rounded-full text-sm hover:bg-blue-200 dark:hover:bg-blue-800 transition"
                                        @click="$refs.fileInput.click()">
                                        <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        Browse Files
                                    </button>
                                    <button type="button"
                                        class="bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 py-1 px-3 rounded-full text-sm hover:bg-green-200 dark:hover:bg-green-800 transition"
                                        @click="captureFromCamera">
                                        <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 9a2 2 0 012-2h4l2-2h4l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        </svg>
                                        Use Camera
                                    </button>
                                </div>
                                <div
                                    class="absolute top-0 right-0 mt-1 mr-1 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-800 text-white text-xs rounded py-1 px-2">
                                    Tip: Upload multiple files for bulk processing. Use camera for live events.
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4" x-show="previews.length > 0">
                                <template x-for="(preview, index) in previews" :key="index">
                                    <div
                                        class="border border-gray-300 dark:border-gray-600 rounded-lg p-2 bg-white dark:bg-gray-700 relative group">
                                        <img :src="preview.url" alt="Preview"
                                            class="preview-canvas mx-auto rounded-lg shadow"
                                            x-show="preview.type.startsWith('image/')">
                                        <video :id="'preview-video-' + index" :src="preview.url" controls
                                            class="preview-video mx-auto rounded-lg shadow"
                                            x-show="preview.type.startsWith('video/')"></video>
                                        <button type="button"
                                            class="absolute top-1 right-1 bg-red-500 text-white rounded-full h-6 w-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition"
                                            @click="removePreview(index)">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            @error('files.*')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <!-- Form Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="relative group">
                                <label for="title"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                                <input type="text" name="title" id="title" placeholder="Enter media title"
                                    class="form-input p-2 mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                    required>
                                <div
                                    class="absolute top-0 right-0 mt-1 mr-1 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-800 text-white text-xs rounded py-1 px-2">
                                    Tip: Use a descriptive title for your photo/video.
                                </div>
                                @error('title')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="relative group">
                                <label for="folder_name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Event/Folder
                                    Name</label>
                                <input type="text" name="folder_name" id="folder_name"
                                    placeholder="Enter event or folder name"
                                    class="form-input p-2 mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                    required>
                                <div
                                    class="absolute top-0 right-0 mt-1 mr-1 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-800 text-white text-xs rounded py-1 px-2">
                                    Tip: Group media by event or folder (e.g., "Wedding 2025").
                                </div>
                                @error('folder_name')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-4 relative group">
                            <label for="description"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <textarea name="description" id="description" rows="3" placeholder="Describe your media"
                                class="form-input p-2 mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                            <div
                                class="absolute top-0 right-0 mt-1 mr-1 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-800 text-white text-xs rounded py-1 px-2">
                                Tip: Add details to make your media searchable.
                            </div>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4 relative group">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tags (AI
                                Auto-Tags + Custom)</label>
                            <input type="hidden" name="tags" x-model="tags">
                            <div x-show="isLoadingTags" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Loading AI tags...
                            </div>
                            <div class="flex flex-wrap gap-2 mt-2"
                                x-show="!isLoadingTags && suggestedTags.length > 0">
                                <template x-for="tag in suggestedTags" :key="tag">
                                    <button type="button"
                                        class="px-3 py-1 rounded-full text-sm font-medium transition"
                                        :class="selectedTags.includes(tag) ? 'bg-blue-600 text-white' :
                                            'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-blue-500 hover:text-white'"
                                        @click="toggleTag(tag)">
                                        <span x-text="tag"></span>
                                    </button>
                                </template>
                            </div>
                            <input type="text" id="custom-tags" placeholder="Add custom tags (comma-separated)"
                                class="form-input p-2 mt-2 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                @keyup.enter="addCustomTags($event.target.value)"
                                @blur="addCustomTags($event.target.value)">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">AI auto-tags from AWS Rekognition.
                                Click tags to select/deselect or add custom tags.</p>
                            <div
                                class="absolute top-0 right-0 mt-1 mr-1 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-800 text-white text-xs rounded py-1 px-2">
                                Tip: Tags help users find your media in searches.
                            </div>
                            @error('tags')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div class="relative group">
                                <label for="tour_provider"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tour
                                    Provider</label>
                                <input type="text" name="tour_provider" id="tour_provider"
                                    placeholder="Enter tour provider (e.g., Blue Star Tours)"
                                    class="form-input p-2 mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                <div
                                    class="absolute top-0 right-0 mt-1 mr-1 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-800 text-white text-xs rounded py-1 px-2">
                                    Tip: Specify the tour provider for event-based media.
                                </div>
                                @error('tour_provider')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="relative group">
                                <label for="location"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                                <input type="text" name="location" id="location"
                                    placeholder="Enter location (e.g., Paris, France)"
                                    class="form-input p-2 mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                    x-model="location">
                                <div
                                    class="absolute top-0 right-0 mt-1 mr-1 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-800 text-white text-xs rounded py-1 px-2">
                                    Tip: Auto-filled by geolocation, but you can edit it.
                                </div>
                                @error('location')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="mb-6 relative group">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_featured" id="is_featured" value="1"
                                    class="h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500"
                                    {{ old('is_featured') ? 'checked' : '' }}>
                                <label for="is_featured" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    Mark as Featured
                                </label>
                                <div
                                    class="absolute right-0 mt-1 mr-1 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-800 text-white text-xs rounded py-1 px-2">
                                    Tip: Featured media appears prominently in galleries.
                                </div>
                            </div>
                            @error('is_featured')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <!-- Progress Bar -->
                        <div x-show="progress > 0" class="mb-4">
                            <div class="bg-gray-200 dark:bg-gray-600 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: progress + '%' }"></div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Uploading: <span
                                    x-text="progress + '%'"></span></p>
                        </div>
                        <!-- Submit -->
                        <button type="submit"
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition duration-200 font-medium flex items-center justify-center"
                            :disabled="isUploading">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Upload Media
                        </button>
                    </form>
                </div>


                <!-- Photos Album Section -->

                <div x-data="{
                    tab: 'album',
                    selectedPhoto: null,
                    searchTags: '',
                    filteredPhotos: @json($photos),
                    showPreview: false,
                    previewPhoto: null
                }" x-show="tab === 'album'"
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-center mb-6 text-gray-800 dark:text-white">📸 Photo Gallery</h2>
                    <!-- Search by Tags -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">🔍 Search by Tags</h3>
                        <input type="text" x-model="searchTags" @input.debounce.500ms="filterPhotos()"
                            placeholder="Enter tags (e.g., Nature, Beach)"
                            class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white">
                    </div>
                    <!-- Recent Uploads -->
                    <div class="mb-10">
                        <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-3">🆕 Recent Uploads</h3>

                        @php
                            // group recent uploads by event so heading ek baar aaye
                            $recentGrouped = $recentUploads->groupBy('event');
                        @endphp

                        @forelse($recentGrouped as $event => $eventPhotos)
                            <div class="mb-8">
                                {{-- BIG centered H2 style heading (ek hi baar per event) --}}
                                <h2
                                    class="text-2xl md:text-3xl font-extrabold text-center text-gray-900 dark:text-gray-100 mb-4">
                                    {{ $event ?? 'Uncategorized' }}
                                    <span class="ml-2 text-sm md:text-base font-normal text-gray-500">
                                        {{ $eventPhotos->count() }} pic{{ $eventPhotos->count() > 1 ? 's' : '' }}
                                    </span>
                                </h2>

                                {{-- Images for this event (chahe to ->take(4) rakho) --}}
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                    @foreach ($eventPhotos->take(4) as $photo)
                                        <div class="relative group rounded-lg shadow overflow-hidden">
                                            <img src="{{ $photo['url'] }}" alt="{{ $photo['title'] }}"
                                                class="w-full h-36 md:h-44 object-contain bg-gray-100">

                                            {{-- action buttons --}}
                                            <div
                                                class="absolute top-1 right-1 z-20 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <form action="{{ route('photos.destroy', $photo['id']) }}"
                                                    method="POST"
                                                    @submit.prevent="deletePhoto($event, {{ $photo['id'] }})">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="bg-red-500 text-white rounded-full h-6 w-6 flex items-center justify-center hover:bg-red-600 transition">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </form>

                                                <button
                                                    @click="selectedPhoto = '{{ $photo['url'] }}'; tab = 'edit'; if(isMobile) sidebarOpen = false"
                                                    class="bg-blue-500 text-white rounded-full h-6 w-6 flex items-center justify-center hover:bg-blue-600 transition">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>

                                                <button
                                                    @click="openPreview('{{ $photo['url'] }}', @json($photo))"
                                                    class="bg-green-500 text-white rounded-full h-6 w-6 flex items-center justify-center hover:bg-green-600 transition">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm-12 0c0 5.523 4.477 10 10 10s10-4.477 10-10S18.523 2 13 2 3 6.477 3 12z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 dark:text-gray-400">No recent uploads.</p>
                        @endforelse
                    </div>


                    <!-- Albums -->
                    <div class="space-y-8">
                        <template x-for="[event, eventPhotos] in Object.entries(filteredPhotos)"
                            :key="event">
                            <div x-data="{ renameOpen: false, newName: event, inviteOpen: false, inviteEmail: '' }">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-xl font-semibold text-gray-800 dark:text-white">📂 <span
                                            x-text="event || 'Uncategorized'"></span></h3>
                                    <div class="flex space-x-2">
                                        <button @click="renameOpen = true"
                                            class="bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 py-1 px-3 rounded hover:bg-gray-300 dark:hover:bg-gray-500">
                                            Rename
                                        </button>
                                        <button @click="deleteAlbum(event)"
                                            class="bg-red-500 text-white py-1 px-3 rounded hover:bg-red-600">
                                            Delete
                                        </button>
                                        <button @click="inviteOpen = true"
                                            class="bg-blue-500 text-white py-1 px-3 rounded hover:bg-blue-600">
                                            Invite
                                        </button>
                                    </div>
                                </div>
                                <!-- Rename Form -->
                                <div x-show="renameOpen" class="mb-4">
                                    <form @submit.prevent="renameAlbum(event, newName)">
                                        <input type="text" x-model="newName"
                                            class="form-input p-2 mr-2 border-gray-300 dark:border-gray-600 rounded-md"
                                            placeholder="New event name">
                                        <button type="submit"
                                            class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700">Save</button>
                                        <button type="button" @click="renameOpen = false"
                                            class="bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 py-1 px-3 rounded hover:bg-gray-300 dark:hover:bg-gray-500">Cancel</button>
                                    </form>
                                </div>
                                <!-- Invite Form -->
                                <div x-show="inviteOpen" class="mb-4">
                                    <form @submit.prevent="inviteCollaborator(event, inviteEmail)">
                                        <input type="email" x-model="inviteEmail"
                                            class="form-input p-2 mr-2 border-gray-300 dark:border-gray-600 rounded-md"
                                            placeholder="Collaborator email">
                                        <button type="submit"
                                            class="bg-blue-600 text-white py-1 px-3 rounded hover:bg-blue-700">Send
                                            Invite</button>
                                        <button type="button" @click="inviteOpen = false"
                                            class="bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 py-1 px-3 rounded hover:bg-gray-300 dark:hover:bg-gray-500">Cancel</button>
                                    </form>
                                </div>
                                <!-- Photos Grid -->
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                    <template x-for="photo in eventPhotos" :key="photo.id">
                                        <div class="relative group overflow-hidden rounded-lg shadow">
                                            <img :src="photo.url" :alt="photo.title"
                                                class="w-full h-32 object-cover group-hover:scale-110 transition duration-500">
                                            <p
                                                class="absolute bottom-0 left-0 w-full bg-black/40 text-white text-xs px-2 py-1 opacity-90 group-hover:opacity-100 transition">
                                                <span x-text="photo.title"></span>
                                            </p>
                                            <div
                                                class="absolute top-1 right-1 flex space-x-1 opacity-0 group-hover:opacity-100 gallery-button transition-opacity">
                                                <form action="{{ route('photos.destroy', ':photo.id') }}"
                                                    method="POST" @submit.prevent="deletePhoto($event, photo.id)">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="bg-red-500 text-white rounded-full h-6 w-6 flex items-center justify-center hover:bg-red-600 transition">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </form>
                                                <button
                                                    @click="selectedPhoto = photo.url; tab = 'edit'; if(isMobile) sidebarOpen = false"
                                                    class="bg-blue-500 text-white rounded-full h-6 w-6 flex items-center justify-center hover:bg-blue-600 transition">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                                <button @click="openPreview(photo.url, photo)"
                                                    class="bg-green-500 text-white rounded-full h-6 w-6 flex items-center justify-center hover:bg-green-600 transition">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm-12 0c0 5.523 4.477 10 10 10s10-4.477 10-10S18.523 2 13 2 3 6.477 3 12z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <div x-show="Object.keys(filteredPhotos).length === 0"
                            class="text-gray-500 dark:text-gray-400 text-center">
                            No photos found. Upload some 📤
                        </div>
                    </div>
                    <!-- Preview Modal -->
                    <div x-show="showPreview"
                        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                        @click="showPreview = false">
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 max-w-3xl w-full" @click.stop>
                            <img :src="previewPhoto.url" :alt="previewPhoto.title"
                                class="w-full max-h-[70vh] object-contain rounded-lg">
                            <div class="mt-4">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white"
                                    x-text="previewPhoto.title"></h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400"
                                    x-text="previewPhoto.date + ' - ' + previewPhoto.location"></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400"
                                    x-text="'Tags: ' + (previewPhoto.tags || []).join(', ')"></p>
                            </div>
                            <button @click="showPreview = false"
                                class="mt-4 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 py-1 px-3 rounded hover:bg-gray-300 dark:hover:bg-gray-500">
                                Close
                            </button>
                        </div>
                    </div>
                </div>


        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exif-js/2.3.0/exif.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('gallery', () => ({
                filterPhotos() {
                    const tags = this.searchTags.toLowerCase().split(',').map(tag => tag.trim()).filter(
                        tag => tag);
                    if (!tags.length) {
                        this.filteredPhotos = @json($photos);
                        return;
                    }
                    const filtered = {};
                    Object.entries(@json($photos)).forEach(([event, photos]) => {
                        const matchingPhotos = photos.filter(photo =>
                            tags.every(searchTag =>
                                photo.tags.some(tag => tag.toLowerCase().includes(
                                    searchTag))
                            )
                        );
                        if (matchingPhotos.length) {
                            filtered[event] = matchingPhotos;
                        }
                    });
                    this.filteredPhotos = filtered;
                },
                openPreview(url, photo) {
                    this.previewPhoto = photo;
                    this.showPreview = true;
                },
                deletePhoto(event, id) {
                    if (confirm('Are you sure you want to delete this photo?')) {
                        event.target.closest('form').submit();
                    }
                },
                renameAlbum(event, newName) {
                    console.log(`Renaming album ${event} to ${newName}`);
                },
                deleteAlbum(event) {
                    if (confirm(`Are you sure you want to delete the album ${event}?`)) {
                        console.log(`Deleting album ${event}`);
                    }
                },
                inviteCollaborator(event, email) {
                    console.log(`Inviting ${email} to album ${event}`);
                }
            }));

            Alpine.data('uploadFormData', () => ({
                isDragging: false,
                previews: [],
                tags: '',
                suggestedTags: [],
                selectedTags: [],
                location: '',
                progress: 0,
                isLoadingTags: false,
                processedFiles: new Set(),
                isUploading: false, // Prevent multiple submissions
                handleFileChange(event) {
                    if (this.isUploading) return; // Prevent processing during upload
                    console.log('handleFileChange triggered with files:', event.target.files);
                    this.processFiles(event.target.files);
                },
                handleDrop(event) {
                    if (this.isUploading) return; // Prevent processing during upload
                    console.log('handleDrop triggered with files:', event.dataTransfer.files);
                    this.isDragging = false;
                    const files = event.dataTransfer.files;
                    const input = this.$refs.fileInput;
                    input.files = files; // Set files to input
                    this.processFiles(files);
                },
                processFiles(files) {
                    console.log('Processing files:', files);
                    this.previews = [];
                    this.suggestedTags = [];
                    this.selectedTags = [];
                    this.tags = '';
                    this.isLoadingTags = true;
                    this.processedFiles.clear();
                    let pendingRequests = files.length;
                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];
                        const fileKey =
                            `${file.name}-${file.size}-${file.lastModified}`; // Unique identifier
                        if (this.processedFiles.has(fileKey)) {
                            console.log(`Skipping duplicate file: ${file.name}`);
                            pendingRequests--;
                            continue;
                        }
                        this.processedFiles.add(fileKey);
                        if (file.size > 15 * 1024 * 1024) { // 15MB limit
                            Swal.fire({
                                title: 'Error!',
                                text: `${file.name} exceeds 15MB limit.`,
                                icon: 'error',
                            });
                            pendingRequests--;
                            if (pendingRequests === 0) this.isLoadingTags = false;
                            continue;
                        }
                        this.previews.push({
                            url: URL.createObjectURL(file),
                            type: file.type,
                            tags: []
                        });
                        if (file.type.startsWith('image/')) {
                            EXIF.getData(file, () => {
                                const lat = EXIF.getTag(file, 'GPSLatitude');
                                const lon = EXIF.getTag(file, 'GPSLongitude');
                                if (lat && lon) {
                                    const latRef = EXIF.getTag(file, 'GPSLatitudeRef') || 'N';
                                    const lonRef = EXIF.getTag(file, 'GPSLongitudeRef') || 'E';
                                    const latDeg = lat[0] + lat[1] / 60 + lat[2] / 3600;
                                    const lonDeg = lon[0] + lon[1] / 60 + lon[2] / 3600;
                                    this.location =
                                        `${latDeg.toFixed(4)} ${latRef}, ${lonDeg.toFixed(4)} ${lonRef}`;
                                }
                            });
                            const formData = new FormData();
                            formData.append('image', file);
                            fetch('{{ route('photos.analyze') }}', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').content,
                                    },
                                    body: formData,
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success && data.tags) {
                                        const newTags = data.tags.split(',').map(tag => tag.trim())
                                            .filter(tag => tag);
                                        this.previews[i].tags = newTags;
                                        this.suggestedTags = [...new Set([...this.suggestedTags, ...
                                            newTags
                                        ])];
                                        this.selectedTags = [...new Set([...this.selectedTags, ...
                                            newTags
                                        ])];
                                        this.tags = this.selectedTags.join(',');
                                    } else {
                                        Swal.fire({
                                            title: 'Error!',
                                            text: data.message ||
                                                'Failed to fetch tags from AWS Rekognition.',
                                            icon: 'error',
                                        });
                                    }
                                    pendingRequests--;
                                    if (pendingRequests === 0) this.isLoadingTags = false;
                                })
                                .catch(error => {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'An error occurred while fetching tags.',
                                        icon: 'error',
                                    });
                                    console.error('Tag fetch error:', error);
                                    pendingRequests--;
                                    if (pendingRequests === 0) this.isLoadingTags = false;
                                });
                        } else {
                            pendingRequests--;
                            if (pendingRequests === 0) this.isLoadingTags = false;
                        }
                    }
                    console.log('Processed files:', this.processedFiles);
                },
                removePreview(index) {
                    console.log('Removing preview at index:', index);
                    const removedTags = this.previews[index].tags;
                    this.previews.splice(index, 1);
                    this.suggestedTags = [...new Set(this.previews.flatMap(preview => preview.tags))];
                    this.selectedTags = this.selectedTags.filter(tag => this.suggestedTags.includes(
                        tag) || this.isCustomTag(tag));
                    this.tags = this.selectedTags.join(',');
                    const input = this.$refs.fileInput;
                    const dt = new DataTransfer();
                    for (let i = 0; i < input.files.length; i++) {
                        if (i !== index) dt.items.add(input.files[i]);
                    }
                    input.files = dt.files;
                    this.processedFiles.clear();
                    for (let i = 0; i < input.files.length; i++) {
                        this.processedFiles.add(
                            `${input.files[i].name}-${input.files[i].size}-${input.files[i].lastModified}`
                        );
                    }
                    console.log('Updated processed files:', this.processedFiles);
                },
                isCustomTag(tag) {
                    return !this.previews.some(preview => preview.tags.includes(tag));
                },
                toggleTag(tag) {
                    const index = this.selectedTags.indexOf(tag);
                    if (index > -1) {
                        this.selectedTags.splice(index, 1);
                    } else {
                        this.selectedTags.push(tag);
                    }
                    this.tags = this.selectedTags.join(',');
                },
                addCustomTags(input) {
                    if (!input) return;
                    const newTags = input.split(',').map(tag => tag.trim()).filter(tag => tag && !this
                        .selectedTags.includes(tag));
                    this.selectedTags.push(...newTags);
                    this.suggestedTags.push(...newTags);
                    this.tags = this.selectedTags.join(',');
                    document.getElementById('custom-tags').value = '';
                },
                captureFromCamera() {
                    alert('Camera capture not implemented yet');
                },
                showSweetAlert(event) {
                    if (this.isUploading) {
                        console.log('Upload already in progress, ignoring submit');
                        return;
                    }
                    event.preventDefault();
                    Swal.fire({
                        title: 'Uploading...',
                        text: 'Please wait while your files are being uploaded.',
                        icon: 'info',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                            this.handleSubmit(event);
                        }
                    });
                },
                async handleSubmit(event) {
                    if (this.isUploading) {
                        console.log('Upload already in progress, ignoring');
                        return;
                    }
                    this.isUploading = true;
                    console.log('Starting upload with files:', this.$refs.fileInput.files);
                    const form = event.target;
                    const formData = new FormData(form);
                    const xhr = new XMLHttpRequest();
                    xhr.timeout = 30000;
                    xhr.open('POST', form.action);
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector(
                        'meta[name="csrf-token"]').content);
                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) {
                            this.progress = (e.loaded / e.total) * 100;
                        }
                    };
                    xhr.onload = () => {
                        this.progress = 0;
                        this.isUploading = false;
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (xhr.status === 201 && response.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Media uploaded successfully.',
                                    icon: 'success',
                                    timer: 2000
                                }).then(() => {
                                    this.previews = [];
                                    this.suggestedTags = [];
                                    this.selectedTags = [];
                                    this.tags = '';
                                    this.location = '';
                                    this.processedFiles.clear();
                                    this.$refs.fileInput.value = '';
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message || 'Failed to upload media.',
                                    icon: 'error'
                                });
                            }
                        } catch (e) {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Invalid server response.',
                                icon: 'error'
                            });
                        }
                    };
                    xhr.ontimeout = () => {
                        this.progress = 0;
                        this.isUploading = false;
                        Swal.fire({
                            title: 'Error!',
                            text: 'Upload timed out. Please try again.',
                            icon: 'error'
                        });
                    };
                    xhr.onerror = () => {
                        this.progress = 0;
                        this.isUploading = false;
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred during upload.',
                            icon: 'error'
                        });
                    };
                    xhr.send(formData);
                }
            }));


        });
    </script>
</body>

</html>
