<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-bold"><i class="fas fa-plus me-2"></i>Créer un Site</h2>
    </x-slot>

    <div class="container py-4">
        <form action="{{ route('sites.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Messages d'erreur généraux -->
            @if($errors->any())
                <div class="alert alert-danger">
                    <h6>Veuillez corriger les erreurs suivantes :</h6>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Infos générales -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations générales</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom du site *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Localisation *</label>
                            <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                   name="location" value="{{ old('location') }}" required>
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Superficie totale (m²)</label>
                            <input type="number" class="form-control @error('total_area') is-invalid @enderror" 
                                   name="total_area" value="{{ old('total_area') }}" step="0.01" min="0">
                            @error('total_area')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Nombre total de lots *</label>
                            <input type="number" class="form-control @error('total_lots') is-invalid @enderror" 
                                   name="total_lots" value="{{ old('total_lots') }}" required min="1">
                            @error('total_lots')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Date de lancement</label>
                            <input type="date" class="form-control @error('launch_date') is-invalid @enderror" 
                                   name="launch_date" value="{{ old('launch_date') }}">
                            @error('launch_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coordonnées géographiques -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Localisation GPS</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Latitude</label>
                            <input type="text" class="form-control @error('latitude') is-invalid @enderror" 
                                   name="latitude" value="{{ old('latitude') }}" 
                                   placeholder="Ex : 14.6928">
                            @error('latitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Longitude</label>
                            <input type="text" class="form-control @error('longitude') is-invalid @enderror" 
                                   name="longitude" value="{{ old('longitude') }}" 
                                   placeholder="Ex : -17.4467">
                            @error('longitude')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plan de lotissement -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-image me-2"></i>Plan de lotissement</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Plan de lotissement (PDF/Image, max 2MB)</label>
                        <input type="file" class="form-control @error('image_file') is-invalid @enderror" 
                               name="image_file" accept=".pdf,image/*">
                        @error('image_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Formats acceptés : JPG, PNG, PDF (max 2MB)</small>
                    </div>
                </div>
            </div>

            <!-- Frais et tarifs de base -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Frais et tarifs</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Frais de réservation (FCFA) *</label>
                            <input type="number" class="form-control @error('reservation_fee') is-invalid @enderror" 
                                   name="reservation_fee" value="{{ old('reservation_fee') }}" 
                                   required min="0" step="1">
                            @error('reservation_fee')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Frais d'adhésion (FCFA) *</label>
                            <input type="number" class="form-control @error('membership_fee') is-invalid @enderror" 
                                   name="membership_fee" value="{{ old('membership_fee') }}" 
                                   required min="0" step="1">
                            @error('membership_fee')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarifs par position de lot -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i>Tarifs par position de lot</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Définissez les prix fixes selon l'emplacement des lots sur le site.</p>
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-success">Prix lots en angle (FCFA) *</label>
                            <input type="number" class="form-control @error('price_angle') is-invalid @enderror" 
                                   name="price_angle" value="{{ old('price_angle') }}" 
                                   placeholder="Ex : 8000000" required min="0" step="1">
                            @error('price_angle')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Lots situés aux angles du site</small>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-warning">Prix lots en façade (FCFA) *</label>
                            <input type="number" class="form-control @error('price_facade') is-invalid @enderror" 
                                   name="price_facade" value="{{ old('price_facade') }}" 
                                   placeholder="Ex : 6000000" required min="0" step="1">
                            @error('price_facade')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Lots donnant sur la façade principale</small>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-info">Prix lots intérieurs (FCFA) *</label>
                            <input type="number" class="form-control @error('price_interieur') is-invalid @enderror" 
                                   name="price_interieur" value="{{ old('price_interieur') }}" 
                                   placeholder="Ex : 5000000" required min="0" step="1">
                            @error('price_interieur')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Lots situés à l'intérieur du site</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Options de paiement -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Options de paiement disponibles</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Sélectionnez les modes de paiement autorisés pour ce site.</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <!-- Paiement comptant -->
                            <div class="form-check mb-3 p-3 border rounded">
                                <input class="form-check-input" type="checkbox" id="chkCash" 
                                       name="enable_payment_cash" {{ old('enable_payment_cash', true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-success" for="chkCash">
                                    <i class="fas fa-money-bill-wave me-2"></i>Paiement comptant
                                </label>
                                <div class="small text-muted mt-1">Prix de base sans majoration</div>
                            </div>

                            <!-- Paiement 1 an -->
                            <div class="form-check mb-3 p-3 border rounded">
                                <input class="form-check-input" type="checkbox" id="chk1Year" 
                                       name="enable_payment_1_year" {{ old('enable_payment_1_year', true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-primary" for="chk1Year">
                                    <i class="fas fa-calendar-alt me-2"></i>Paiement sur 1 an (+5%)
                                </label>
                                <div class="small text-muted mt-1">Prix de base + 5% de majoration</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Paiement 2 ans -->
                            <div class="form-check mb-3 p-3 border rounded">
                                <input class="form-check-input" type="checkbox" id="chk2Years" 
                                       name="enable_payment_2_years" {{ old('enable_payment_2_years', true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-warning" for="chk2Years">
                                    <i class="fas fa-calendar-alt me-2"></i>Paiement sur 2 ans (+10%)
                                </label>
                                <div class="small text-muted mt-1">Prix de base + 10% de majoration</div>
                            </div>

                            <!-- Paiement 3 ans -->
                            <div class="form-check mb-3 p-3 border rounded">
                                <input class="form-check-input" type="checkbox" id="chk3Years" 
                                       name="enable_payment_3_years" {{ old('enable_payment_3_years') ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-danger" for="chk3Years">
                                    <i class="fas fa-calendar-alt me-2"></i>Paiement sur 3 ans (+15%)
                                </label>
                                <div class="small text-muted mt-1">Prix de base + 15% de majoration</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note :</strong> Les prix finaux des lots seront calculés automatiquement en appliquant les majorations selon le plan de paiement choisi par le client.
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Enregistrer le site
                        </button>
                        <a href="{{ route('sites.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Annuler
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>