<div class="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
    <div class="sm:px-0">
        <div class="flex justify-between items-center mb-4 sm:mb-6">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">Edit Sticker</h1>
            <x-button href="{{ route('stickers.index') }}" label="Back" icon="arrow-left"
                class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600" />
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-4 sm:mb-6">
            <div class="flex flex-col md:flex-row">
                <div
                    class="w-full md:w-3/5 p-4 sm:p-6 border-b md:border-b-0 md:border-r border-gray-200 dark:border-gray-700">
                    <div
                        class="rounded-lg overflow-hidden border-2 border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <div class="w-full h-[250px] sm:h-[350px] md:h-[400px] relative">
                            <img src="{{ asset('storage/stickers/' . $sticker->filename) }}" alt="Sticker"
                                class="absolute inset-0 w-full h-full object-cover">
                        </div>
                    </div>
                </div>

                <div class="w-full md:w-2/5 p-4 sm:p-6">
                    <h2 class="text-lg sm:text-xl font-semibold mb-4 sm:mb-6 text-gray-800 dark:text-white">Sticker
                        Details</h2>

                    <form wire:submit.prevent="save" class="space-y-4 sm:space-y-6">
                        <div>
                            <x-select label="Tags" placeholder="Select tags for your sticker" :options="$tags"
                                option-label="name" multiselect option-value="id" wire:model.defer="selectedTags"
                                class="w-full" />
                        </div>

                        <div>
                            <x-select label="State" placeholder="Select a state" :options="\App\State::cases()" option-label="name"
                                option-value="value" wire:model.defer="selectedState" class="w-full" />
                        </div>

                        <div>
                            <x-datetime-picker wire:model.defer="lastSeen" label="Last Seen" placeholder="Last Seen"
                                display-format="DD-MM-YYYY" parse-format="YYYY-MM-DD" without-time />
                        </div>

                        <div class="flex justify-between pt-2 sm:pt-4">
                            <x-button negative wire:click="delete"
                                wire:confirm="Are you sure you want to delete this sticker?" label="Delete"
                                icon="trash" />
                            <x-button type="submit" primary label="Save Changes" right-icon="check" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="p-4 sm:p-6">
                <h2 class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4 text-gray-800 dark:text-white">Location</h2>
                <div id="map" class="w-full h-[250px] sm:h-[350px] rounded-lg overflow-hidden"></div>
            </div>
        </div>
    </div>

    @assets
        <link href="https://tiles.versatiles.org/assets/lib/maplibre-gl/maplibre-gl.css" rel="stylesheet" />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <script src="https://tiles.versatiles.org/assets/lib/maplibre-gl/maplibre-gl.js"></script>
    @endassets

    <style>
        .map-marker {
            width: 24px;
            height: 24px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 16px;
            color: white;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        @media (min-width: 640px) {
            .map-marker {
                width: 30px;
                height: 30px;
                font-size: 20px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            try {
                const map = new maplibregl.Map({
                    container: 'map',
                    style: 'https://tiles.versatiles.org/assets/styles/colorful/style.json',
                    center: [{{ $sticker->lon }}, {{ $sticker->lat }}],
                    zoom: 16
                });

                const markerElement = document.createElement('div');
                markerElement.className = 'map-marker';
                const firstTagColor = "{{ $sticker->tags->first()->color ?? '#888' }}";
                markerElement.style.backgroundColor = firstTagColor;

                new maplibregl.Marker({
                        element: markerElement
                    })
                    .setLngLat([{{ $sticker->lon }}, {{ $sticker->lat }}])
                    .addTo(map);

                window.addEventListener('resize', () => {
                    map.resize();
                });

                function updateDarkMode() {
                    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        document.body.classList.add('dark-mode');
                        map.setStyle('https://tiles.versatiles.org/assets/styles/eclipse/style.json');
                    } else {
                        document.body.classList.remove('dark-mode');
                        map.setStyle('https://tiles.versatiles.org/assets/styles/colorful/style.json');
                    }
                }

                updateDarkMode();
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', updateDarkMode);
            } catch (error) {
                console.error('Error initializing map:', error);
            }
        });
    </script>
</div>
