<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Panel Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/exif-js"></script>
    <script src="https://cdn.jsdelivr.net/npm/video.js@7/dist/video.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/video.js@7/dist/video-js.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

<body class="bg-gray-50 min-h-screen font-sans antialiased" x-data="dashboardData()" x-init="init()">
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
                        <button @click="logout()"
                            class="w-full text-left px-4 py-2 rounded-lg hover:bg-red-100 dark:hover:bg-red-600 transition text-gray-800 dark:text-white">
                            Logout
                        </button>
                    </li>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                        @csrf
                    </form>
                </ul>
            </nav>
            <div class="p-2 border-t dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <span class="text-gray-700 dark:text-gray-300">Dark Mode</span>
                    <button @click="toggleDarkMode()"
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
        <main class="flex-grow overflow-y-auto">
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
                        <div class="bg-white p-6 rounded-xl shadow hover:scale-105 transition transform duration-300">
                            <div class="flex items-center justify-center gap-3">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 7l9-4 9 4-9 4-9-4zm0 10l9 4 9-4m-18-6l9 4 9-4"></path>
                                </svg>
                                <h3 class="text-lg font-semibold">Total Uploads</h3>
                            </div>
                            <p class="text-3xl font-bold mt-2" x-text="stats.totalUploads"></p>
                            <p class="text-sm mt-2">Events/Albums: <span x-text="stats.totalEvents"></span></p>
                            <p class="text-sm">Featured: <span x-text="stats.featuredPhotos"></span></p>
                            <p class="text-sm">With Faces: <span x-text="stats.photosWithFaces"></span></p>
                        </div>

                        <div class="bg-white p-6 rounded-xl shadow hover:scale-105 transition transform duration-300">
                            <div class="flex items-center justify-center gap-3">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <h3 class="text-lg font-semibold">Storage Used</h3>
                            </div>
                            <p class="text-3xl font-bold mt-2">
                                <span x-text="stats.storageUsedGB"></span> GB /
                                <span
                                    x-text="stats.storageLimitGB > 0 ? stats.storageLimitGB + ' GB' : 'Unlimited'"></span>
                            </p>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mt-3">
                                <div class="bg-gray-600 h-2.5 rounded-full transition-all duration-500"
                                    :style="`width: ${stats.storageUsagePercent}%`"></div>
                            </div>
                            <p class="text-sm mt-2"><span x-text="stats.storageUsagePercent"></span>% Used</p>
                        </div>

                        <div class="bg-white p-6 rounded-xl shadow hover:scale-105 transition transform duration-300">
                            <div class="flex items-center justify-center gap-3">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <h3 class="text-lg font-semibold">Active Plan</h3>
                            </div>
                            <p class="text-3xl font-bold mt-2" x-text="stats.planName"></p>
                            <p class="text-sm mt-2">Daily Upload Limit: <span
                                    x-text="stats.dailyUploadLimit > 0 ? stats.dailyUploadLimit : 'Unlimited'"></span>
                            </p>
                            <p class="text-sm">Facial Recognition: <span
                                    x-text="stats.facialRecognition ? 'Enabled' : 'Disabled'"></span></p>
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
                            <template x-for="upload in recentUploads" :key="upload.id">
                                <li
                                    class="flex items-center space-x-4 bg-gray-100 p-3 rounded-lg hover:bg-gray-200 transition">
                                    <img :src="upload.url" :alt="upload.title"
                                        class="w-12 h-12 rounded-md object-cover">
                                    <div>
                                        <p class="font-medium" x-text="upload.title"></p>
                                        <p class="text-sm text-gray-500">
                                            <span x-text="upload.date"></span> - <span
                                                x-text="upload.location"></span>
                                        </p>
                                        <p class="text-sm text-gray-500">Tags: <span
                                                x-text="upload.tags.join(', ')"></span></p>
                                    </div>
                                </li>
                            </template>
                            <li x-show="recentUploads.length === 0" class="text-gray-500">No recent uploads.</li>
                        </ul>
                    </div>
                </div>

                <!-- Upload Media Section -->
                <div x-show="tab === 'upload'" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
                    <h2 class="text-3xl font-bold text-center mb-8 text-gray-800 dark:text-white">Upload Media</h2>

                    <!-- Success/Error Messages -->
                    <div x-show="uploadMessage.show" x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show"
                        class="border-l-4 p-2 mb-6 rounded" role="alert"
                        :class="uploadMessage.type === 'success' ?
                            'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-200' :
                            'bg-red-100 dark:bg-red-900 border-red-500 text-red-700 dark:text-red-200'">
                        <span x-text="uploadMessage.text"></span>
                    </div>

                    <form @submit.prevent="handleUploadSubmit()">
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
                                <input type="file" x-ref="fileInput" class="hidden"
                                    accept="image/jpeg,image/png,video/mp4,video/quicktime" multiple
                                    @change="handleFileChange($event)">
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
                                        @click="captureFromCamera()">
                                        <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 9a2 2 0 012-2h4l2-2h4l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        </svg>
                                        Use Camera
                                    </button>
                                </div>
                            </div>

                            <!-- File Previews -->
                            <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4" x-show="previews.length > 0">
                                <template x-for="(preview, index) in previews" :key="index">
                                    <div
                                        class="border border-gray-300 dark:border-gray-600 rounded-lg p-2 bg-white dark:bg-gray-700 relative group">
                                        <img :src="preview.url" alt="Preview"
                                            class="preview-canvas mx-auto rounded-lg shadow"
                                            x-show="preview.type.startsWith('image/')">
                                        <video :src="preview.url" controls
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
                        </div>

                        <!-- Form Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                                <input type="text" x-model="uploadForm.title" placeholder="Enter media title"
                                    class="form-input p-2 mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                    required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Event/Folder
                                    Name</label>
                                <input type="text" x-model="uploadForm.folderName"
                                    placeholder="Enter event or folder name"
                                    class="form-input p-2 mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                    required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <textarea x-model="uploadForm.description" rows="3" placeholder="Describe your media"
                                class="form-input p-2 mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                        </div>

                        <!-- Tags Section -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tags (AI
                                Auto-Tags + Custom)</label>
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
                            <input type="text" placeholder="Add custom tags (comma-separated)"
                                class="form-input p-2 mt-2 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                @keyup.enter="addCustomTags($event.target.value); $event.target.value = ''"
                                @blur="addCustomTags($event.target.value); $event.target.value = ''">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">AI auto-tags from image analysis.
                                Click tags to select/deselect or add custom tags.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tour
                                    Provider</label>
                                <input type="text" x-model="uploadForm.tourProvider"
                                    placeholder="Enter tour provider"
                                    class="form-input p-2 mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Location</label>
                                <input type="text" x-model="uploadForm.location" placeholder="Enter location"
                                    class="form-input p-2 mt-1 block w-full border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            </div>
                        </div>

                        <div class="mb-6">
                            <div class="flex items-center">
                                <input type="checkbox" x-model="uploadForm.isFeatured"
                                    class="h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500">
                                <label class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    Mark as Featured
                                </label>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div x-show="uploadProgress > 0" class="mb-4">
                            <div class="bg-gray-200 dark:bg-gray-600 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" :style="`width: ${uploadProgress}%`">
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Uploading: <span
                                    x-text="uploadProgress + '%'"></span></p>
                        </div>

                        <!-- Submit Button -->
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
                <div x-show="tab === 'album'" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                    <h2 class="text-2xl font-bold text-center mb-6 text-gray-800 dark:text-white">📸 Photo Gallery</h2>

                    <!-- Search by Tags -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">🔍 Search by Tags</h3>
                        <input type="text" x-model="searchTags" @input.debounce.500ms="filterPhotos()"
                            placeholder="Enter tags (e.g., Nature, Beach)"
                            class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-white">
                    </div>

                    <!-- Albums -->
                    <div class="space-y-8">
                        <template x-for="[event, eventPhotos] in Object.entries(filteredPhotos)"
                            :key="event">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-xl font-semibold text-gray-800 dark:text-white">
                                        📂 <span x-text="event || 'Uncategorized'"></span>
                                    </h3>
                                    <div class="flex space-x-2">
                                        <button @click="renameAlbum(event)"
                                            class="bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 py-1 px-3 rounded hover:bg-gray-300 dark:hover:bg-gray-500">
                                            Rename
                                        </button>
                                        <button @click="deleteAlbum(event)"
                                            class="bg-red-500 text-white py-1 px-3 rounded hover:bg-red-600">
                                            Delete
                                        </button>
                                    </div>
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
                                                <button @click="deletePhoto(photo.id)"
                                                    class="bg-red-500 text-white rounded-full h-6 w-6 flex items-center justify-center hover:bg-red-600 transition">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
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
                            <img :src="previewPhoto?.url" :alt="previewPhoto?.title"
                                class="w-full max-h-[70vh] object-contain rounded-lg">
                            <div class="mt-4">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-white"
                                    x-text="previewPhoto?.title"></h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400"
                                    x-text="previewPhoto ? previewPhoto.date + ' - ' + previewPhoto.location : ''"></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400"
                                    x-text="previewPhoto ? 'Tags: ' + (previewPhoto.tags || []).join(', ') : ''"></p>
                            </div>
                            <button @click="showPreview = false"
                                class="mt-4 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 py-1 px-3 rounded hover:bg-gray-300 dark:hover:bg-gray-500">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        window.__BOOTSTRAP__ = {
            stats: {
                /* ...controller se aaya stats... */
            },
            recentUploads: @json($recentUploads),
            albums: @json($photos), // grouped by event
            routes: {
                upload: "{{ route('upload.store') }}",
                photoDestroy: "{{ route('photos.destroy', ':id') }}",
                albumRename: "{{ route('albums.rename') }}",
                albumDelete: "{{ route('albums.delete') }}",
            }
        };
    </script>


    <script>
        function dashboardData() {
            return {
                /* ---------- layout ---------- */
                tab: localStorage.getItem('activeTab') || 'dashboard',
                sidebarOpen: false,
                isMobile: window.innerWidth < 768,
                darkMode: false,

                /* ---------- server data ---------- */
                stats: window.__BOOTSTRAP__.stats || {},
                recentUploads: window.__BOOTSTRAP__.recentUploads || [],
                rawAlbums: window.__BOOTSTRAP__.albums || {},
                filteredPhotos: {},
                searchTags: '',
                showPreview: false,
                previewPhoto: null,

                /* ---------- upload form ---------- */
                uploadForm: {
                    title: '',
                    folderName: '',
                    description: '',
                    tourProvider: '',
                    location: '',
                    isFeatured: false
                },
                isDragging: false,
                previews: [],
                suggestedTags: [],
                selectedTags: [],
                isLoadingTags: false,
                uploadProgress: 0,
                isUploading: false,
                uploadMessage: {
                    show: false,
                    type: 'success',
                    text: ''
                },

                /* ============ init ============ */
                init() {
                    // clone albums for filtered view
                    this.filteredPhotos = JSON.parse(JSON.stringify(this.rawAlbums || {}));

                    this.$watch('tab', v => localStorage.setItem('activeTab', v));
                    window.addEventListener('resize', () => {
                        this.isMobile = window.innerWidth < 768;
                        if (!this.isMobile) this.sidebarOpen = true;
                    });
                    this.sidebarOpen = !this.isMobile;
                },

                /* ============ helpers ============ */
                switchTab(name) {
                    this.tab = name;
                    if (this.isMobile) this.sidebarOpen = false;
                },
                toggleDarkMode() {
                    this.darkMode = !this.darkMode;
                    document.documentElement.classList.toggle('dark', this.darkMode);
                },

                // REAL logout (hidden form submit)
                logout() {
                    if (!confirm('Are you sure you want to logout?')) return;
                    const form = document.getElementById('logout-form');
                    if (form) form.submit();
                },

                /* ============ upload ============ */
                handleFileChange(e) {
                    if (!this.isUploading) this.processFiles(e.target.files);
                },
                handleDrop(e) {
                    if (this.isUploading) return;
                    this.isDragging = false;
                    const files = e.dataTransfer.files;
                    this.$refs.fileInput.files = files;
                    this.processFiles(files);
                },
                processFiles(files) {
                    this.previews = [];
                    this.suggestedTags = [];
                    this.selectedTags = [];
                    this.isLoadingTags = true;
                    const MAX_MB = 20;
                    for (const file of files) {
                        if (file.size > MAX_MB * 1024 * 1024) {
                            Swal.fire('Error', `${file.name} exceeds ${MAX_MB}MB limit.`, 'error');
                            continue;
                        }
                        this.previews.push({
                            url: URL.createObjectURL(file),
                            type: file.type,
                            tags: []
                        });
                        if (file.type.startsWith('image/')) {
                            setTimeout(() => {
                                const mock = ['nature', 'outdoor', 'beautiful', 'scenic'];
                                this.suggestedTags = [...new Set([...this.suggestedTags, ...mock])];
                                this.selectedTags = [...new Set([...this.selectedTags, ...mock])];
                                this.isLoadingTags = false;
                            }, 400);
                        }
                    }
                    if (!Array.from(files).some(f => f.type.startsWith('image/'))) this.isLoadingTags = false;
                },
                removePreview(idx) {
                    this.previews.splice(idx, 1);
                    const dt = new DataTransfer();
                    for (let i = 0; i < this.$refs.fileInput.files.length; i++) {
                        if (i !== idx) dt.items.add(this.$refs.fileInput.files[i]);
                    }
                    this.$refs.fileInput.files = dt.files;
                },
                toggleTag(t) {
                    const i = this.selectedTags.indexOf(t);
                    i > -1 ? this.selectedTags.splice(i, 1) : this.selectedTags.push(t);
                },
                addCustomTags(input) {
                    if (!input) return;
                    const tags = input.split(',').map(t => t.trim()).filter(t => t && !this.selectedTags.includes(t));
                    this.selectedTags.push(...tags);
                    this.suggestedTags.push(...tags);
                },
                captureFromCamera() {
                    alert('Camera capture functionality would be implemented here');
                },

                handleUploadSubmit() {
                    if (this.isUploading) return;
                    const files = this.$refs.fileInput.files;
                    if (!files || !files.length) {
                        Swal.fire('Select files', 'Please choose at least one file.', 'warning');
                        return;
                    }

                    const fd = new FormData();
                    Array.from(files).forEach(f => fd.append('files[]', f));
                    fd.append('title', this.uploadForm.title || '');
                    fd.append('folder_name', this.uploadForm.folderName || '');
                    fd.append('description', this.uploadForm.description || '');
                    fd.append('tags', (this.selectedTags || []).join(','));
                    fd.append('tour_provider', this.uploadForm.tourProvider || '');
                    fd.append('location', this.uploadForm.location || '');
                    fd.append('is_featured', this.uploadForm.isFeatured ? 1 : 0);

                    this.isUploading = true;

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', window.__BOOTSTRAP__.routes.upload, true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute(
                        'content'));
                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                    };
                    xhr.onload = () => {
                        this.isUploading = false;
                        this.uploadProgress = 0;
                        let res = {};
                        try {
                            res = JSON.parse(xhr.responseText);
                        } catch {}
                        if (xhr.status >= 200 && xhr.status < 300 && res.success) {
                            Swal.fire('Success!', res.message || 'Uploaded.', 'success').then(() => window.location
                                .reload());
                        } else {
                            Swal.fire('Error', res.message || 'Upload failed', 'error');
                        }
                    };
                    xhr.onerror = () => {
                        this.isUploading = false;
                        this.uploadProgress = 0;
                        Swal.fire('Error', 'Network error', 'error');
                    };
                    xhr.send(fd);
                },

                /* ============ gallery ============ */
                filterPhotos() {
                    const base = JSON.parse(JSON.stringify(this.rawAlbums || {}));
                    const terms = this.searchTags.toLowerCase().split(',').map(t => t.trim()).filter(Boolean);
                    if (!terms.length) {
                        this.filteredPhotos = base;
                        return;
                    }
                    const out = {};
                    Object.entries(base).forEach(([event, photos]) => {
                        const hits = photos.filter(p => terms.every(term => (p.tags || []).some(t => (t || '')
                            .toLowerCase().includes(term))));
                        if (hits.length) out[event] = hits;
                    });
                    this.filteredPhotos = out;
                },
                openPreview(url, photo) {
                    this.previewPhoto = photo;
                    this.showPreview = true;
                },

                async deletePhoto(id) {
                    if (!confirm('Are you sure you want to delete this photo?')) return;
                    const url = window.__BOOTSTRAP__.routes.photoDestroy.replace(':id', id);
                    const res = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Accept': 'application/json'
                        }
                    });
                    const json = await res.json().catch(() => ({}));
                    if (res.ok && json.success) {
                        [this.filteredPhotos, this.rawAlbums].forEach(map => {
                            Object.keys(map).forEach(ev => {
                                map[ev] = map[ev].filter(p => p.id !== id);
                                if (!map[ev].length) delete map[ev];
                            });
                        });
                        Swal.fire('Deleted!', 'Photo has been deleted.', 'success');
                    } else {
                        Swal.fire('Error', json.message || 'Failed to delete', 'error');
                    }
                },

                async renameAlbum(event) {
                    const newName = prompt(`Rename album "${event}" to:`, event);
                    if (!newName || newName === event) return;
                    const res = await fetch(window.__BOOTSTRAP__.routes.albumRename, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            old_event: event,
                            new_event: newName
                        })
                    });
                    const json = await res.json().catch(() => ({}));
                    if (res.ok && json.success) {
                        [this.filteredPhotos, this.rawAlbums].forEach(map => {
                            map[newName] = map[event];
                            delete map[event];
                        });
                        Swal.fire('Renamed!', `Album renamed to "${newName}".`, 'success');
                    } else {
                        Swal.fire('Error', json.message || 'Rename failed', 'error');
                    }
                },

                async deleteAlbum(event) {
                    if (!confirm(`Are you sure you want to delete the album "${event}"? All photos will be removed.`))
                        return;
                    const res = await fetch(window.__BOOTSTRAP__.routes.albumDelete, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            event
                        })
                    });
                    const json = await res.json().catch(() => ({}));
                    if (res.ok && json.success) {
                        delete this.filteredPhotos[event];
                        delete this.rawAlbums[event];
                        Swal.fire('Deleted!', `Album "${event}" has been deleted.`, 'success');
                    } else {
                        Swal.fire('Error', json.message || 'Delete failed', 'error');
                    }
                }
            };
        }
    </script>


</body>

</html>
