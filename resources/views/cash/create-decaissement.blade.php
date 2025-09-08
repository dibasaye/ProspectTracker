<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h5">
                <i class="fas fa-money-bill-wave me-2"></i>💸 Nouveau Décaissement
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
                    <h1 class="h4 mb-0">💸 Nouveau Décaissement</h1>
                </div>

                <!-- Avertissement -->
                <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                    <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                    <div>
                        <strong>Important :</strong> La pièce justificative est obligatoire pour tous les décaissements. 
                        Tous les décaissements doivent être validés par l'administrateur avant d'être finalisés.
                    </div>
                </div>

                <form action="{{ route('cash.decaissement.store') }}" method="POST" enctype="multipart/form-data">
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
                                        <option value="salaire" {{ old('category') == 'salaire' ? 'selected' : '' }}>💼 Salaire</option>
                                        <option value="charge_social" {{ old('category') == 'charge_social' ? 'selected' : '' }}>🏛️ Charge sociale</option>
                                        <option value="fourniture" {{ old('category') == 'fourniture' ? 'selected' : '' }}>📦 Fourniture</option>
                                        <option value="transport" {{ old('category') == 'transport' ? 'selected' : '' }}>🚗 Transport</option>
                                        <option value="maintenance" {{ old('category') == 'maintenance' ? 'selected' : '' }}>🔧 Maintenance</option>
                                        <option value="marketing" {{ old('category') == 'marketing' ? 'selected' : '' }}>📢 Marketing</option>
                                        <option value="administration" {{ old('category') == 'administration' ? 'selected' : '' }}>🏢 Administration</option>
                                        <option value="autre" {{ old('category') == 'autre' ? 'selected' : '' }}>❓ Autre</option>
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
                                           placeholder="Exemple: 25000">
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
                                           placeholder="Référence du décaissement">
                                    @error('reference')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fournisseur/Bénéficiaire -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5 class="card-title">👥 Fournisseur/Bénéficiaire (optionnel)</h5>
                            
                            <div class="mb-3">
                                <label for="supplier_id" class="form-label">🏪 Fournisseur/Bénéficiaire</label>
                                <select name="supplier_id" 
                                        id="supplier_id" 
                                        class="form-select @error('supplier_id') is-invalid @enderror">
                                    <option value="">-- Aucun fournisseur spécifique --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }} ({{ $supplier->role }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Description et justificatif OBLIGATOIRE -->
                    <div class="card border-danger mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="card-title mb-0">📝 Description et justificatif (OBLIGATOIRE)</h5>
                        </div>
                        <div class="card-body bg-light">
                            <div class="mb-3">
                                <label for="description" class="form-label">📄 Description <span class="text-danger">*</span></label>
                                <textarea name="description" 
                                          id="description" 
                                          rows="4" 
                                          required 
                                          class="form-control @error('description') is-invalid @enderror" 
                                          placeholder="Décrivez la nature et la raison de ce décaissement en détail...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="receipt" class="form-label text-danger">
                                    📎 Pièce justificative <span class="text-danger">* (OBLIGATOIRE)</span>
                                </label>
                                <input type="file" 
                                       name="receipt" 
                                       id="receipt" 
                                       accept="image/*,.pdf" 
                                       required 
                                       class="form-control border-danger @error('receipt') is-invalid @enderror">
                                <div class="form-text text-danger">
                                    <i class="fas fa-exclamation-circle"></i> <strong>Obligatoire :</strong> Formats acceptés : JPG, JPEG, PNG, PDF (max. 2 MB)
                                    <br><small>Exemples : facture, reçu, bon de commande, justificatif de paiement, etc.</small>
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
                                          placeholder="Commentaires additionnels pour l'administrateur...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Information de validation -->
                    <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                        <i class="fas fa-info-circle me-3 fs-4"></i>
                        <div>
                            Ce décaissement sera automatiquement mis en attente de validation administrative. 
                            Seuls les administrateurs peuvent valider les décaissements.
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="{{ route('cash.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Annuler
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-check me-2"></i>Enregistrer le décaissement
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

            // Vérification du fichier justificatif
            const form = document.querySelector('form');
            const receiptInput = document.getElementById('receipt');
            
            form.addEventListener('submit', function(e) {
                if (!receiptInput.files.length) {
                    e.preventDefault();
                    alert('⚠️ Veuillez joindre une pièce justificative avant de soumettre le décaissement.');
                    receiptInput.focus();
                    return false;
                }
            });

            // Validation de la taille du fichier
            receiptInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const fileSize = (file.size / 1024 / 1024).toFixed(2);
                    if (fileSize > 2) {
                        alert('⚠️ Le fichier est trop volumineux (max. 2 MB)');
                        this.value = '';
                    } else {
                        // Afficher un feedback positif
                        const feedback = document.createElement('div');
                        feedback.className = 'text-success small mt-1';
                        feedback.innerHTML = '<i class="fas fa-check-circle"></i> Fichier sélectionné : ' + file.name;
                        
                        // Supprimer l'ancien feedback s'il existe
                        const oldFeedback = this.parentNode.querySelector('.text-success');
                        if (oldFeedback) {
                            oldFeedback.remove();
                        }
                        
                        this.parentNode.appendChild(feedback);
                    }
                }
            });
        });
    </script>
</x-app-layout>