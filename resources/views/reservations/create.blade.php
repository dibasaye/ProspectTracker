<x-app-layout>
    <x-slot name="header">
        <h2 class="h5 fw-bold">Réservation d'un lot pour {{ $prospect->full_name }}</h2>
    </x-slot>

    <div class="container py-4">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <!-- Section Sélection de Site -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-map-marker-alt me-2"></i>Sélection du Site
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('reservations.create', $prospect) }}" id="siteSelectionForm">
                    <div class="row align-items-end">
                        <div class="col-md-6">
                            <label for="site_id" class="form-label fw-bold">Site d'intérêt</label>
                            <select class="form-select" id="site_id" name="site_id" onchange="this.form.submit()">
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" {{ $selectedSite == $site->id ? 'selected' : '' }}>
                                        {{ $site->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info mb-0">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    @if($prospect->interested_site_id)
                                        Le prospect a déjà un site d'intérêt défini. 
                                        <strong>Changer le site mettra à jour le profil du prospect.</strong>
                                    @else
                                        <strong>Nouveau prospect :</strong> Veuillez sélectionner un site d'intérêt.
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section Réservation Rapide par Numéro -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Réservation Rapide par Numéro
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('reservations.reserve-by-number', $prospect) }}" method="POST" id="quickReserveForm">
                    @csrf
                    <input type="hidden" name="create_lot" value="1">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label for="lot_number" class="form-label fw-bold">Numéro de Lot</label>
                            <input type="text" class="form-control" id="lot_number" name="lot_number" required 
                                   placeholder="Ex: A1, B2..." maxlength="10">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="reserve_site_id" class="form-label fw-bold">Site</label>
                            <select class="form-select" id="reserve_site_id" name="site_id" required>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" {{ $selectedSite == $site->id ? 'selected' : '' }}>
                                        {{ $site->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="area" class="form-label fw-bold">Surface (m²)</label>
                            <input type="number" class="form-control" id="area" name="area" required 
                                   min="0" step="0.01" placeholder="Ex: 150">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="base_price" class="form-label fw-bold">Prix de base</label>
                            <input type="number" class="form-control" id="base_price" name="base_price" required 
                                   min="0" placeholder="Ex : 5 000 000">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="position" class="form-label fw-bold">Position</label>
                            <select class="form-select" id="position" name="position" required>
                                <option value="">Choisir...</option>
                                <option value="interieur">Intérieur</option>
                                <option value="facade">Façade</option>
                                <option value="angle">Angle</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-check me-1"></i>Réserver
                            </button>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="description" class="form-label">Description (optionnel)</label>
                            <textarea class="form-control" id="description" name="description" rows="2" 
                                      placeholder="Description du lot..."></textarea>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="alert alert-info mb-0 w-100">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>Info :</strong> Si le lot existe déjà et est disponible, il sera réservé. 
                                    Sinon, un nouveau lot sera créé et réservé automatiquement.
                                </small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section Lots Disponibles -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Lots Disponibles
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="searchLot" class="form-label fw-bold">Rechercher un lot</label>
                        <input type="text" id="searchLot" class="form-control" placeholder="Numéro de lot...">
                    </div>
                    <div class="col-md-2 d-flex align-items-end mb-3">
                        <button id="clearFilters" class="btn btn-secondary w-100">Réinitialiser</button>
                    </div>
                </div>

                @if($availableLots->count())
                    <form method="POST" action="{{ route('reservations.store', $prospect) }}">
                        @csrf
                        <div class="row g-3" id="lotsGrid">
                            @foreach($availableLots as $lot)
                                <div class="col-md-4 col-lg-3 col-6 lot-item" 
                                     data-number="{{ strtolower($lot->lot_number) }}" 
                                     data-site="{{ strtolower($lot->site->name) }}">
                                    <label class="card h-100 lot-card p-3 shadow-sm d-block" style="cursor:pointer;">
                                        <input type="checkbox" name="lot_ids[]" value="{{ $lot->id }}" class="form-check-input me-2">
                                        <div>
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <strong>Lot {{ $lot->lot_number }}</strong>
                                                @if($lot->position === 'angle')
                                                    <i class="fas fa-crown text-warning" title="Lot en angle"></i>
                                                @elseif($lot->position === 'facade')
                                                    <i class="fas fa-star text-info" title="Lot en façade"></i>
                                                @endif
                                            </div>
                                            <div><small class="text-muted">{{ $lot->area }} m²</small></div>
                                            <div class="fw-bold text-primary">
                                                {{ number_format($lot->final_price, 0, ',', ' ') }} FCFA
                                            </div>
                                            <div class="mt-1">
                                                <span class="badge bg-success">Disponible</span>
                                                <span class="badge bg-light text-dark">{{ $lot->site->name }}</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary" id="reserveButton" disabled>
                                <i class="fas fa-bookmark me-2"></i>Réserver les lots sélectionnés
                            </button>
                            <a href="{{ route('prospects.show', $prospect) }}" class="btn btn-secondary">Annuler</a>
                            <div class="mt-2">
                                <small class="text-muted" id="selectionCounter">Aucun lot sélectionné</small>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        @if($selectedSite)
                            Aucun lot disponible pour le site sélectionné.
                        @else
                            Veuillez sélectionner un site pour voir les lots disponibles.
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Gestion de la sélection multiple de lots
            const checkboxes = document.querySelectorAll('input[name="lot_ids[]"]');
            const reserveButton = document.getElementById('reserveButton');
            const selectionCounter = document.getElementById('selectionCounter');
            
            function updateSelectionStatus() {
                const selectedCount = document.querySelectorAll('input[name="lot_ids[]"]:checked').length;
                
                if (selectedCount === 0) {
                    reserveButton.disabled = true;
                    selectionCounter.textContent = 'Aucun lot sélectionné';
                    selectionCounter.className = 'text-muted';
                } else if (selectedCount === 1) {
                    reserveButton.disabled = false;
                    selectionCounter.textContent = '1 lot sélectionné';
                    selectionCounter.className = 'text-success fw-bold';
                } else {
                    reserveButton.disabled = false;
                    selectionCounter.textContent = selectedCount + ' lots sélectionnés';
                    selectionCounter.className = 'text-success fw-bold';
                }
            }
            
            // Ajouter l'événement de changement à toutes les cases à cocher
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectionStatus);
            });
            
            // Initialiser l'état
            updateSelectionStatus();
            
            // Filtrage des lots
            const searchInput = document.getElementById('searchLot');
            const clearBtn = document.getElementById('clearFilters');
            const lotItems = document.querySelectorAll('.lot-item');

            function filterLots() {
                const search = searchInput.value.toLowerCase();

                lotItems.forEach(item => {
                    const lotNum = item.dataset.number;
                    const siteName = item.dataset.site;

                    const matches = lotNum.includes(search) || siteName.includes(search);
                    item.style.display = matches ? 'block' : 'none';
                });
            }

            searchInput.addEventListener('input', filterLots);
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                filterLots();
            });

            // Gérer le formulaire de réservation rapide
            const quickReserveForm = document.getElementById('quickReserveForm');
            const lotNumberInput = document.getElementById('lot_number');
            const basePriceInput = document.getElementById('base_price');
            const positionSelect = document.getElementById('position');

            // Auto-calcul du prix final basé sur la position
            function updateFinalPrice() {
                const basePrice = parseFloat(basePriceInput.value) || 0;
                const position = positionSelect.value;
                let finalPrice = basePrice;

                if (position === 'facade' || position === 'angle') {
                    finalPrice = basePrice * 1.10; // +10% pour façade et angle
                }

                // Afficher le prix calculé (optionnel)
                const priceDisplay = document.getElementById('priceDisplay');
                if (priceDisplay) {
                    priceDisplay.textContent = `Prix final estimé: ${finalPrice.toLocaleString()} FCFA`;
                }
            }

            basePriceInput.addEventListener('input', updateFinalPrice);
            positionSelect.addEventListener('change', updateFinalPrice);

            // Validation du formulaire
            quickReserveForm.addEventListener('submit', function(e) {
                const lotNumber = lotNumberInput.value.trim();
                const siteId = document.getElementById('reserve_site_id').value;
                const area = document.getElementById('area').value;
                const basePrice = basePriceInput.value;
                const position = positionSelect.value;

                if (!lotNumber || !siteId || !area || !basePrice || !position) {
                    e.preventDefault();
                    alert('Veuillez remplir tous les champs obligatoires.');
                    return;
                }

                // Confirmation avant soumission
                if (!confirm(`Êtes-vous sûr de vouloir réserver le lot ${lotNumber} ?`)) {
                    e.preventDefault();
                }
            });

            // Focus automatique sur le numéro de lot
            lotNumberInput.focus();
        });
    </script>
    @endpush
</x-app-layout>
