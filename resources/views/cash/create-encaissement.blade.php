<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h5">
                <i class="fas fa-money-bill-wave me-2"></i>💰 Nouvel Encaissement
            </h2>
            <a href="{{ route('cash.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Retour à la caisse
            </a>
        </div>
    </x-slot>

    <div class="container py-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h4 mb-0">💰 Nouvel Encaissement</h1>
                    <a href="{{ route('cash.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>

                <form action="{{ route('cash.encaissement.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Informations générales -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5 class="card-title">📋 Informations générales</h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="transaction_date" class="form-label">📅 Date de transaction <span class="text-danger">*</span></label>
                                    <input type="date" 
                                           name="transaction_date" 
                                           id="transaction_date" 
                                           value="{{ old('transaction_date', now()->format('Y-m-d')) }}" 
                                           required 
                                           class="form-control @error('transaction_date') is-invalid @enderror">
                                    @error('transaction_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">🏷️ Catégorie <span class="text-danger">*</span></label>
                                    <select name="category" 
                                            id="category" 
                                            required 
                                            class="form-select @error('category') is-invalid @enderror">
                                        <option value="">-- Sélectionnez une catégorie --</option>
                                        <option value="vente_terrain" {{ old('category') == 'vente_terrain' ? 'selected' : '' }}>🏞️ Vente de terrain</option>
                                        <option value="adhesion" {{ old('category') == 'adhesion' ? 'selected' : '' }}>📝 Adhésion</option>
                                        <option value="reservation" {{ old('category') == 'reservation' ? 'selected' : '' }}>🎫 Réservation</option>
                                        <option value="mensualite" {{ old('category') == 'mensualite' ? 'selected' : '' }}>📅 Mensualité</option>
                                    </select>
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="amount" class="form-label">💵 Montant (FCFA) <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           name="amount" 
                                           id="amount" 
                                           value="{{ old('amount') }}" 
                                           min="0" 
                                           step="1" 
                                           required 
                                           class="form-control @error('amount') is-invalid @enderror"
                                           placeholder="Exemple: 50000">
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="reference" class="form-label">🔗 Référence</label>
                                    <input type="text" 
                                           name="reference" 
                                           id="reference" 
                                           value="{{ old('reference') }}" 
                                           class="form-control @error('reference') is-invalid @enderror"
                                           placeholder="Référence du paiement">
                                    @error('reference')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Client et Site (optionnel) -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5 class="card-title">👤 Client et Site (optionnel)</h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="client_id" class="form-label">👤 Client</label>
                                    <select name="client_id" 
                                            id="client_id" 
                                            class="form-select @error('client_id') is-invalid @enderror">
                                        <option value="">-- Aucun client spécifique --</option>
                                        @foreach($clients as $client)
                                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                                {{ $client->first_name }} {{ $client->last_name }} - {{ $client->phone }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('client_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="site_id" class="form-label">🏢 Site</label>
                                    <select name="site_id" 
                                            id="site_id" 
                                            class="form-select @error('site_id') is-invalid @enderror">
                                        <option value="">-- Aucun site spécifique --</option>
                                        @foreach($sites as $site)
                                            <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                                {{ $site->name }} - {{ $site->location }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('site_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paiement lié (optionnel) -->
                    @if($payments->count() > 0)
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h5 class="card-title">🔗 Paiement lié (optionnel)</h5>
                                
                                <div class="mb-3">
                                    <label for="payment_id" class="form-label">💳 Associer à un paiement existant</label>
                                    <select name="payment_id" 
                                            id="payment_id" 
                                            class="form-select @error('payment_id') is-invalid @enderror">
                                        <option value="">-- Aucun paiement associé --</option>
                                        @foreach($payments as $payment)
                                            <option value="{{ $payment->id }}" {{ old('payment_id') == $payment->id ? 'selected' : '' }}>
                                                {{ $payment->client ? $payment->client->first_name . ' ' . $payment->client->last_name : 'Client inconnu' }} - 
                                                {{ number_format($payment->amount, 0, ',', ' ') }} FCFA - 
                                                {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : 'Date inconnue' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('payment_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Description et justificatif -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5 class="card-title">📝 Description et justificatif</h5>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">📄 Description <span class="text-danger">*</span></label>
                                <textarea name="description" 
                                          id="description" 
                                          rows="4" 
                                          required 
                                          class="form-control @error('description') is-invalid @enderror" 
                                          placeholder="Décrivez la nature de cet encaissement...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="receipt" class="form-label">📎 Pièce justificative (optionnel)</label>
                                <input type="file" 
                                       name="receipt" 
                                       id="receipt" 
                                       accept="image/*,.pdf" 
                                       class="form-control @error('receipt') is-invalid @enderror">
                                <div class="form-text">
                                    <i class="fas fa-info-circle"></i> Formats acceptés : JPG, JPEG, PNG, PDF (max. 2 MB)
                                </div>
                                @error('receipt')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">💭 Notes supplémentaires</label>
                                <textarea name="notes" 
                                          id="notes" 
                                          rows="3" 
                                          class="form-control @error('notes') is-invalid @enderror" 
                                          placeholder="Notes ou commentaires additionnels...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="{{ route('cash.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Annuler
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Enregistrer l'encaissement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Script pour améliorer l'UX -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-formatage du montant
            const amountInput = document.getElementById('amount');
            if (amountInput) {
                amountInput.addEventListener('input', function() {
                    let value = this.value.replace(/\s/g, '');
                    if (value) {
                        this.value = parseInt(value).toLocaleString('fr-FR');
                    }
                });
                
                // Nettoyer avant soumission
                amountInput.closest('form').addEventListener('submit', function() {
                    amountInput.value = amountInput.value.replace(/\s/g, '');
                });
            }

            // Prévisualisation du fichier uploadé
            const receiptInput = document.getElementById('receipt');
            if (receiptInput) {
                receiptInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const fileSize = (file.size / 1024 / 1024).toFixed(2);
                        if (fileSize > 2) {
                            alert('⚠️ Le fichier est trop volumineux (max. 2 MB)');
                            this.value = '';
                        }
                    }
                });
            }
        });
    </script>
</x-app-layout>