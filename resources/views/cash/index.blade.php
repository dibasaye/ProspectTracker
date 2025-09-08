<x-app-layout>
    <x-slot name="header">
        <h2 class="h5">💰 Gestion de la Caisse</h2>
    </x-slot>

    <div class="container py-4">
        <!-- En-tête avec actions -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h4 mb-0">Gestion de la Caisse</h1>
                    <div>
                        @if(in_array(auth()->user()->role, ['caissier', 'responsable_commercial', 'administrateur']))
                            <a href="{{ route('cash.encaissement.create') }}" class="btn btn-success btn-sm me-2">
                                <i class="fas fa-plus"></i> Nouvel Encaissement
                            </a>
                            <a href="{{ route('cash.decaissement.create') }}" class="btn btn-danger btn-sm me-2">
                                <i class="fas fa-minus"></i> Nouveau Décaissement
                            </a>
                        @endif
                        <a href="{{ route('cash.rapport') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-chart-bar"></i> Rapport
                        </a>
                    </div>
                </div>

                <!-- Statistiques en cartes -->
                <div class="row mb-4">
                    <!-- Solde du jour -->
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6 class="card-title">📅 Solde du Jour</h6>
                                <h4 class="mb-1">{{ number_format($stats['solde_jour'], 0, ',', ' ') }} FCFA</h4>
                                <small class="opacity-75">{{ now()->format('d/m/Y') }}</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Solde de la période -->
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6 class="card-title">📊 Solde Période</h6>
                                <h4 class="mb-1">{{ number_format($stats['solde_periode'], 0, ',', ' ') }} FCFA</h4>
                                <small class="opacity-75">
                                    @switch($stats['filter_period'])
                                        @case('today') Aujourd'hui @break
                                        @case('this_week') Cette semaine @break
                                        @case('this_month') Ce mois @break
                                        @case('this_year') Cette année @break
                                        @default Période sélectionnée
                                    @endswitch
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- En attente de validation -->
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h6 class="card-title">⏳ En Attente</h6>
                                <h4 class="mb-1">{{ $stats['pending_count'] }}</h4>
                                <small class="opacity-75">{{ $stats['pending_decaissements'] }} décaissement(s)</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Solde total -->
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6 class="card-title">💎 Solde Total</h6>
                                <h4 class="mb-1">{{ number_format($stats['solde_total'], 0, ',', ' ') }} FCFA</h4>
                                <small class="opacity-75">Tous les temps</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres avancés -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">🔍 Filtres</h5>
                
                <form method="GET" action="{{ route('cash.index') }}">
                    <div class="row mb-3">
                        <!-- Filtre par période -->
                        <div class="col-md-3">
                            <label for="filter_period" class="form-label">Période</label>
                            <select name="filter_period" id="filter_period" class="form-select">
                                <option value="today" {{ request('filter_period') == 'today' ? 'selected' : '' }}>Aujourd'hui</option>
                                <option value="yesterday" {{ request('filter_period') == 'yesterday' ? 'selected' : '' }}>Hier</option>
                                <option value="this_week" {{ request('filter_period') == 'this_week' ? 'selected' : '' }}>Cette semaine</option>
                                <option value="last_week" {{ request('filter_period') == 'last_week' ? 'selected' : '' }}>Semaine dernière</option>
                                <option value="this_month" {{ request('filter_period') == 'this_month' ? 'selected' : '' }}>Ce mois</option>
                                <option value="last_month" {{ request('filter_period') == 'last_month' ? 'selected' : '' }}>Mois dernier</option>
                                <option value="this_year" {{ request('filter_period') == 'this_year' ? 'selected' : '' }}>Cette année</option>
                                <option value="last_year" {{ request('filter_period') == 'last_year' ? 'selected' : '' }}>Année dernière</option>
                            </select>
                        </div>

                        <!-- Type de transaction -->
                        <div class="col-md-3">
                            <label for="type" class="form-label">Type</label>
                            <select name="type" id="type" class="form-select">
                                <option value="">Tous les types</option>
                                <option value="encaissement" {{ request('type') == 'encaissement' ? 'selected' : '' }}>💰 Encaissements</option>
                                <option value="decaissement" {{ request('type') == 'decaissement' ? 'selected' : '' }}>💸 Décaissements</option>
                            </select>
                        </div>

                        <!-- Statut -->
                        <div class="col-md-3">
                            <label for="status" class="form-label">Statut</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Tous les statuts</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ En attente</option>
                                <option value="validated" {{ request('status') == 'validated' ? 'selected' : '' }}>✅ Validé</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>❌ Annulé</option>
                            </select>
                        </div>

                        <!-- Seulement validées -->
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check">
                                <input id="only_validated" name="only_validated" type="checkbox" value="1" {{ request('only_validated') ? 'checked' : '' }} class="form-check-input">
                                <label for="only_validated" class="form-check-label">
                                    Seulement les validées
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Dates personnalisées -->
                    <div class="row">
                        <div class="col-md-4">
                            <label for="date_from" class="form-label">Date de début</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="date_to" class="form-label">Date de fin</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="form-control">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Filtrer</button>
                            <a href="{{ route('cash.index') }}" class="btn btn-secondary">Réinitialiser</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des transactions -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">💳 Transactions</h5>
                
                @if($transactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>N° Transaction</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Catégorie</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $transaction)
                                    <tr>
                                        <td class="fw-bold">{{ $transaction->transaction_number }}</td>
                                        <td>{{ $transaction->transaction_date->format('d/m/Y') }}</td>
                                        <td>
                                            @if($transaction->type === 'encaissement')
                                                <span class="badge bg-success">💰 {{ $transaction->type_label }}</span>
                                            @else
                                                <span class="badge bg-danger">💸 {{ $transaction->type_label }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $transaction->category_label }}</td>
                                        <td class="fw-bold">{{ $transaction->formatted_amount }}</td>
                                        <td>
                                            @if($transaction->status === 'validated')
                                                <span class="badge bg-success">✅ {{ $transaction->status_label }}</span>
                                            @elseif($transaction->status === 'pending')
                                                <span class="badge bg-warning text-dark">⏳ {{ $transaction->status_label }}</span>
                                            @else
                                                <span class="badge bg-secondary">❌ {{ $transaction->status_label }}</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            <a href="{{ route('cash.show', $transaction) }}" class="btn btn-sm btn-info" title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if($transaction->canBeValidated())
                                                @if($transaction->type === 'decaissement' && auth()->user()->role === 'administrateur')
                                                    <form action="{{ route('cash.validate', $transaction) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Confirmer la validation de ce décaissement ?')" title="Valider">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                @elseif($transaction->type === 'encaissement' && in_array(auth()->user()->role, ['caissier', 'responsable_commercial', 'administrateur']))
                                                    <form action="{{ route('cash.validate', $transaction) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Confirmer la validation de cet encaissement ?')" title="Valider">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $transactions->withQueryString()->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-receipt fa-3x text-muted"></i>
                        </div>
                        <h5 class="text-muted">Aucune transaction</h5>
                        <p class="text-muted">Aucune transaction trouvée avec les filtres appliqués.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Messages de notification -->
    @if(session('success'))
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1050">
            <div class="toast show" role="alert">
                <div class="toast-header bg-success text-white">
                    <strong class="me-auto">✅ Succès</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    {{ session('success') }}
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1050">
            <div class="toast show" role="alert">
                <div class="toast-header bg-danger text-white">
                    <strong class="me-auto">❌ Erreur</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    {{ session('error') }}
                </div>
            </div>
        </div>
    @endif
</x-app-layout>