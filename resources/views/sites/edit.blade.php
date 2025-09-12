<x-app-layout> 
    <x-slot name="header">
        <h2 class="h4 fw-bold">
            <i class="fas fa-edit me-2"></i>Modifier le Site : {{ $site->name }}
        </h2>
    </x-slot>

    <div class="container py-4">
        <!-- Messages de statut -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        <!-- Messages de validation -->
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('sites.update', $site->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Infos générales -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations générales</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label required-field">Nom du site</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $site->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="location" class="form-label required-field">Localisation</label>
                            <input type="text" name="location" id="location" class="form-control @error('location') is-invalid @enderror" value="{{ old('location', $site->location) }}" required>
                            @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $site->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="total_area" class="form-label required-field">Superficie Totale (m²)</label>
                            <input type="number" step="0.01" name="total_area" id="total_area" class="form-control @error('total_area') is-invalid @enderror" value="{{ old('total_area', $site->total_area) }}" required>
                            @error('total_area')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="total_lots" class="form-label required-field">Nombre total de lots</label>
                            <input type="number" name="total_lots" id="total_lots" class="form-control @error('total_lots') is-invalid @enderror" value="{{ old('total_lots', $site->total_lots) }}" required>
                            @error('total_lots')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="launch_date" class="form-label">Date de lancement</label>
                            <input type="date" name="launch_date" id="launch_date" class="form-control @error('launch_date') is-invalid @enderror" value="{{ old('launch_date', $site->launch_date ? $site->launch_date->format('Y-m-d') : '') }}">
                            @error('launch_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="text" name="latitude" id="latitude" class="form-control @error('latitude') is-invalid @enderror" value="{{ old('latitude', $site->latitude) }}" placeholder="Ex: 14.6928">
                            @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="text" name="longitude" id="longitude" class="form-control @error('longitude') is-invalid @enderror" value="{{ old('longitude', $site->longitude) }}" placeholder="Ex: -17.4467">
                            @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarification -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Tarification</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="reservation_fee" class="form-label required-field">Frais de réservation (FCFA)</label>
                            <input type="number" step="0.01" name="reservation_fee" id="reservation_fee" class="form-control @error('reservation_fee') is-invalid @enderror" value="{{ old('reservation_fee', $site->reservation_fee) }}" required>
                            @error('reservation_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="membership_fee" class="form-label required-field">Frais d'adhésion (FCFA)</label>
                            <input type="number" step="0.01" name="membership_fee" id="membership_fee" class="form-control @error('membership_fee') is-invalid @enderror" value="{{ old('membership_fee', $site->membership_fee) }}" required>
                            @error('membership_fee')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Tarifs par position de lot -->
                    <div class="mt-4 p-3 border rounded bg-light">
                        <h5 class="mb-3 text-primary"><i class="fas fa-map-marked-alt me-2"></i>Tarifs par position de lot</h5>
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold required-field">Prix lots en angle (FCFA)</label>
                                <input type="number" step="0.01" class="form-control @error('price_angle') is-invalid @enderror" name="price_angle" value="{{ old('price_angle', $site->price_angle) }}" placeholder="Ex: 8000000" required>
                                @error('price_angle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Lots situés aux angles du site</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold required-field">Prix lots en façade (FCFA)</label>
                                <input type="number" step="0.01" class="form-control @error('price_facade') is-invalid @enderror" name="price_facade" value="{{ old('price_facade', $site->price_facade) }}" placeholder="Ex: 6000000" required>
                                @error('price_facade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Lots donnant sur la façade principale</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold required-field">Prix lots intérieurs (FCFA)</label>
                                <input type="number" step="0.01" class="form-control @error('price_interieur') is-invalid @enderror" name="price_interieur" value="{{ old('price_interieur', $site->price_interieur) }}" placeholder="Ex: 5000000" required>
                                @error('price_interieur')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Lots situés à l'intérieur du site</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Options de paiement -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Options de paiement</h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="chkCash" name="enable_payment_cash" value="1" {{ old('enable_payment_cash', $site->enable_payment_cash) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-success" for="chkCash">
                            <i class="fas fa-money-bill-wave me-1"></i>Paiement comptant
                        </label>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="chk1Year" name="enable_payment_1_year" value="1" {{ old('enable_payment_1_year', $site->enable_payment_1_year) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-primary" for="chk1Year">
                            <i class="fas fa-calendar-alt me-1"></i>Paiement sur 1 an (+5%)
                        </label>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="chk2Years" name="enable_payment_2_years" value="1" {{ old('enable_payment_2_years', $site->enable_payment_2_years) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-warning" for="chk2Years">
                            <i class="fas fa-calendar-alt me-1"></i>Paiement sur 2 ans (+10%)
                        </label>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="chk3Years" name="enable_payment_3_years" value="1" {{ old('enable_payment_3_years', $site->enable_payment_3_years) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-danger" for="chk3Years">
                            <i class="fas fa-calendar-alt me-1"></i>Paiement sur 3 ans (+15%)
                        </label>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note :</strong> Les prix finaux des lots seront calculés automatiquement en appliquant les majorations selon le plan de paiement choisi par le client.
                    </div>
                </div>
            </div>

            <!-- Plan de lotissement -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-map me-2"></i>Plan de lotissement</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Plan de lotissement (PDF/Image, max 2MB)</label>
                            <input type="file" class="form-control @error('image_file') is-invalid @enderror" name="image_file" accept=".pdf,image/*">
                            @error('image_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="form-text text-muted">Formats acceptés : JPG, PNG, PDF (max 2MB)</small>
                        </div>
                        <div class="col-md-6">
                            @if($site->image_url)
                                @php
                                    $isPdf = Str::endsWith($site->image_url, '.pdf');
                                @endphp

                                <p class="mt-2 mb-1"><strong>Fichier actuel :</strong></p>
                                @if($isPdf)
                                    <a href="{{ asset('storage/' . $site->image_url) }}" target="_blank" class="btn btn-outline-primary btn-sm">Voir le PDF</a>
                                @else
                                    <img src="{{ asset('storage/' . $site->image_url) }}" alt="Plan du lotissement" class="preview-image">
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <a href="{{ route('sites.show', $site->id) }}" class="btn btn-secondary me-2">
                    <i class="fas fa-times me-1"></i> Annuler
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>

    <style>
        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
        
        .preview-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.2);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .card-header {
            border-radius: 10px 10px 0 0 !important;
        }
    </style>
</x-app-layout>