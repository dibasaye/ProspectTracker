<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-bold">
            <i class="fas fa-map me-2"></i>Détails du site : {{ $site->name }}
        </h2>
    </x-slot>

    <div class="container-fluid py-4">
        <div class="row g-4 flex-column-reverse flex-lg-row">

            {{-- Informations principales --}}
            <div class="{{ $site->latitude && $site->longitude ? 'col-12 col-lg-7' : 'col-12' }}">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Informations principales</h5>
                        <div class="row">
                            <div class="col-12 col-md-6"><p><strong>Localisation :</strong> {{ $site->location }}</p></div>
                            <div class="col-12 col-md-6"><p><strong>Superficie :</strong> {{ $site->total_area ?? '-' }} m²</p></div>
                            <div class="col-12 col-md-6"><p><strong>Nombre total de lots :</strong> {{ $site->total_lots ?? '-' }}</p></div>
                            <div class="col-12 col-md-6"><p><strong>Date de lancement :</strong> {{ $site->launch_date ? $site->launch_date->format('d/m/Y') : '-' }}</p></div>
                            <div class="col-12 col-md-6"><p><strong>Frais de réservation :</strong> {{ number_format($site->reservation_fee, 0, ',', ' ') }} FCFA</p></div>
                            <div class="col-12 col-md-6"><p><strong>Frais d'adhésion :</strong> {{ number_format($site->membership_fee, 0, ',', ' ') }} FCFA</p></div>
                            @if($site->latitude && $site->longitude)
                                <div class="col-12 col-md-6"><p><strong>Latitude :</strong> {{ $site->latitude }}</p></div>
                                <div class="col-12 col-md-6"><p><strong>Longitude :</strong> {{ $site->longitude }}</p></div>
                            @endif
                        </div>
                        <p><strong>Description :</strong><br>{{ $site->description ?? '-' }}</p>
                    </div>
                </div>

                {{-- Tarifs par position --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Tarifs par position de lot</h5>
                        <div class="row">
                            <div class="col-12 col-md-4">
                                <div class="card bg-light mb-3">
                                    <div class="card-body text-center">
                                        <h6 class="card-title text-success">Lots en angle</h6>
                                        <p class="card-text fw-bold">{{ number_format($site->angle_price, 0, ',', ' ') }} FCFA</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="card bg-light mb-3">
                                    <div class="card-body text-center">
                                        <h6 class="card-title text-warning">Lots en façade</h6>
                                        <p class="card-text fw-bold">{{ number_format($site->facade_price, 0, ',', ' ') }} FCFA</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="card bg-light mb-3">
                                    <div class="card-body text-center">
                                        <h6 class="card-title text-info">Lots intérieurs</h6>
                                        <p class="card-text fw-bold">{{ number_format($site->interior_price, 0, ',', ' ') }} FCFA</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Options de paiement --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Options de paiement disponibles</h5>
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-{{ $site->enable_payment_cash ? 'success' : 'secondary' }} me-2">
                                        <i class="fas fa-{{ $site->enable_payment_cash ? 'check' : 'times' }}"></i>
                                    </span>
                                    <span>Paiement comptant</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-{{ $site->enable_payment_1_year ? 'success' : 'secondary' }} me-2">
                                        <i class="fas fa-{{ $site->enable_payment_1_year ? 'check' : 'times' }}"></i>
                                    </span>
                                    <span>Paiement sur 1 an (+5%)</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-{{ $site->enable_payment_2_years ? 'success' : 'secondary' }} me-2">
                                        <i class="fas fa-{{ $site->enable_payment_2_years ? 'check' : 'times' }}"></i>
                                    </span>
                                    <span>Paiement sur 2 ans (+10%)</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-{{ $site->enable_payment_3_years ? 'success' : 'secondary' }} me-2">
                                        <i class="fas fa-{{ $site->enable_payment_3_years ? 'check' : 'times' }}"></i>
                                    </span>
                                    <span>Paiement sur 3 ans (+15%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Plans de paiement --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Plans de paiement</h5>
                        <ul class="list-group">
                            @if($site->enable_12 && $site->price_12_months)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    12 mois
                                    <span class="badge bg-primary rounded-pill">
                                        {{ number_format($site->price_12_months, 0, ',', ' ') }} FCFA
                                    </span>
                                </li>
                            @endif

                            @if($site->enable_24 && $site->price_24_months)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    24 mois
                                    <span class="badge bg-primary rounded-pill">
                                        {{ number_format($site->price_24_months, 0, ',', ' ') }} FCFA
                                    </span>
                                </li>
                            @endif

                            @if($site->enable_36 && $site->price_36_months)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    36 mois
                                    <span class="badge bg-primary rounded-pill">
                                        {{ number_format($site->price_36_months, 0, ',', ' ') }} FCFA
                                    </span>
                                </li>
                            @endif

                            @if($site->enable_cash && $site->price_cash)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Paiement cash
                                    <span class="badge bg-success rounded-pill">
                                        {{ number_format($site->price_cash, 0, ',', ' ') }} FCFA
                                    </span>
                                </li>
                            @endif

                            @if(
                                !($site->enable_12 && $site->price_12_months) && 
                                !($site->enable_24 && $site->price_24_months) &&
                                !($site->enable_36 && $site->price_36_months) && 
                                !($site->enable_cash && $site->price_cash)
                            )
                                <li class="list-group-item text-muted">Aucun plan de paiement défini</li>
                            @endif
                        </ul>
                    </div>
                </div>

                {{-- Statistiques --}}
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Statistiques</h5>
                        <div class="row">
                            <div class="col-12 col-md-6"><p>Lots totaux : {{ $stats['total_lots'] }}</p></div>
                            <div class="col-12 col-md-6"><p>Disponibles : {{ $stats['available_lots'] }}</p></div>
                            <div class="col-12 col-md-6"><p>Réservés : {{ $stats['reserved_lots'] }}</p></div>
                            <div class="col-12 col-md-6"><p>Vendus : {{ $stats['sold_lots'] }}</p></div>
                            <div class="col-12 col-md-6"><p>Prospects intéressés : {{ $stats['total_prospects'] }}</p></div>
                            <div class="col-12 col-md-6"><p>Revenus générés : {{ number_format($stats['total_revenue'], 0, ',', ' ') }} FCFA</p></div>
                        </div>
                    </div>
                </div>

                {{-- Affichage du plan du lotissement (image ou PDF) --}}
                @if($site->image_url)
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Plan du lotissement</h5>
                        @if($isPdf)
                            <iframe src="{{ asset('storage/' . $site->image_url) }}" width="100%" height="500px" style="border: none;"></iframe>
                        @else
                            <img 
                                src="{{ asset('storage/' . $site->image_url) }}" 
                                alt="Plan du lotissement" 
                                style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.2);"
                            >
                        @endif
                    </div>
                </div>
                @endif

                <div class="d-flex gap-2 mb-4">
                    <a href="{{ route('sites.edit', $site) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-1"></i> Modifier le site
                    </a>
                    <a href="{{ route('sites.lots', $site) }}" class="btn btn-primary">
                        <i class="fas fa-list me-1"></i> Gérer les lots
                    </a>
                </div>
            </div>

            {{-- Carte Leaflet --}}
            @if($site->latitude && $site->longitude)
            <div class="col-12 col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column" style="min-height: 350px;">
                        <h5 class="card-title">Carte de localisation</h5>
                        <div id="map" class="rounded shadow-sm flex-grow-1" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- Leaflet.js CSS & JS --}}
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <style>
            .card {
                border: none;
                border-radius: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            
            .card-header {
                border-radius: 10px 10px 0 0 !important;
            }
            
            .badge {
                font-size: 0.8em;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        @if($site->latitude && $site->longitude)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const map = L.map('map', {
                    center: [{{ $site->latitude }}, {{ $site->longitude }}],
                    zoom: 15,
                    scrollWheelZoom: false,
                });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(map);

                L.marker([{{ $site->latitude }}, {{ $site->longitude }}])
                    .addTo(map)
                    .bindPopup("<b>{{ addslashes($site->name) }}</b><br>{{ addslashes($site->location) }}")
                    .openPopup();
            });
        </script>
        @endif
    @endpush
</x-app-layout>