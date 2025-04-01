<div>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Upload Image</h1>

            <form wire:submit.prevent="save" class="space-y-6">
                @if ($photo)
                    <div class="mt-2 mb-4">
                        <div
                            class="relative rounded-lg overflow-hidden border-2 border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800">
                            <img alt="Display not working, see Troubleshooting in README"
                                src="{{ $photo->temporaryUrl() }}" class="w-full h-auto object-cover max-h-80">
                        </div>

                        @if ($lat && $lon)
                            <div
                                class="mt-3 p-3 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200">Location
                                            detected</h3>
                                        <div class="mt-1 text-sm text-green-700 dark:text-green-300">
                                            Latitude: {{ number_format($lat, 6) }} | Longitude:
                                            {{ number_format($lon, 6) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($noCoordinatesError)
                            <div
                                class="mt-3 p-3 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Location data
                                            missing</h3>
                                        <div class="mt-1 text-sm text-red-700 dark:text-red-300">
                                            This image doesn't contain GPS coordinates. Please upload an image with
                                            location data.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <label for="photo-upload" class="block cursor-pointer">
                        <div
                            class="rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700 p-12 text-center bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" stroke="currentColor"
                                fill="none" viewBox="0 0 48 48">
                                <path
                                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Click or drag to upload an image
                            </p>
                        </div>
                    </label>
                @endif

                <div class="mt-4">
                    <label for="photo-upload"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Image</label>
                    <div class="flex items-center">
                        <label for="photo-upload"
                            class="cursor-pointer py-2 px-4 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                            Select File
                        </label>
                        <span class="ml-3 text-sm text-gray-500 dark:text-gray-400" id="file-name">
                            @if ($photo)
                                {{ $photo->getClientOriginalName() }}
                            @else
                                No file chosen
                            @endif
                        </span>
                    </div>
                    <input id="photo-upload" type="file" wire:model="photo" class="sr-only">
                    @error('photo')
                        <span class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-4">
                    <x-select label="Tags" placeholder="Select tags for your sticker" :options="$tags"
                        option-label="name" multiselect option-value="id" wire:model.defer="selectedTags"
                        class="w-full" />
                    @error('selectedTags')
                        <span class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex justify-end mt-6">
                    <x-button type="submit" primary label="Upload" right-icon="arrow-up-tray" />
                </div>
            </form>
        </div>
    </div>
</div>
