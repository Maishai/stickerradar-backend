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
    <script src="https://unpkg.com/supercluster@7.1.5/dist/supercluster.min.js"></script>
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

    .cluster-marker {
        background: #ff6b6b;
        border-radius: 50%;
        color: white;
        height: 40px;
        width: 40px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-weight: bold;
        font-size: 14px;
        border: 3px solid white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    }

    #map-loading {
        position: absolute;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        background: white;
        padding: 8px 15px;
        border-radius: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        z-index: 100;
        font-family: 'Instrument Sans', sans-serif;
        font-size: 14px;
        font-weight: 500;
        color: #333;
        display: flex;
        align-items: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
    }

    #map-loading.visible {
        opacity: 1;
    }

    #map-loading::before {
        content: '';
        width: 16px;
        height: 16px;
        margin-right: 8px;
        border: 2px solid #ddd;
        border-top-color: #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
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

    .dark-mode #map-loading {
        background: #2D3748;
        color: #CBD5E0;
    }
</style>

<script>
    function initializeMap() {
        if (window.location.pathname !== '{{ request()->path() === '/' ? '/' : '/' . request()->path() }}') {
            return;
        }

        try {
            const StickerMap = class {
                constructor() {
                    this.CONFIG = {
                        defaultCenter: [8.690206837689823, 48.44363437574163],
                        defaultZoom: 15,
                        clusterRadius: 60,
                        clusterMaxZoom: 16,
                        individualMarkerZoomThreshold: 15,
                        minLoadingZoom: 6,
                        baseQuadrantSize: 0.01,
                        maxQuadrantsPerLoad: 15,
                        loadDebounceTime: 300,
                        darkModeStyle: 'https://tiles.versatiles.org/assets/styles/eclipse/style.json',
                        lightModeStyle: 'https://tiles.versatiles.org/assets/styles/colorful/style.json',
                        defaultTagColor: '#888'
                    };

                    this.map = null;
                    this.markers = {};
                    this.clusterMarkers = {};
                    this.tagsById = {};
                    this.isLoading = false;
                    this.stickersData = [];
                    this.loadedQuadrants = new Set();
                    this.loadTimeout = null;
                    this.supercluster = null;
                    this.mapInitialized = false;

                    this.mapContainer = document.getElementById('map');
                    this.loadingIndicator = this._createLoadingIndicator();

                    this.initialize();
                }

                initialize() {
                    this.map = new maplibregl.Map({
                        container: 'map',
                        style: this._getDarkModeStyle(),
                        center: this.CONFIG.defaultCenter,
                        zoom: this.CONFIG.defaultZoom
                    });

                    this._setupMapControls();
                    this._setupEventListeners();

                    this.supercluster = new Supercluster({
                        radius: this.CONFIG.clusterRadius,
                        maxZoom: this.CONFIG.clusterMaxZoom
                    });

                    this._loadTags()
                        .then(() => {
                            this.map.on('load', () => this._loadStickersInView());
                        })
                        .catch(error => {
                            console.error('Failed to initialize map data:', error);
                        });
                }

                _setupMapControls() {
                    this.map.addControl(new maplibregl.NavigationControl());
                    this.map.addControl(
                        new maplibregl.GeolocateControl({
                            positionOptions: {
                                enableHighAccuracy: true
                            },
                            trackUserLocation: true,
                            showUserHeading: true
                        })
                    );

                    const RefreshControlClass = this._createRefreshControl();
                    this.map.addControl(new RefreshControlClass());
                }

                _setupEventListeners() {
                    this.map.on('moveend', () => {
                        if (this.loadTimeout) clearTimeout(this.loadTimeout);
                        this.loadTimeout = setTimeout(() => {
                            this._loadStickersInView();
                        }, this.CONFIG.loadDebounceTime);
                    });

                    this.map.on('zoom', () => {
                        this._updateMarkerDisplay();
                    });

                    window.addEventListener('resize', () => {
                        this.map.resize();
                    });

                    this._setupDarkModeListener();
                }

                _setupDarkModeListener() {
                    const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

                    const updateDarkMode = () => {
                        if (darkModeMediaQuery.matches) {
                            document.body.classList.add('dark-mode');
                            this.map.setStyle(this.CONFIG.darkModeStyle);
                        } else {
                            document.body.classList.remove('dark-mode');
                            this.map.setStyle(this.CONFIG.lightModeStyle);
                        }
                    };

                    updateDarkMode();
                    darkModeMediaQuery.addEventListener('change', updateDarkMode);
                }

                _getDarkModeStyle() {
                    return window.matchMedia('(prefers-color-scheme: dark)').matches ?
                        this.CONFIG.darkModeStyle :
                        this.CONFIG.lightModeStyle;
                }

                _createLoadingIndicator() {
                    const indicator = document.createElement('div');
                    indicator.id = 'map-loading';
                    indicator.textContent = 'Loading stickers';
                    this.mapContainer.appendChild(indicator);
                    return indicator;
                }

                _showLoading() {
                    this.loadingIndicator.classList.add('visible');
                }

                _hideLoading() {
                    this.loadingIndicator.classList.remove('visible');
                }

                _loadTags() {
                    return fetch('/api/tags')
                        .then(response => {
                            if (!response.ok) throw new Error('Failed to fetch tags');
                            return response.json();
                        })
                        .then(data => {
                            data.data.forEach(tag => {
                                this.tagsById[tag.id] = tag;
                            });
                            return data;
                        });
                }

                _getQuadrantSizeForZoom(zoom) {
                    if (zoom >= 17) return this.CONFIG.baseQuadrantSize * 0.5;
                    if (zoom >= 14) return this.CONFIG.baseQuadrantSize;
                    if (zoom >= 12) return this.CONFIG.baseQuadrantSize * 2;
                    if (zoom >= 10) return this.CONFIG.baseQuadrantSize * 5;
                    if (zoom >= 8) return this.CONFIG.baseQuadrantSize * 10;
                    return this.CONFIG.baseQuadrantSize * 20;
                }

                _getQuadrantKey(lat, lon, quadrantSize) {
                    const latQuad = Math.floor(lat / quadrantSize);
                    const lonQuad = Math.floor(lon / quadrantSize);
                    return `${latQuad}:${lonQuad}:${quadrantSize}`;
                }

                _createPopupForSticker(sticker) {
                    const popupContent = document.createElement('div');

                    let tagsHtml = '';
                    if (sticker.tags && sticker.tags.length > 0) {
                        sticker.tags.forEach(tagId => {
                            const tag = this.tagsById[tagId];
                            if (tag) {
                                tagsHtml +=
                                    `<span class="tag" style="background-color: ${tag.color};">${tag.name}</span>`;
                            }
                        });
                    }

                    popupContent.innerHTML = `
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <img src="/storage/stickers/thumbnails/${sticker.filename}"
                             style="max-width: 200px; height: auto; margin-bottom: 10px;">
                        <div>${tagsHtml}</div>
                    </div>
                `;

                    return new maplibregl.Popup({
                        offset: 25
                    }).setDOMContent(popupContent);
                }

                _getTagColorForSticker(sticker) {
                    if (sticker.tags && sticker.tags.length > 0) {
                        const firstTagId = sticker.tags[0];
                        if (this.tagsById[firstTagId]) {
                            return this.tagsById[firstTagId].color;
                        }
                    }
                    return this.CONFIG.defaultTagColor;
                }

                _createMarkerForSticker(sticker) {
                    const markerElement = document.createElement('div');
                    markerElement.className = 'map-marker';
                    markerElement.style.backgroundColor = this._getTagColorForSticker(sticker);

                    const marker = new maplibregl.Marker({
                            element: markerElement
                        })
                        .setLngLat([sticker.lon, sticker.lat])
                        .setPopup(this._createPopupForSticker(sticker));

                    this.markers[sticker.id] = marker;
                    return marker;
                }

                _updateMarkerDisplay() {
                    const zoom = this.map.getZoom();
                    const bounds = this.map.getBounds();
                    const boundingBox = [
                        bounds.getWest(),
                        bounds.getSouth(),
                        bounds.getEast(),
                        bounds.getNorth()
                    ];

                    // At high zoom levels, use individual markers
                    if (zoom >= this.CONFIG.individualMarkerZoomThreshold) {
                        this._displayIndividualMarkers(boundingBox);
                    } else {
                        // At lower zoom levels, use clustering
                        this._displayClusteredMarkers(zoom, boundingBox);
                    }
                }

                _displayIndividualMarkers(boundingBox) {
                    // Hide all cluster markers
                    Object.values(this.clusterMarkers).forEach(marker => marker.remove());

                    // Show individual markers that are in view
                    const [west, south, east, north] = boundingBox;

                    this.stickersData.forEach(sticker => {
                        const inBounds = sticker.lon >= west &&
                            sticker.lon <= east &&
                            sticker.lat >= south &&
                            sticker.lat <= north;

                        if (inBounds) {
                            if (!this.markers[sticker.id]) {
                                this._createMarkerForSticker(sticker).addTo(this.map);
                            } else if (!this.markers[sticker.id]._map) {
                                this.markers[sticker.id].addTo(this.map);
                            }
                        } else if (this.markers[sticker.id] && this.markers[sticker.id]._map) {
                            // Remove markers that are out of view
                            this.markers[sticker.id].remove();
                        }
                    });
                }

                _displayClusteredMarkers(zoom, boundingBox) {
                    // Remove individual markers
                    Object.values(this.markers).forEach(marker => {
                        if (marker._map) marker.remove();
                    });

                    // Update cluster data
                    const points = this.stickersData.map(sticker => ({
                        type: 'Feature',
                        properties: {
                            id: sticker.id,
                            tagColor: this._getTagColorForSticker(sticker)
                        },
                        geometry: {
                            type: 'Point',
                            coordinates: [sticker.lon, sticker.lat]
                        }
                    }));

                    this.supercluster.load(points);

                    // Get clusters for current viewport
                    const clusters = this.supercluster.getClusters(boundingBox, Math.floor(zoom));

                    // Remove old cluster markers
                    Object.values(this.clusterMarkers).forEach(marker => marker.remove());
                    this.clusterMarkers = {};

                    // Create new cluster markers
                    clusters.forEach(cluster => {
                        const [longitude, latitude] = cluster.geometry.coordinates;
                        const clusterId = `cluster-${cluster.id || cluster.properties.id}`;

                        // For clustered points
                        if (cluster.properties.cluster) {
                            this._createClusterMarker(cluster, clusterId, longitude, latitude);
                        }
                        // For individual points in low-density areas
                        else {
                            this._createIndividualClusterMarker(cluster, clusterId, longitude,
                                latitude);
                        }
                    });
                }

                _createClusterMarker(cluster, clusterId, longitude, latitude) {
                    const pointCount = cluster.properties.point_count;
                    const clusterElement = document.createElement('div');
                    clusterElement.className = 'cluster-marker';
                    clusterElement.innerHTML = pointCount;

                    const marker = new maplibregl.Marker({
                            element: clusterElement
                        })
                        .setLngLat([longitude, latitude])
                        .addTo(this.map);

                    clusterElement.addEventListener('click', () => {
                        // Zoom in to expand this cluster
                        this.map.flyTo({
                            center: [longitude, latitude],
                            zoom: Math.min(
                                this.supercluster.getClusterExpansionZoom(cluster.id),
                                17
                            )
                        });
                    });

                    this.clusterMarkers[clusterId] = marker;
                }

                _createIndividualClusterMarker(cluster, clusterId, longitude, latitude) {
                    const sticker = this.stickersData.find(s => s.id === cluster.properties.id);
                    if (sticker) {
                        const markerElement = document.createElement('div');
                        markerElement.className = 'map-marker';
                        markerElement.style.backgroundColor = cluster.properties.tagColor;

                        const marker = new maplibregl.Marker({
                                element: markerElement
                            })
                            .setLngLat([longitude, latitude])
                            .setPopup(this._createPopupForSticker(sticker))
                            .addTo(this.map);

                        this.clusterMarkers[clusterId] = marker;
                    }
                }

                _loadStickersInView() {
                    if (this.isLoading) return;

                    const zoom = this.map.getZoom();

                    // Skip loading if zoomed out too far
                    if (zoom < this.CONFIG.minLoadingZoom) {
                        return;
                    }

                    this.isLoading = true;
                    this._showLoading();

                    const bounds = this.map.getBounds();
                    const south = bounds.getSouth();
                    const north = bounds.getNorth();
                    const west = bounds.getWest();
                    const east = bounds.getEast();

                    // Use appropriate quadrant size based on zoom
                    const quadrantSize = this._getQuadrantSizeForZoom(zoom);

                    const quadrantsToLoad = new Set();
                    for (let lat = south; lat <= north; lat += quadrantSize) {
                        for (let lon = west; lon <= east; lon += quadrantSize) {
                            quadrantsToLoad.add(this._getQuadrantKey(lat, lon, quadrantSize));
                        }
                    }

                    const newQuadrants = [...quadrantsToLoad].filter(q => !this.loadedQuadrants.has(q));

                    if (newQuadrants.length === 0) {
                        console.log('All quadrants already loaded, updating display');
                        this._updateMarkerDisplay();
                        this.isLoading = false;
                        this._hideLoading();
                        return;
                    }

                    // Limit the number of quadrants loaded at once
                    const quadrantsToFetch = newQuadrants.slice(0, this.CONFIG.maxQuadrantsPerLoad);
                    quadrantsToFetch.forEach(q => this.loadedQuadrants.add(q));

                    console.log(
                        `Loading ${quadrantsToFetch.length} new quadrants at zoom ${zoom.toFixed(1)} ` +
                        `(${newQuadrants.length - quadrantsToFetch.length} deferred)`
                    );

                    this._fetchStickers(south, north, west, east)
                        .then(newStickers => {
                            if (newStickers.length > 0) {
                                this.stickersData = [...this.stickersData, ...newStickers];
                                console.log(
                                    `Added ${newStickers.length} new stickers, total: ${this.stickersData.length}`
                                );
                            }

                            this._updateMarkerDisplay();
                        })
                        .catch(error => {
                            console.error('Error fetching stickers:', error);
                        })
                        .finally(() => {
                            this.isLoading = false;
                            this._hideLoading();
                        });
                }

                _fetchStickers(south, north, west, east) {
                    const params = {
                        min_lat: south,
                        max_lat: north,
                        min_lon: west,
                        max_lon: east
                    };

                    const queryString = Object.keys(params)
                        .map(key => `${key}=${params[key]}`)
                        .join('&');

                    return fetch(`/api/stickers?${queryString}`)
                        .then(response => {
                            if (!response.ok) throw new Error('Failed to fetch stickers');
                            return response.json();
                        })
                        .then(data => {
                            return data.data.filter(sticker =>
                                !this.stickersData.some(s => s.id === sticker.id)
                            );
                        });
                }

                refreshAllStickers() {
                    this.loadedQuadrants.clear();
                    this.stickersData = [];

                    Object.values(this.markers).forEach(marker => marker.remove());
                    Object.values(this.clusterMarkers).forEach(marker => marker.remove());

                    this.markers = {};
                    this.clusterMarkers = {};

                    this._loadStickersInView();
                }

                _createRefreshControl() {
                    const self = this;

                    return class RefreshControl {
                        onAdd(map) {
                            this._map = map;
                            this._container = document.createElement('div');
                            this._container.className =
                                'maplibregl-ctrl maplibregl-ctrl-group refresh-control';

                            const button = document.createElement('button');
                            button.className = 'flex items-center justify-center w-8 h-8';
                            button.title = "Refresh Stickers";

                            const span = document.createElement('span');
                            span.className = 'text-lg text-neutral-700 dark:text-neutral-800';
                            span.textContent = '↻';

                            button.appendChild(span);
                            this._container.appendChild(button);
                            this._container.onclick = () => self.refreshAllStickers();
                            return this._container;
                        }

                        onRemove() {
                            this._container.parentNode.removeChild(this._container);
                            this._map = undefined;
                        }
                    };
                }
            };

            const stickerMap = new StickerMap();

            return stickerMap;
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
