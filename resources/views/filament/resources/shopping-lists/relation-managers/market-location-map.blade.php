@php
    $mapId = 'shopping-list-market-map-' . uniqid();
@endphp

<div
    x-data="{
        mapId: '{{ $mapId }}',
        market: @js($market),
        map: null,
        marketMarker: null,
        userMarker: null,
        userLocationError: null,
        requestingUserLocation: false,
        defaultCenter: [-19.939, -44.006],
        defaultZoom: 12,
        getCurrentPosition() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Geolocalização não suportada.'));
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => resolve(position),
                    (error) => reject(error),
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                );
            });
        },
        normalizeLocationError(error) {
            if (!error) {
                return 'Não foi possível obter sua localização.';
            }

            if (error.code === 1) {
                return 'Permissão de localização negada.';
            }

            if (error.code === 2) {
                return 'Não foi possível determinar sua localização no momento.';
            }

            if (error.code === 3) {
                return 'Tempo esgotado ao tentar obter sua localização.';
            }

            return error.message ? error.message : 'Não foi possível obter sua localização.';
        },
        addMarketMarker() {
            if (!this.map || !this.market || this.market.lat === null || this.market.lng === null) {
                return;
            }

            this.marketMarker = window.L.marker([this.market.lat, this.market.lng])
                .addTo(this.map)
                .bindPopup((this.market.name || 'Supermercado') + (this.market.address ? '<br>' + this.market.address : ''));
        },
        addUserMarker(lat, lng) {
            if (!this.map) {
                return;
            }

            if (this.userMarker) {
                this.userMarker.remove();
            }

            this.userMarker = window.L.circleMarker([lat, lng], {
                radius: 8,
                color: '#2563eb',
                fillColor: '#3b82f6',
                fillOpacity: 0.95,
            }).addTo(this.map).bindPopup('Sua localização aproximada');
        },
        async locateUser() {
            this.userLocationError = null;
            this.requestingUserLocation = true;

            try {
                const position = await this.getCurrentPosition();
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                this.addUserMarker(lat, lng);

                if (this.market && this.market.lat !== null && this.market.lng !== null) {
                    const bounds = window.L.latLngBounds([
                        [lat, lng],
                        [this.market.lat, this.market.lng],
                    ]);
                    this.map.fitBounds(bounds, { padding: [30, 30] });
                } else {
                    this.map.setView([lat, lng], 14);
                }
            } catch (error) {
                this.userLocationError = this.normalizeLocationError(error);
            } finally {
                this.requestingUserLocation = false;
            }
        },
        initMap() {
            const setup = () => {
                const element = document.getElementById(this.mapId);
                if (!element || typeof window.L === 'undefined') {
                    return;
                }

                if (this.map) {
                    this.map.remove();
                }

                let center = this.defaultCenter;
                let zoom = this.defaultZoom;

                if (this.market && this.market.lat !== null && this.market.lng !== null) {
                    center = [this.market.lat, this.market.lng];
                    zoom = 15;
                }

                this.map = window.L.map(this.mapId).setView(center, zoom);

                window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(this.map);

                this.addMarketMarker();
                this.locateUser();

                setTimeout(() => this.map.invalidateSize(), 150);
            };

            if (typeof window.L !== 'undefined') {
                setup();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.onload = () => setup();
            document.head.appendChild(script);
        },
    }"
    x-init="initMap()"
    class="space-y-3"
>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm">
        <div class="font-semibold text-gray-900" x-text="market.name || 'Supermercado'"></div>
        <div class="text-gray-600" x-text="market.address || 'Endereço não disponível.'"></div>
    </div>

    <div id="{{ $mapId }}" style="height: 380px; border-radius: 12px;"></div>

    <div class="flex items-center gap-2">
        <button
            type="button"
            @click="locateUser()"
            :disabled="requestingUserLocation"
            class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
        >
            <span x-show="!requestingUserLocation">Atualizar minha localização</span>
            <span x-show="requestingUserLocation">Obtendo localização...</span>
        </button>
    </div>

    <p x-show="userLocationError" x-text="userLocationError" class="text-sm text-amber-600"></p>
    <p x-show="market.lat === null || market.lng === null" class="text-sm text-amber-600">
        Este supermercado não possui coordenadas cadastradas.
    </p>
</div>
