<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 fw-bold">
                <i class="fas fa-layer-group me-2"></i>Ajout en masse de lots - {{ $site->name }}
            </h2>
            <a href="{{ route('sites.lots', $site) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Retour aux lots
            </a>
        </div>
    </x-slot>

    <div class="container py-4">
        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-magic me-2"></i>Création automatique de lots
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('sites.lots.bulk-store', $site) }}" method="POST">
                            @csrf

                            <div class="mb-4">
                                <label for="lot_numbers" class="form-label fw-bold">
                                    <i class="fas fa-list-ol me-1"></i>Numéros de lots
                                </label>
                                <textarea 
                                    class="form-control @error('lot_numbers') is-invalid @enderror" 
                                    id="lot_numbers" 
                                    name="lot_numbers" 
                                    rows="6" 
                                    placeholder="Entrez les numéros de lots, séparés par des virgules, espaces ou nouvelles lignes.
Exemples:
A1, A2, A3, A4
B1 B2 B3
C1
C2
D1-D10"
                                    required>{{ old('lot_numbers') }}</textarea>
                                @error('lot_numbers')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Vous pouvez séparer les numéros par des virgules, espaces ou nouvelles lignes.
                                </small>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="default_area" class="form-label fw-bold">
                                        <i class="fas fa-expand-arrows-alt me-1"></i>Superficie par défaut (m²)
                                    </label>
                                    <input 
                                        type="number" 
                                        class="form-control @error('default_area') is-invalid @enderror" 
                                        id="default_area" 
                                        name="default_area" 
                                        value="{{ old('default_area', 150) }}" 
                                        min="0" 
                                        step="0.01"
                                        placeholder="Ex: 150">
                                    @error('default_area')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Superficie appliquée à tous les lots (modifiable individuellement après création)</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-calculator me-1"></i>Calcul automatique des prix
                                    </label>
                                    <div class="form-check mt-2">
                                        <input 
                                            class="form-check-input" 
                                            type="checkbox" 
                                            id="auto_calculate_prices" 
                                            name="auto_calculate_prices" 
                                            value="1" 
                                            {{ old('auto_calculate_prices', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="auto_calculate_prices">
                                            Calculer automatiquement les prix selon la position
                                        </label>
                                    </div>
                                    <small class="text-muted">Les lots seront créés en position "intérieur" par défaut</small>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>Processus automatique :</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Tous les lots seront créés avec la position "Intérieur" par défaut</li>
                                    <li>Les prix seront calculés automatiquement selon la configuration du site</li>
                                    <li>Vous pourrez modifier individuellement les positions et prix après création</li>
                                    <li>Les numéros déjà existants seront ignorés</li>
                                </ul>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Créer les lots
                                </button>
                                <a href="{{ route('sites.lots', $site) }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Annuler
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>Configuration du site
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block">Prix par position</small>
                            <div class="d-flex justify-content-between">
                                <span class="text-success"><i class="fas fa-crown me-1"></i>Angle:</span>
                                <span class="fw-bold">{{ number_format($site->price_angle ?? 0, 0, ',', ' ') }} FCFA</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-warning"><i class="fas fa-star me-1"></i>Façade:</span>
                                <span class="fw-bold">{{ number_format($site->price_facade ?? 0, 0, ',', ' ') }} FCFA</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-info"><i class="fas fa-circle me-1"></i>Intérieur:</span>
                                <span class="fw-bold">{{ number_format($site->price_interieur ?? 0, 0, ',', ' ') }} FCFA</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted d-block">Plans de paiement activés</small>
                            @if($site->enable_payment_cash)
                                <div class="text-success"><i class="fas fa-check me-1"></i>Paiement comptant</div>
                            @endif
                            @if($site->enable_payment_1_year)
                                <div class="text-primary"><i class="fas fa-check me-1"></i>Paiement 1 an (+5%)</div>
                            @endif
                            @if($site->enable_payment_2_years)
                                <div class="text-warning"><i class="fas fa-check me-1"></i>Paiement 2 ans (+10%)</div>
                            @endif
                            @if($site->enable_payment_3_years)
                                <div class="text-danger"><i class="fas fa-check me-1"></i>Paiement 3 ans (+15%)</div>
                            @endif
                        </div>

                        <div class="border-top pt-3">
                            <small class="text-muted d-block">Statistiques actuelles</small>
                            <div class="d-flex justify-content-between">
                                <span>Lots existants:</span>
                                <span class="fw-bold">{{ $site->lots()->count() }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Lots disponibles:</span>
                                <span class="fw-bold text-success">{{ $site->availableLots()->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="fas fa-question-circle me-2"></i>Aide
                        </h6>
                    </div>
                    <div class="card-body">
                        <h6>Formats acceptés :</h6>
                        <ul class="small mb-0">
                            <li><strong>Virgules :</strong> A1, A2, A3</li>
                            <li><strong>Espaces :</strong> A1 A2 A3</li>
                            <li><strong>Nouvelles lignes :</strong><br>A1<br>A2<br>A3</li>
                            <li><strong>Mixte :</strong> A1, A2 A3<br>B1 B2</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('lot_numbers');
            const counter = document.createElement('small');
            counter.className = 'text-muted';
            textarea.parentNode.appendChild(counter);

            function updateCounter() {
                const text = textarea.value.trim();
                if (text) {
                    const numbers = text.split(/[\s,;]+/).filter(n => n.trim().length > 0);
                    counter.textContent = `${numbers.length} lot(s) détecté(s)`;
                } else {
                    counter.textContent = '';
                }
            }

            textarea.addEventListener('input', updateCounter);
            updateCounter();
        });
    </script>
</x-app-layout>