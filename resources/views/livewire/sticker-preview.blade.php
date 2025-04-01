<div>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Sticker Gallery</h1>

            <div class="my-4">
                <x-select label="Tags" placeholder="Filter by tags" :options="$tags" option-label="name" multiselect
                    option-value="id" wire:model.live="selectedTags" class="w-full" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @forelse($stickers as $sticker)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-700/30 overflow-hidden">
                        <img src="{{ asset('storage/stickers/' . $sticker->filename) }}" alt="Sticker"
                            class="w-full h-48 object-cover">
                        <div class="p-4">
                            <div class="mb-2 text-xs text-gray-600 dark:text-gray-400">
                                <span title="Latitude, Longitude">📍 {{ number_format($sticker->lat, 5) }},
                                    {{ number_format($sticker->lon, 5) }}</span>
                            </div>
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach ($sticker->tags as $tag)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                            <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                Added {{ $sticker->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-gray-500 dark:text-gray-400">
                        <p>No stickers found with the selected filters.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
