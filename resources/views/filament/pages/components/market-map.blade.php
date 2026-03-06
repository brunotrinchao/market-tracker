@php
    $mapId = 'map-' . uniqid();
@endphp

<div class="p-4" wire:ignore>
    <style>
        .market-map-modal-layout {
            position: relative;
            height: calc(100dvh - 14rem);
            min-height: 420px;
        }

        .market-map-overlay-list {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 25%;
            min-width: 260px;
            max-width: 360px;
            height: calc(100% - 24px);
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.88);
            padding: 8px;
            z-index: 500;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
        }

        .market-map-overlay-toggle {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 510;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #111827;
            border-radius: 10px;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-size: 18px;
            font-weight: 700;
            line-height: 1;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.15);
            cursor: pointer;
        }

        .market-map-location-target {
            position: absolute;
            left: 12px;
            bottom: 12px;
            z-index: 510;
            width: 44px;
            height: 44px;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #1f2937;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.15);
            cursor: pointer;
            transition: transform .12s ease, box-shadow .12s ease, background-color .12s ease;
        }

        .market-map-location-target:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.18);
            background: #f9fafb;
        }

        .market-map-location-target:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        @keyframes market-map-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 1023px) {
            .market-map-overlay-list {
                display: none;
            }

            .market-map-overlay-toggle {
                display: none;
            }
        }
    </style>

    <div
        x-data="{
            markets: @js($markets),
            map: null,
            markersByMarketId: {},
            selectedMarketId: null,
            mapId: '{{ $mapId }}',
            defaultCenter: [-19.939, -44.006],
            defaultZoom: 13,
            resolvedMarkets: [],
            userLatLng: null,
            userLocationError: null,
            requestingUserLocation: false,
            userLocationMarker: null,
            isMarketsListCollapsed: false,
            baseLayerControl: null,
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

                    this.sortMarketsByDistance();
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
                this.resolvedMarkets.sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'pt-BR'));
            },
            toRadians(value) {
                return (Number(value) * Math.PI) / 180;
            },
            distanceInKm(lat1, lng1, lat2, lng2) {
                const earthRadiusKm = 6371;
                const dLat = this.toRadians(lat2 - lat1);
                const dLng = this.toRadians(lng2 - lng1);
                const a =
                    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(this.toRadians(lat1)) * Math.cos(this.toRadians(lat2)) *
                    Math.sin(dLng / 2) * Math.sin(dLng / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

                return earthRadiusKm * c;
            },
            marketDistanceLabel(market) {
                if (!this.userLatLng || market.lat === null || market.lng === null) {
                    return null;
                }

                const distance = this.distanceInKm(
                    this.userLatLng[0],
                    this.userLatLng[1],
                    Number(market.lat),
                    Number(market.lng),
                );

                if (!isFinite(distance)) {
                    return null;
                }

                return `${distance.toFixed(1).replace('.', ',')} km`;
            },
            sortMarketsByDistance() {
                if (!this.userLatLng) {
                    this.resolvedMarkets.sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), 'pt-BR'));
                    return;
                }

                this.resolvedMarkets.sort((a, b) => {
                    const distanceA = this.marketDistanceLabel(a);
                    const distanceB = this.marketDistanceLabel(b);

                    if (distanceA === null && distanceB === null) {
                        return String(a.name || '').localeCompare(String(b.name || ''), 'pt-BR');
                    }

                    if (distanceA === null) return 1;
                    if (distanceB === null) return -1;

                    const valueA = Number(distanceA.replace(' km', '').replace(',', '.'));
                    const valueB = Number(distanceB.replace(' km', '').replace(',', '.'));

                    return valueA - valueB;
                });
            },
            marketInitials(name) {
                const words = String(name || '').trim().split(/\s+/).filter(Boolean);

                if (words.length >= 2) {
                    return (words[0][0] + words[1][0]).toUpperCase();
                }

                return String(words[0] || 'MC').slice(0, 2).toUpperCase();
            },
            focusMarket(marketId, zoom = 16) {
                const market = this.resolvedMarkets.find((item) => Number(item.id) === Number(marketId));
                if (!market || !this.map) {
                    return;
                }

                this.selectedMarketId = Number(market.id);
                this.map.setView([market.lat, market.lng], zoom, { animate: true });

                const marker = this.markersByMarketId[this.selectedMarketId];
                if (marker) {
                    marker.openPopup();
                }
            },
            initMap() {
                const setup = async () => {
                    const element = document.getElementById(this.mapId);

                    if (!element || typeof window.L === 'undefined') {
                        return;
                    }

                    if (this.map) {
                        this.map.remove();
                        this.baseLayerControl = null;
                    }

                    this.map = window.L.map(this.mapId).setView(this.defaultCenter, this.defaultZoom);

                    const defaultLayer = window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    });

                    const satelliteLayer = window.L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        attribution: 'Tiles &copy; Esri'
                    });

                    defaultLayer.addTo(this.map);

                    this.baseLayerControl = window.L.control.layers(
                        {
                            '🗺️ Padrão': defaultLayer,
                            '🛰️ Satélite': satelliteLayer,
                        },
                        {},
                        {
                            position: 'topleft',
                            collapsed: true,
                        }
                    ).addTo(this.map);

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

                        this.markersByMarketId[Number(market.id)] = window.L.marker([market.lat, market.lng], markerOptions)
                            .addTo(this.map)
                            .bindPopup(popupHtml)
                            .on('click', () => {
                                this.selectedMarketId = Number(market.id);
                            });
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

        <div class="market-map-modal-layout">
            <div id="{{ $mapId }}" style="height: 100%; border-radius: 12px;"></div>

            <button
                type="button"
                class="market-map-overlay-toggle"
                @click="isMarketsListCollapsed = !isMarketsListCollapsed"
                :aria-label="isMarketsListCollapsed ? 'Mostrar mercados' : 'Recolher mercados'"
                :title="isMarketsListCollapsed ? 'Mostrar mercados' : 'Recolher mercados'"
                x-text="isMarketsListCollapsed ? '◀' : '▶'"
            ></button>

            <div class="market-map-overlay-list" x-show="!isMarketsListCollapsed" x-transition.opacity.duration.150ms>
                <p style="padding:0 8px 8px 8px;font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#6b7280;display:flex;align-items:center;gap:6px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 22S5 16.5 5 10.5A7 7 0 1 1 19 10.5C19 16.5 12 22 12 22Z" stroke="currentColor" stroke-width="1.8"></path>
                        <circle cx="12" cy="10.5" r="2.5" stroke="currentColor" stroke-width="1.8"></circle>
                    </svg>
                    Mercados cadastrados
                </p>

                <template x-if="resolvedMarkets.length === 0">
                    <p style="padding:0 8px;font-size:14px;color:#6b7280;display:flex;align-items:center;gap:6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"></circle>
                            <path d="M12 8V13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                            <circle cx="12" cy="16.5" r="1" fill="currentColor"></circle>
                        </svg>
                        Nenhum mercado com coordenadas encontrado.
                    </p>
                </template>

                <div style="display:grid;gap:8px;">
                    <template x-for="(market, index) in resolvedMarkets" :key="market.id">
                        <button
                            type="button"
                            @click="focusMarket(market.id)"
                            style="width:100%;padding:12px 8px;text-align:left;cursor:pointer;transition:all .15s ease;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;"
                            :style="Number(selectedMarketId) === Number(market.id)
                                ? 'background:#eff6ff;border-color:#bfdbfe;'
                                : 'background:#ffffff;border-color:#e5e7eb;'"
                        >
                            <div style="display:grid;grid-template-columns:56px minmax(0,1fr);gap:10px;align-items:center;">
                                <div style="display:flex;align-items:center;justify-content:center;">
                                    <template x-if="market.image">
                                        <img :src="market.image" :alt="market.name" style="width:52px;height:52px;border-radius:10px;object-fit:cover;border:1px solid #e5e7eb;" />
                                    </template>
                                    <template x-if="!market.image">
                                        <div style="width:52px;height:52px;border-radius:10px;background:#e5e7eb;color:#374151;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;" x-text="marketInitials(market.name)"></div>
                                    </template>
                                </div>

                                <div style="min-width:0;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                                        <div style="font-size:14px;font-weight:600;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" x-text="market.name"></div>
                                        <div style="font-size:12px;font-weight:700;color:#2563eb;white-space:nowrap;display:flex;align-items:center;gap:4px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M12 22S5 16.5 5 10.5A7 7 0 1 1 19 10.5C19 16.5 12 22 12 22Z" stroke="currentColor" stroke-width="1.8"></path>
                                                <circle cx="12" cy="10.5" r="2.5" fill="currentColor"></circle>
                                            </svg>
                                            <span x-text="marketDistanceLabel(market) ?? '-'"></span>
                                        </div>
                                    </div>
                                    <div style="font-size:12px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:flex;align-items:center;gap:4px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M12 22S5 16.5 5 10.5A7 7 0 1 1 19 10.5C19 16.5 12 22 12 22Z" stroke="currentColor" stroke-width="1.8"></path>
                                            <circle cx="12" cy="10.5" r="2.5" stroke="currentColor" stroke-width="1.8"></circle>
                                        </svg>
                                        <span x-text="market.address"></span>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            <button
                type="button"
                class="market-map-location-target"
                @click="requestUserLocation()"
                :disabled="requestingUserLocation"
                :aria-label="requestingUserLocation ? 'Obtendo localização...' : 'Usar minha localização'"
                :title="requestingUserLocation ? 'Obtendo localização...' : 'Usar minha localização'"
            >
                <svg x-show="!requestingUserLocation" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.8"></circle>
                    <path d="M12 3V6M12 18V21M3 12H6M18 12H21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                </svg>
                <svg x-show="requestingUserLocation" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="animation: market-map-spin 1s linear infinite;">
                    <path d="M21 12a9 9 0 1 1-3.1-6.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                </svg>
            </button>
        </div>

        <p x-show="userLocationError" x-text="userLocationError" class="mt-3 text-sm text-amber-600"></p>
    </div>
</div>
