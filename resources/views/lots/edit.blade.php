<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-bold">
            <i class="fas fa-edit me-2"></i>Modifier le Lot {{ $lot->lot_number }} - {{ $site->name }}
        </h2>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <!-- CORRECTION ICI : sites.lots.update au lieu de lots.update -->
                    <form action="{{ route('sites.lots.update', ['site' => $site->id, 'lot' => $lot->id]) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Numéro du lot -->
                        <div class="mb-3">
                            <label for="lot_number" class="form-label">Numéro du lot</label>
                            <input type="text" name="lot_number" id="lot_number" class="form-control" value="{{ old('lot_number', $lot->lot_number) }}" required>
                            @error('lot_number')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Superficie -->
                        <div class="mb-3">
                            <label for="area" class="form-label">Superficie (m²)</label>
                            <input type="number" step="0.01" name="area" id="area" class="form-control" value="{{ old('area', $lot->area) }}" required>
                            @error('area')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Position -->
                        <div class="mb-3">
                            <label for="position" class="form-label">Position</label>
                            <select name="position" id="position" class="form-select" required>
                                <option value="">-- Choisir --</option>
                                <option value="interieur" {{ old('position', $lot->position) == 'interieur' ? 'selected' : '' }}>Intérieur</option>
                                <option value="facade" {{ old('position', $lot->position) == 'facade' ? 'selected' : '' }}>Façade (+10%)</option>
                                <option value="angle" {{ old('position', $lot->position) == 'angle' ? 'selected' : '' }}>Angle (+10%)</option>
                            </select>
                            @error('position')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Prix de base -->
                        <div class="mb-3">
                            <label for="base_price" class="form-label">Prix de base (FCFA)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="base_price" id="base_price" class="form-control" value="{{ old('base_price', $lot->base_price) }}" required>
                                <button type="button" class="btn btn-outline-secondary" id="reset-price" title="Réappliquer le prix automatique">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                            <small class="text-muted" id="price-info">Prix actuel: {{ number_format($lot->base_price, 0, ',', ' ') }} FCFA</small>
                            @error('base_price')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Prix final calculé -->
                        <div class="mb-3">
                            <label class="form-label">Prix final estimé (FCFA)</label>
                            <div class="alert alert-info py-2" id="final-price-display">
                                <i class="fas fa-calculator me-2"></i>
                                <span id="final-price-value">{{ number_format($lot->final_price, 0, ',', ' ') }}</span> FCFA
                                <small class="ms-2" id="price-details"></small>
                            </div>
                        </div>

                        <!-- Statut -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Statut du lot</label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="disponible" {{ old('status', $lot->status) == 'disponible' ? 'selected' : '' }}>Disponible</option>
                                <option value="reserve_temporaire" {{ old('status', $lot->status) == 'reserve_temporaire' ? 'selected' : '' }}>Réservation temporaire</option>
                                <option value="reserve" {{ old('status', $lot->status) == 'reserve' ? 'selected' : '' }}>Réservé</option>
                                <option value="vendu" {{ old('status', $lot->status) == 'vendu' ? 'selected' : '' }}>Vendu</option>
                            </select>
                            @error('status')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (optionnelle)</label>
                            <textarea name="description" id="description" rows="3" class="form-control">{{ old('description', $lot->description) }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('sites.lots', $site) }}" class="btn btn-secondary me-2">Annuler</a>
                            <button type="submit" class="btn btn-primary">Modifier le lot</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Prix par défaut selon la position (récupérés du site)
            const sitePrices = {
                'interieur': {{ $site->price_interieur ?? 0 }},
                'facade': {{ $site->price_facade ?? 0 }},
                'angle': {{ $site->price_angle ?? 0 }}
            };

            const positionSelect = document.getElementById('position');
            const basePriceInput = document.getElementById('base_price');
            const priceInfo = document.getElementById('price-info');
            const resetPriceBtn = document.getElementById('reset-price');
            const finalPriceValue = document.getElementById('final-price-value');
            const priceDetails = document.getElementById('price-details');

            let autoPriceApplied = false;
            let lastSelectedPosition = '{{ $lot->position }}';

            // Fonction pour calculer le prix final avec majorations
            function calculateFinalPrice() {
                const basePrice = parseFloat(basePriceInput.value) || 0;
                const position = positionSelect.value;
                let finalPrice = basePrice;
                let details = '';

                if (position === 'facade' || position === 'angle') {
                    finalPrice = basePrice * 1.10; // +10% pour façade et angle
                    details = `(Base: ${basePrice.toLocaleString()} + 10% = ${finalPrice.toLocaleString()} FCFA)`;
                } else {
                    details = `(Prix de base: ${basePrice.toLocaleString()} FCFA)`;
                }

                finalPriceValue.textContent = finalPrice.toLocaleString();
                priceDetails.textContent = details;
            }

            // Fonction pour mettre à jour le prix selon la position
            function updatePriceByPosition() {
                const position = positionSelect.value;
                
                if (position && sitePrices[position] > 0) {
                    basePriceInput.value = sitePrices[position];
                    priceInfo.textContent = `Prix ${position} appliqué automatiquement`;
                    priceInfo.className = 'text-success';
                    autoPriceApplied = true;
                    lastSelectedPosition = position;
                    
                    // Calculer et afficher le prix final
                    calculateFinalPrice();
                } else {
                    priceInfo.textContent = 'Sélectionnez une position';
                    priceInfo.className = 'text-muted';
                    autoPriceApplied = false;
                }
            }

            // Événement changement de position
            positionSelect.addEventListener('change', function() {
                calculateFinalPrice();
            });

            // Événement modification manuelle du prix
            basePriceInput.addEventListener('input', function() {
                basePriceInput.dataset.manualEdit = 'true';
                autoPriceApplied = false;
                priceInfo.textContent = 'Prix modifié manuellement';
                priceInfo.className = 'text-warning';
                
                // Recalculer le prix final
                calculateFinalPrice();
            });

            // Bouton de réinitialisation du prix
            resetPriceBtn.addEventListener('click', function() {
                if (lastSelectedPosition && sitePrices[lastSelectedPosition] > 0) {
                    basePriceInput.value = sitePrices[lastSelectedPosition];
                    delete basePriceInput.dataset.manualEdit;
                    autoPriceApplied = true;
                    priceInfo.textContent = `Prix ${lastSelectedPosition} réappliqué`;
                    priceInfo.className = 'text-success';
                    
                    // Recalculer le prix final
                    calculateFinalPrice();
                }
            });

            // Calcul initial
            calculateFinalPrice();

            // Validation du formulaire
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const lotNumber = document.getElementById('lot_number').value.trim();
                const area = document.getElementById('area').value;
                const position = positionSelect.value;
                const basePrice = basePriceInput.value;

                if (!lotNumber || !area || !position || !basePrice) {
                    e.preventDefault();
                    alert('Veuillez remplir tous les champs obligatoires.');
                    return;
                }

                if (parseFloat(basePrice) <= 0) {
                    e.preventDefault();
                    alert('Le prix de base doit être supérieur à 0.');
                    return;
                }
            });
        });
    </script>
    @endpush

    <style>
        .input-group .btn {
            border-left: 0;
        }
        #final-price-display {
            background-color: #e9ecef;
            border: 1px solid #dee2e6;
        }
        #price-info {
            font-size: 0.875rem;
        }
    </style>
</x-app-layout>