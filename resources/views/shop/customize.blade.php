<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customize {{ $mug->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-6 sm:py-8">
        <h1 class="text-2xl sm:text-3xl font-bold mb-4 sm:mb-6">{{ $mug->name }} – Customize</h1>

        @if (session('success'))
            <div class="bg-green-100 text-green-700 p-3 sm:p-4 mb-4 rounded">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">
            <!-- Mug Preview -->
            <div>
                <h2 class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4">Mug Preview</h2>
                <img src="{{ Storage::url($mug->image_path) }}" alt="{{ $mug->name }}"
                     class="w-full h-48 sm:h-64 lg:h-80 object-cover rounded shadow-md">
                <p class="mt-3 sm:mt-4 text-gray-600 text-sm sm:text-base">{{ $mug->description }}</p>
                <p class="text-lg sm:text-xl font-bold mt-2">${{ number_format($mug->price, 2) }}</p>
            </div>

            <!-- Upload Form -->
            <div>
                <h2 class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4">Upload Your Image</h2>
                <form action="{{ route('shop.storeCustomization', $mug->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label for="custom_image" class="block text-sm font-medium">Custom Image (PNG/JPEG, max 2MB)</label>
                        <input type="file" name="custom_image" id="custom_image" accept="image/png,image/jpeg"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               required>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Preview:</p>
                        <div id="imagePreview"
                             class="mt-2 w-28 h-28 sm:w-32 sm:h-32 border rounded flex items-center justify-center text-gray-500 text-xs sm:text-sm bg-white">
                            No image selected
                        </div>
                    </div>
                    <button type="submit"
                            class="w-full sm:w-auto bg-blue-500 text-white px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg hover:bg-blue-600 transition">
                        Upload Custom Image
                    </button>
                </form>
            </div>
        </div>

        <a href="{{ route('shop.index') }}"
           class="mt-6 inline-block text-blue-600 hover:underline text-sm sm:text-base">
           ← Back to Shop
        </a>
    </div>

    <script>
        document.getElementById('custom_image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('imagePreview');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover rounded">`;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = 'No image selected';
            }
        });
    </script>
</body>
</html>
