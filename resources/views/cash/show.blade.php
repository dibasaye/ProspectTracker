<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold">
                    <i class="fas fa-file-invoice-dollar me-2"></i>Détails de la transaction
                </h2>
                <p class="text-muted mb-0">{{ $transaction->transaction_number }}</p>
            </div>
            <a href="{{ route('cash.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Retour à la caisse
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-fluid">
            <!-- En-tête avec actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h1 class="h3 fw-bold text-dark mb-1">Détails de la transaction</h1>
                            <p class="text-muted small mb-0">{{ $transaction->transaction_number }}</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('cash.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>
                                Retour à la liste
                            </a>
                            
                            @if($transaction->canBeValidated())
                                @if($transaction->type === 'decaissement' && auth()->user()->role === 'administrateur')
                                    <form action="{{ route('cash.validate', $transaction) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Confirmer la validation de ce décaissement ?')">
                                            <i class="fas fa-check me-1"></i>
                                            Valider
                                        </button>
                                    </form>
                                @elseif($transaction->type === 'encaissement' && in_array(auth()->user()->role, ['caissier', 'responsable_commercial', 'administrateur']))
                                    <form action="{{ route('cash.validate', $transaction) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Confirmer la validation de cet encaissement ?')">
                                            <i class="fas fa-check me-1"></i>
                                            Valider
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Statut et type en badges -->
                    <div class="d-flex gap-3 mb-0">
                        <span class="badge rounded-pill px-3 py-2" style="background-color: {{ $transaction->type_color }}20; color: {{ $transaction->type_color }}">
                            {{ $transaction->type_label }}
                        </span>
                        <span class="badge rounded-pill px-3 py-2" style="background-color: {{ $transaction->status_color }}20; color: {{ $transaction->status_color }}">
                            {{ $transaction->status_label }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Informations principales -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h3 class="h5 fw-semibold text-dark mb-4">Informations de la transaction</h3>
                            
                            <div class="row g-4">
                                <div class="col-sm-6">
                                    <dt class="small fw-medium text-muted">Date de transaction</dt>
                                    <dd class="mb-0 text-dark">{{ $transaction->transaction_date->format('d/m/Y') }}</dd>
                                </div>
                                
                                <div class="col-sm-6">
                                    <dt class="small fw-medium text-muted">Montant</dt>
                                    <dd class="mb-0 h5 fw-semibold text-dark">{{ $transaction->formatted_amount }}</dd>
                                </div>
                                
                                <div class="col-sm-6">
                                    <dt class="small fw-medium text-muted">Catégorie</dt>
                                    <dd class="mb-0 text-dark">{{ $transaction->category_label }}</dd>
                                </div>
                                
                                @if($transaction->reference)
                                    <div class="col-sm-6">
                                        <dt class="small fw-medium text-muted">Référence</dt>
                                        <dd class="mb-0 text-dark">{{ $transaction->reference }}</dd>
                                    </div>
                                @endif
                                
                                <div class="col-12">
                                    <dt class="small fw-medium text-muted">Description</dt>
                                    <dd class="mb-0 text-dark">{{ $transaction->description }}</dd>
                                </div>
                                
                                @if($transaction->notes)
                                    <div class="col-12">
                                        <dt class="small fw-medium text-muted">Notes</dt>
                                        <dd class="mb-0 text-dark">{{ $transaction->notes }}</dd>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Pièce justificative -->
                    @if($transaction->receipt_path)
                        <div class="card shadow-sm mt-4">
                            <div class="card-body">
                                <h3 class="h5 fw-semibold text-dark mb-3">Pièce justificative</h3>
                                
                                <div class="border rounded p-3">
                                    @if(Str::endsWith($transaction->receipt_path, '.pdf'))
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-pdf text-danger fs-2 me-3"></i>
                                            <div>
                                                <p class="fw-medium text-dark mb-1">Document PDF</p>
                                                <a href="{{ $transaction->receipt_url }}" target="_blank" class="text-primary text-decoration-none small">
                                                    Ouvrir le document
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <img src="{{ $transaction->receipt_url }}" alt="Pièce justificative" class="img-fluid rounded">
                                    @endif
                                    
                                    <div class="mt-3">
                                        <a href="{{ $transaction->receipt_url }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-download me-1"></i>
                                            Télécharger
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Informations complémentaires -->
                <div class="col-lg-4">
                    <div class="row g-4">
                        <!-- Informations de gestion -->
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h3 class="h5 fw-semibold text-dark mb-3">Informations de gestion</h3>
                                    
                                    <div class="mb-3">
                                        <dt class="small fw-medium text-muted">Créé par</dt>
                                        <dd class="mb-0 text-dark">
                                            {{ $transaction->createdBy->name ?? 'Utilisateur inconnu' }}
                                            <small class="text-muted d-block">({{ $transaction->created_at->format('d/m/Y H:i') }})</small>
                                        </dd>
                                    </div>
                                    
                                    @if($transaction->validated_at)
                                        <div>
                                            <dt class="small fw-medium text-muted">Validé par</dt>
                                            <dd class="mb-0 text-dark">
                                                {{ $transaction->validatedBy->name ?? 'Utilisateur inconnu' }}
                                                <small class="text-muted d-block">({{ $transaction->validated_at->format('d/m/Y H:i') }})</small>
                                            </dd>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Client associé -->
                        @if($transaction->client)
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h3 class="h5 fw-semibold text-dark mb-3">Client associé</h3>
                                        
                                        <div class="mb-2">
                                            <dt class="small fw-medium text-muted">Nom</dt>
                                            <dd class="mb-0 text-dark">{{ $transaction->client->first_name }} {{ $transaction->client->last_name }}</dd>
                                        </div>
                                        <div class="mb-2">
                                            <dt class="small fw-medium text-muted">Téléphone</dt>
                                            <dd class="mb-0 text-dark">{{ $transaction->client->phone }}</dd>
                                        </div>
                                        @if($transaction->client->email)
                                            <div>
                                                <dt class="small fw-medium text-muted">Email</dt>
                                                <dd class="mb-0 text-dark">{{ $transaction->client->email }}</dd>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Site associé -->
                        @if($transaction->site)
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h3 class="h5 fw-semibold text-dark mb-3">Site associé</h3>
                                        
                                        <div class="mb-2">
                                            <dt class="small fw-medium text-muted">Nom</dt>
                                            <dd class="mb-0 text-dark">{{ $transaction->site->name }}</dd>
                                        </div>
                                        <div>
                                            <dt class="small fw-medium text-muted">Localisation</dt>
                                            <dd class="mb-0 text-dark">{{ $transaction->site->location }}</dd>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Fournisseur (pour décaissements) -->
                        @if($transaction->supplier)
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h3 class="h5 fw-semibold text-dark mb-3">Fournisseur/Bénéficiaire</h3>
                                        
                                        <div class="mb-2">
                                            <dt class="small fw-medium text-muted">Nom</dt>
                                            <dd class="mb-0 text-dark">{{ $transaction->supplier->name }}</dd>
                                        </div>
                                        <div>
                                            <dt class="small fw-medium text-muted">Rôle</dt>
                                            <dd class="mb-0 text-dark">{{ $transaction->supplier->role }}</dd>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Paiement associé -->
                        @if($transaction->payment)
                            <div class="col-12">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <h3 class="h5 fw-semibold text-dark mb-3">Paiement associé</h3>
                                        
                                        <div class="mb-2">
                                            <dt class="small fw-medium text-muted">Montant</dt>
                                            <dd class="mb-0 text-dark">{{ number_format($transaction->payment->amount, 0, ',', ' ') }} FCFA</dd>
                                        </div>
                                        @if($transaction->payment->payment_date)
                                            <div>
                                                <dt class="small fw-medium text-muted">Date de paiement</dt>
                                                <dd class="mb-0 text-dark">{{ $transaction->payment->payment_date->format('d/m/Y') }}</dd>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages de notification -->
    @if(session('success'))
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1050;">
            <div class="toast show" role="alert">
                <div class="toast-header bg-success text-white">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong class="me-auto">Succès</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    {{ session('success') }}
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1050;">
            <div class="toast show" role="alert">
                <div class="toast-header bg-danger text-white">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong class="me-auto">Erreur</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    {{ session('error') }}
                </div>
            </div>
        </div>
    @endif

    <script>
        // Auto-hide toasts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(toast => {
                setTimeout(() => {
                    const bsToast = new bootstrap.Toast(toast);
                    bsToast.hide();
                }, 5000);
            });
        });
    </script>
</x-app-layout>