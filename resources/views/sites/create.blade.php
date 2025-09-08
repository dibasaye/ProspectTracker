<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-bold"><i class="fas fa-plus me-2"></i>Créer un Site</h2>
    </x-slot>

    <div class="container py-4">
        <form action="{{ route('sites.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Infos générales -->
            <div class="mb-3">
                <label class="form-label">Nom du site</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Localisation</label>
                <input type="text" class="form-control @error('location') is-invalid @enderror" name="location" value="{{ old('location') }}" required>
                @error('location')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Superficie totale (m²)</label>
                    <input type="number" class="form-control @error('total_area') is-invalid @enderror" name="total_area" value="{{ old('total_area') }}" required>
                    @error('total_area')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Prix de base au m² (FCFA)</label>
                    <input type="number" class="form-control @error('base_price_per_sqm') is-invalid @enderror" name="base_price_per_sqm" value="{{ old('base_price_per_sqm') }}" required>
                    @error('base_price_per_sqm')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Frais de réservation (FCFA)</label>
                    <input type="number" class="form-control @error('reservation_fee') is-invalid @enderror" name="reservation_fee" value="{{ old('reservation_fee') }}" required>
                    @error('reservation_fee')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-md-4">
                    <label class="form-label">Frais d'adhésion (FCFA)</label>
                    <input type="number" class="form-control @error('membership_fee') is-invalid @enderror" name="membership_fee" value="{{ old('membership_fee') }}" required>
                    @error('membership_fee')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nombre total de lots</label>
                    <input type="number" class="form-control @error('total_lots') is-invalid @enderror" name="total_lots" value="{{ old('total_lots') }}" required>
                    @error('total_lots')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date de lancement</label>
                    <input type="date" class="form-control @error('launch_date') is-invalid @enderror" name="launch_date" value="{{ old('launch_date') }}">
                    @error('launch_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-md-6">
                    <label class="form-label">Latitude</label>
                    <input type="text" class="form-control @error('latitude') is-invalid @enderror" name="latitude" value="{{ old('latitude') }}" placeholder="Ex : 14.6928">
                    @error('latitude')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Longitude</label>
                    <input type="text" class="form-control @error('longitude') is-invalid @enderror" name="longitude" value="{{ old('longitude') }}" placeholder="Ex : -17.4467">
                    @error('longitude')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-md-6">
                    <label class="form-label">Plan de lotissement (PDF/Image, max 2MB)</label>
                    <input type="file" class="form-control @error('image_file') is-invalid @enderror" name="image_file" accept=".pdf,image/*">
                    @error('image_file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">Formats acceptés : JPG, PNG, PDF (max 2MB)</small>
                </div>
            </div>

            <!-- Tarifs par position de lot -->
            <div class="mt-4 p-3 border rounded bg-primary bg-opacity-10">
                <h5 class="mb-3 text-primary"><i class="fas fa-map-marked-alt me-2"></i>Tarifs par position de lot</h5>
                <p class="text-muted mb-3">Définissez les prix fixes selon l'emplacement des lots sur le site.</p>
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-success">Prix lots en angle (FCFA)</label>
                        <input type="number" class="form-control @error('price_angle') is-invalid @enderror" 
                               name="price_angle" value="{{ old('price_angle') }}" 
                               placeholder="Ex : 8000000" required>
                        @error('price_angle')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Lots situés aux angles du site</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-warning">Prix lots en façade (FCFA)</label>
                        <input type="number" class="form-control @error('price_facade') is-invalid @enderror" 
                               name="price_facade" value="{{ old('price_facade') }}" 
                               placeholder="Ex : 6000000" required>
                        @error('price_facade')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Lots donnant sur la façade principale</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-info">Prix lots intérieurs (FCFA)</label>
                        <input type="number" class="form-control @error('price_interieur') is-invalid @enderror" 
                               name="price_interieur" value="{{ old('price_interieur') }}" 
                               placeholder="Ex : 5000000" required>
                        @error('price_interieur')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Lots situés à l'intérieur du site</small>
                    </div>
                </div>
            </div>

            <!-- Plan de paiement avec options à cocher -->
            <div class="mt-4 p-3 border rounded bg-light">
                <h5 class="mb-3"><i class="fas fa-credit-card me-2"></i>Options de paiement disponibles</h5>
                <p class="text-muted mb-3">Sélectionnez les modes de paiement autorisés pour ce site.</p>

                <!-- Paiement comptant -->
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="chkCash" name="enable_payment_cash" {{ old('enable_payment_cash', true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold text-success" for="chkCash">
                        <i class="fas fa-money-bill-wave me-1"></i>Paiement comptant
                    </label>
                </div>

                <!-- Paiement 1 an -->
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="chk1Year" name="enable_payment_1_year" {{ old('enable_payment_1_year', true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold text-primary" for="chk1Year">
                        <i class="fas fa-calendar-alt me-1"></i>Paiement sur 1 an (+5%)
                    </label>
                </div>

                <!-- Paiement 2 ans -->
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="chk2Years" name="enable_payment_2_years" {{ old('enable_payment_2_years', true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold text-warning" for="chk2Years">
                        <i class="fas fa-calendar-alt me-1"></i>Paiement sur 2 ans (+10%)
                    </label>
                </div>

                <!-- Paiement 3 ans -->
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="chk3Years" name="enable_payment_3_years" {{ old('enable_payment_3_years') ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold text-danger" for="chk3Years">
                        <i class="fas fa-calendar-alt me-1"></i>Paiement sur 3 ans (+15%)
                    </label>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note :</strong> Les prix finaux des lots seront calculés automatiquement en appliquant les majorations selon le plan de paiement choisi par le client.
                </div>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary">✅ Enregistrer</button>
                <a href="{{ route('sites.index') }}" class="btn btn-secondary">❌ Annuler</a>
            </div>
        </form>
    </div>

</x-app-layout>
