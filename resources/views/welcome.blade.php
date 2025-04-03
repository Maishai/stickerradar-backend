<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Stickerradar</title>

    <link href="https://tiles.versatiles.org/assets/lib/maplibre-gl/maplibre-gl.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <script src="https://tiles.versatiles.org/assets/lib/maplibre-gl/maplibre-gl.js"></script>
    <style>
        #map {
            height: 90vh;
            width: 100%;
            min-height: 500px;
        }

        .maplibregl-popup-content {
            background-color: white;
            color: #333;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            padding: 15px;
            border-radius: 8px;
            font-family: 'Instrument Sans', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .maplibregl-popup-content h3 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .maplibregl-popup-content p {
            margin: 0;
            font-size: 1rem;
            line-height: 1.4;
            margin-bottom: 8px;
        }

        .maplibregl-popup-content a.address {
            color: blue;
            text-decoration: underline;
        }

        .maplibregl-popup-content a.address:hover {
            color: darkblue;
        }

        .dark-mode .maplibregl-popup-content a.address {
            color: lightblue;
        }

        .dark-mode .maplibregl-popup-content a.address:hover {
            color: white;
        }

        .maplibregl-popup-close-button {
            color: #777;
            font-size: 1.2rem;
        }

        .dark-mode .maplibregl-popup-content {
            background-color: #2D3748;
            color: #CBD5E0;
            box-shadow: 0 2px 8px rgba(255, 255, 255, 0.08);
        }

        .dark-mode .maplibregl-popup-close-button {
            color: #A0AEC0;
        }

        .map-marker {
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
            color: white;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .user-location-marker {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background-color: #3498db;
            border: 2px solid white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.4);
                opacity: 0.5;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .tag {
            display: inline-block;
            padding: 4px 8px;
            margin-right: 4px;
            margin-bottom: 4px;
            border-radius: 4px;
            font-size: 0.8rem;
            color: white;
        }
    </style>
</head>

<body
    class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] dark:text-[#EDEDEC] flex items-center lg:justify-center min-h-screen flex-col">
    <header
        class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden pt-6 flex justify-between items-center">
        <div class="flex-shrink-0">
        </div>
        @if (Route::has('login'))
            <nav class="flex items-center gap-4">
                @auth
                    <a href="{{ url('/dashboard') }}"
                        class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal">
                        Log in
                    </a>
                @endauth
            </nav>
        @endif
    </header>
    <div id="map" class="flex-1 relative"></div>

    @if (Route::has('login'))
        <div class="h-14.5 hidden lg:block"></div>
    @endif
    <script>
        const map = new maplibregl.Map({
            container: 'map',
            style: 'https://tiles.versatiles.org/assets/styles/eclipse/style.json',
            center: [8.690206837689823, 48.44363437574163],
            zoom: 15
        });
        map.addControl(new maplibregl.NavigationControl());

        map.addControl(
            new maplibregl.GeolocateControl({
                positionOptions: {
                    enableHighAccuracy: true
                },
                trackUserLocation: true,
                showUserHeading: true
            })
        );

        const markers = {};
        let markerElement = "";
        let firstTagColor = "";
        let popupContent = "";

        @foreach (App\Models\Sticker::with('tags')->get() as $sticker)
            markerElement = document.createElement('div');
            markerElement.className = 'map-marker';
            firstTagColor = "{{ $sticker->tags->first()->color ?? '#888' }}";
            markerElement.style.backgroundColor = firstTagColor;

            popupContent = document.createElement('div');
            popupContent.innerHTML = `
                <div style="display: flex; flex-direction: column; align-items: center;">
                    <img src="{{ asset('storage/stickers/thumbnails/' . $sticker->filename) }}" style="max-width: 200px; height: auto; margin-bottom: 10px;">
                    <div>
                        @foreach ($sticker->tags as $tag)
                            <span class="tag" style="background-color: {{ $tag->color }};">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                </div>
            `;

            new maplibregl.Marker({
                    element: markerElement
                })
                .setLngLat([{{ $sticker->lon }}, {{ $sticker->lat }}])
                .setPopup(new maplibregl.Popup({
                        offset: 25
                    })
                    .setDOMContent(popupContent))
                .addTo(map);
        @endforeach

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
    </script>
</body>

</html>
