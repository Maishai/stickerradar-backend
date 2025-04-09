@props([
    'class' => 'relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700',
])

<div id="map" class="{{ $class }}">
</div>

@assets
    <link href="https://tiles.versatiles.org/assets/lib/maplibre-gl/maplibre-gl.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <script src="https://tiles.versatiles.org/assets/lib/maplibre-gl/maplibre-gl.js"></script>
@endassets

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

    /* Style for the refresh button */
    .refresh-control button {
        background: none;
        border: none;
        cursor: pointer;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .refresh-control button span {
        font-size: 18px;
    }
</style>

<script>
    function initializeMap() {
        if (window.location.pathname !== '{{ request()->path() === '/' ? '/' : '/' . request()->path() }}') {
            return;
        }

        try {
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

            let tagsById = {};

            const loadedQuadrants = new Set();
            const quadrantSize = 0.01;

            function getQuadrantKey(lat, lon) {
                const latQuad = Math.floor(lat / quadrantSize);
                const lonQuad = Math.floor(lon / quadrantSize);
                return `${latQuad}:${lonQuad}`;
            }

            fetch('/api/tags')
                .then(response => response.json())
                .then(data => {
                    data.data.forEach(tag => {
                        tagsById[tag.id] = tag;
                    });

                    map.on('load', () => {
                        loadStickersInView();
                    });

                    map.on('moveend', () => {
                        loadStickersInView();
                    });
                })
                .catch(error => {
                    console.error('Error fetching tags:', error);
                });

            function loadStickersInView() {
                const bounds = map.getBounds();

                const south = bounds.getSouth();
                const north = bounds.getNorth();
                const west = bounds.getWest();
                const east = bounds.getEast();

                const quadrantsToLoad = new Set();
                for (let lat = south; lat <= north; lat += quadrantSize) {
                    for (let lon = west; lon <= east; lon += quadrantSize) {
                        quadrantsToLoad.add(getQuadrantKey(lat, lon));
                    }
                }

                const newQuadrants = [...quadrantsToLoad].filter(q => !loadedQuadrants.has(q));

                if (newQuadrants.length === 0) {
                    console.log('All quadrants already loaded, skipping fetch');
                    return;
                }

                newQuadrants.forEach(q => loadedQuadrants.add(q));

                const params = {
                    min_lat: south,
                    max_lat: north,
                    min_lon: west,
                    max_lon: east
                };

                const queryString = Object.keys(params)
                    .map(key => `${key}=${params[key]}`)
                    .join('&');

                console.log(`Loading ${newQuadrants.length} new quadrants`);

                fetch(`/api/stickers?${queryString}`)
                    .then(response => response.json())
                    .then(data => {
                        data.data.forEach(sticker => {
                            if (markers[sticker.id]) return;

                            const markerElement = document.createElement('div');
                            markerElement.className = 'map-marker';

                            let firstTagColor = '#888';
                            if (sticker.tags && sticker.tags.length > 0) {
                                const firstTagId = sticker.tags[0];
                                if (tagsById[firstTagId]) {
                                    firstTagColor = tagsById[firstTagId].color;
                                }
                            }
                            markerElement.style.backgroundColor = firstTagColor;

                            const popupContent = document.createElement('div');

                            let tagsHtml = '';
                            if (sticker.tags) {
                                sticker.tags.forEach(tagId => {
                                    const tag = tagsById[tagId];
                                    if (tag) {
                                        tagsHtml +=
                                            `<span class="tag" style="background-color: ${tag.color};">${tag.name}</span>`;
                                    }
                                });
                            }

                            popupContent.innerHTML = `
    <div style="display: flex; flex-direction: column; align-items: center;">
        <img src="/storage/stickers/thumbnails/${sticker.filename}" style="max-width: 200px; height: auto; margin-bottom: 10px;">
        <div>${tagsHtml}</div>
    </div>
`;

                            const marker = new maplibregl.Marker({
                                    element: markerElement
                                })
                                .setLngLat([sticker.lon, sticker.lat])
                                .setPopup(new maplibregl.Popup({
                                        offset: 25
                                    })
                                    .setDOMContent(popupContent))
                                .addTo(map);

                            markers[sticker.id] = marker;
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching stickers:', error);
                    });
            }

            function refreshAllStickers() {
                loadedQuadrants.clear();

                Object.values(markers).forEach(marker => marker.remove());

                Object.keys(markers).forEach(id => delete markers[id]);

                loadStickersInView();
            }

            class RefreshControl {
                onAdd(map) {
                    this._map = map;
                    this._container = document.createElement('div');
                    this._container.className = 'maplibregl-ctrl maplibregl-ctrl-group refresh-control';

                    const button = document.createElement('button');
                    button.className = 'flex items-center justify-center w-8 h-8';
                    button.title = "Refresh Stickers";

                    const span = document.createElement('span');
                    span.className = 'text-lg text-neutral-700 dark:text-neutral-800';
                    span.textContent = '↻';

                    button.appendChild(span);
                    this._container.appendChild(button);
                    this._container.onclick = refreshAllStickers;
                    return this._container;
                }
                onRemove() {
                    this._container.parentNode.removeChild(this._container);
                    this._map = undefined;
                }
            }
            map.addControl(new RefreshControl());

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

            mapInitialized = true;
        } catch (error) {
            console.error('Error initializing map:', error);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeMap);
    } else {
        initializeMap();
    }

    document.addEventListener('livewire:navigated', initializeMap);
</script>
