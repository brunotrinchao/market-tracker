@php
    $mapId = 'map-' . uniqid();
@endphp

<div class="p-4" wire:ignore>
    <div
        x-data="{
            markets: @js($markets),
            map: null,
            mapId: '{{ $mapId }}',
            defaultCenter: [-19.939, -44.006],
            defaultZoom: 13,
            resolvedMarkets: [],
            userLatLng: null,
            userLocationError: null,
            requestingUserLocation: false,
            userLocationMarker: null,
            isSecureOrigin() {
                const host = window.location.hostname;
                return window.isSecureContext || host === 'localhost' || host === '127.0.0.1';
            },
            getCurrentPosition() {
                return new Promise((resolve, reject) => {
                    if (!navigator.geolocation) {
                        reject(new Error('Geolocalização não suportada.'));
                        return;
                    }

                    navigator.geolocation.getCurrentPosition(
                        (position) => resolve(position),
                        (error) => reject(error),
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 60000,
                        }
                    );
                });
            },
            normalizeLocationError(error) {
                if (!error) {
                    return 'Não foi possível obter sua localização.';
                }

                if (error.code === 1) {
                    return 'Permissão de localização negada. Toque em Usar minha localização e permita o acesso.';
                }

                if (error.code === 2) {
                    return 'Não foi possível determinar sua localização no momento.';
                }

                if (error.code === 3) {
                    return 'Tempo esgotado ao tentar obter sua localização.';
                }

                return error && error.message ? error.message : 'Não foi possível obter sua localização.';
            },
            drawUserLocationMarker() {
                if (!this.map || !this.userLatLng) {
                    return;
                }

                if (this.userLocationMarker) {
                    this.userLocationMarker.remove();
                }

                this.userLocationMarker = window.L.circleMarker(this.userLatLng, {
                    radius: 8,
                    color: '#2563eb',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.95,
                })
                    .addTo(this.map)
                    .bindPopup('Sua localização aproximada');
            },
            async requestUserLocation() {
                this.userLocationError = null;

                if (!navigator.geolocation) {
                    this.userLocationError = 'Geolocalização não suportada neste dispositivo.';
                    return;
                }

                if (!this.isSecureOrigin()) {
                    this.userLocationError = 'Para usar geolocalização no celular, acesse por HTTPS.';
                    return;
                }

                this.requestingUserLocation = true;

                try {
                    const position = await this.getCurrentPosition();

                    this.userLatLng = [
                        position.coords.latitude,
                        position.coords.longitude,
                    ];
                    this.defaultCenter = this.userLatLng;
                    this.defaultZoom = 14;

                    this.drawUserLocationMarker();

                    if (this.map) {
                        this.map.setView(this.userLatLng, 14);
                    }
                } catch (error) {
                    this.userLocationError = this.normalizeLocationError(error);
                } finally {
                    this.requestingUserLocation = false;
                }
            },
            geocodeAddress(market) {

                if (!market || !market.address) {
                    return Promise.resolve(null);
                }

                const query = `${market.address}`;
                const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`;

                return fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                    },
                })
                    .then((response) => response.ok ? response.json() : [])
                    .then((results) => {
                        if (!Array.isArray(results) || results.length === 0) {
                            return null;
                        }

                        return {
                            lat: Number(results[0].lat),
                            lng: Number(results[0].lon),
                        };
                    })
                    .catch(() => null);
            },
            async resolveMarketCoordinates() {
                this.resolvedMarkets = await Promise.all(
                
                    this.markets.map(async (market) => {
                        if (market.lat !== null && market.lng !== null) {
                            return market;
                        }

                        const geo = await this.geocodeAddress(market);

                        if (!geo) {
                            return null;
                        }

                        return {
                            ...market,
                            lat: geo.lat,
                            lng: geo.lng,
                        };
                    })
                );

                this.resolvedMarkets = this.resolvedMarkets.filter((market) => market && market.lat !== null && market.lng !== null);
            },
            initMap() {
                const setup = async () => {
                    const element = document.getElementById(this.mapId);

                    if (!element || typeof window.L === 'undefined') {
                        return;
                    }

                    if (this.map) {
                        this.map.remove();
                    }

                    this.map = window.L.map(this.mapId).setView(this.defaultCenter, this.defaultZoom);

                    window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(this.map);

                    await this.resolveMarketCoordinates();

                    this.resolvedMarkets.forEach((market) => {
                        const markerOptions = {};

                        if (market.image) {
                            markerOptions.icon = window.L.divIcon({
                                className: 'market-pin-icon',
                                html: '<div style=\'width:40px;height:40px;border:2px solid #fff;border-radius:5px;box-shadow:0 2px 8px rgba(0,0,0,.25);overflow:hidden;background:#fff;\'><img src=\'' + market.image + '\' alt=\'' + market.name + '\' style=\'width:100%;height:100%;object-fit:cover;\' /></div>',
                                iconSize: [40, 40],
                                iconAnchor: [20, 40],
                                popupAnchor: [0, -36],
                            });
                        }

                        const popupLogo = market.image
                            ? '<img src=\'' + market.image + '\' alt=\'' + market.name + '\' style=\'width:100%;height:48px;border-radius:10px;object-fit:cover;display:block;\' />'
                            : '<div style=\'width:auto;height:48px;border-radius:10px;background:#e5e7eb;display:block;\'></div>';
                        const marketPageButton = market.resource_url
                            ? '<a href=\'' + market.resource_url + '\' target=\'_self\' rel=\'noopener noreferrer\' style=\'display:inline-flex;align-items:center;justify-content:center;margin-top:8px;padding:6px 10px;border-radius:8px;background:#2563eb;color:#fff;text-decoration:none;font-size:12px;font-weight:600;\'>Ir para o supermercado</a>'
                            : '';
                        const popupHtml =
                            '<div style=\'display:grid;grid-template-columns:4fr 8fr;gap:10px;align-items:center;min-width:240px;\'>' +
                                '<div style=\'display:flex;justify-content:center;\'>' + popupLogo + '</div>' +
                                '<div>' +
                                    '<div style=\'font-weight:600;line-height:1.2;\'>' + market.name + '</div>' +
                                    '<div style=\'font-size:12px;color:#4b5563;line-height:1.35;margin-top:2px;\'>' + market.address + '</div>' +
                                    marketPageButton +
                                '</div>' +
                            '</div>';

                        window.L.marker([market.lat, market.lng], markerOptions)
                            .addTo(this.map)
                            .bindPopup(popupHtml);
                    });

                    if (this.resolvedMarkets.length > 0 && !this.userLatLng) {
                        const bounds = window.L.latLngBounds(this.resolvedMarkets.map((market) => [market.lat, market.lng]));
                        this.map.fitBounds(bounds, { padding: [20, 20] });
                    }

                    if (this.userLatLng) {
                        this.drawUserLocationMarker();
                        this.map.setView(this.userLatLng, 14);
                    }

                    setTimeout(() => this.map.invalidateSize(), 150);
                };

                const boot = () => {
                    setup().finally(() => this.requestUserLocation());
                };

                if (typeof window.L !== 'undefined') {
                    boot();
                    return;
                }

                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.onload = () => boot();
                document.head.appendChild(script);
            }
        }"
        x-init="initMap()"
    >
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

        <div id="{{ $mapId }}" style="height: 500px; border-radius: 12px;"></div>

        <div class="mt-3">
            <button
                type="button"
                @click="requestUserLocation()"
                :disabled="requestingUserLocation"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span x-show="!requestingUserLocation">Usar minha localização</span>
                <span x-show="requestingUserLocation">Obtendo localização...</span>
            </button>
        </div>

        <p x-show="userLocationError" x-text="userLocationError" class="mt-3 text-sm text-amber-600"></p>

        @if (empty($markets) || count($markets) === 0)
            <p class="mt-3 text-sm text-gray-500">Nenhum mercado com coordenadas encontrado.</p>
        @endif
    </div>
</div>
