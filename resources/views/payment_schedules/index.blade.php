<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h3 fw-bold text-gradient-primary mb-1">
                    <i class="fas fa-money-check-alt me-2 text-success"></i>Gestion des Paiements Clients
                </h2>
                <p class="text-muted mb-0">Suivez et gérez les échéances de paiement de vos clients</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm shadow-sm" onclick="window.print()">
                    <i class="fas fa-print me-1"></i>Imprimer
                </button>
                <a href="{{ route('payment-schedules.export') }}" class="btn btn-success btn-sm shadow-sm">
                    <i class="fas fa-file-excel me-1"></i>Exporter Excel
                </a>
            </div>
        </div>
    </x-slot>

    <div class="container-fluid py-4">
        <!-- Alertes importantes -->
        @if(auth()->user()->isAgent())
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Information :</strong> Vous ne voyez que les échéances de vos prospects assignés. 
            Les échéances en rouge sont en retard et nécessitent une action immédiate.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Filtres améliorés -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-header bg-gradient-light border-0">
                <div class="d-flex align-items-center">
                    <div class="bg-primary rounded-circle p-2 me-3">
                        <i class="fas fa-filter text-white"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark">Filtres de Recherche</h6>
                        <small class="text-muted">Personnalisez votre vue des paiements</small>
                    </div>
                </div>
            </div>
            <div class="card-body bg-light">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-primary">
                            <i class="fas fa-tasks me-1"></i>Statut des Échéances
                        </label>
                        <select name="status" class="form-select shadow-sm border-primary">
                            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>🔍 Toutes les échéances</option>
                            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>⏳ En attente de paiement</option>
                            <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>✅ Déjà payées</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-primary">
                            <i class="fas fa-calendar-alt me-1"></i>Mois d'Échéance
                        </label>
                        <input type="month" name="month" class="form-control shadow-sm border-primary" value="{{ $month }}">
                        <div class="form-text text-success">
                            <i class="fas fa-info-circle me-1"></i>Par défaut: mois actuel ({{ now()->format('m/Y') }})
                        </div>
                    </div>
                    @if(auth()->user()->isManager() || auth()->user()->isAdmin())
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-primary">
                            <i class="fas fa-user-tie me-1"></i>Commercial
                        </label>
                        <select name="commercial" class="form-select shadow-sm border-primary">
                            <option value="all" {{ $commercial === 'all' ? 'selected' : '' }}>👥 Tous les commerciaux</option>
                            @foreach($commercials as $com)
                                <option value="{{ $com->id }}" {{ $commercial == $com->id ? 'selected' : '' }}>
                                    🔹 {{ $com->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-12 mt-4">
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm px-4">
                                <i class="fas fa-search me-2"></i>Appliquer les filtres
                            </button>
                            <a href="{{ route('payment-schedules.index') }}" class="btn btn-outline-secondary btn-lg shadow-sm px-4">
                                <i class="fas fa-refresh me-2"></i>Réinitialiser
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistiques améliorées -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm bg-gradient-primary text-white h-100">
                    <div class="card-body text-center position-relative overflow-hidden">
                        <div class="position-absolute top-0 end-0 opacity-10">
                            <i class="fas fa-calendar-check" style="font-size: 4rem;"></i>
                        </div>
                        <div class="position-relative">
                            <i class="fas fa-calendar-check fa-3x mb-3"></i>
                            <h3 class="mb-0 fw-bold">{{ $stats['total_installments'] }}</h3>
                            <p class="mb-0 opacity-75">Total Échéances</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm bg-gradient-success text-white h-100">
                    <div class="card-body text-center position-relative overflow-hidden">
                        <div class="position-absolute top-0 end-0 opacity-10">
                            <i class="fas fa-check-circle" style="font-size: 4rem;"></i>
                        </div>
                        <div class="position-relative">
                            <i class="fas fa-check-circle fa-3x mb-3"></i>
                            <h3 class="mb-0 fw-bold">{{ $stats['paid_installments'] }}</h3>
                            <p class="mb-0 opacity-75">✅ Payées</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm bg-gradient-warning text-white h-100">
                    <div class="card-body text-center position-relative overflow-hidden">
                        <div class="position-absolute top-0 end-0 opacity-10">
                            <i class="fas fa-clock" style="font-size: 4rem;"></i>
                        </div>
                        <div class="position-relative">
                            <i class="fas fa-clock fa-3x mb-3"></i>
                            <h3 class="mb-0 fw-bold">{{ $stats['pending_installments'] }}</h3>
                            <p class="mb-0 opacity-75">⏳ En attente</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm bg-gradient-danger text-white h-100">
                    <div class="card-body text-center position-relative overflow-hidden">
                        <div class="position-absolute top-0 end-0 opacity-10">
                            <i class="fas fa-money-bill-wave" style="font-size: 4rem;"></i>
                        </div>
                        <div class="position-relative">
                            <i class="fas fa-money-bill-wave fa-3x mb-3"></i>
                            <h3 class="mb-0 fw-bold">{{ number_format($stats['pending_amount']/1000000, 1) }}M</h3>
                            <p class="mb-0 opacity-75">💰 Montant en attente</p>
                            <small class="opacity-50">{{ number_format($stats['pending_amount'], 0, ',', ' ') }} F</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphique d'évolution -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Évolution des Paiements (6 derniers mois)
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="paymentChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau de bord principal amélioré -->
        <div class="card border-0 shadow">
            <div class="card-header bg-gradient-dark text-white border-0 position-relative overflow-hidden">
                <div class="position-absolute top-0 end-0 opacity-10">
                    <i class="fas fa-users" style="font-size: 3rem;"></i>
                </div>
                <div class="position-relative d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-credit-card me-2"></i>Tableau de Bord des Paiements
                        </h5>
                        <small class="opacity-75">Suivi détaillé par client avec actions directes</small>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-success fs-6 px-3 py-2">
                            <i class="fas fa-coins me-1"></i>Total : {{ number_format($stats['total_amount'], 0, ',', ' ') }} F
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if($clientsPaginated->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 25%">
                                        <i class="fas fa-user me-1"></i>Client & Contact
                                    </th>
                                    <th style="width: 15%" class="text-center">
                                        <i class="fas fa-calculator me-1"></i>Montant Total
                                    </th>
                                    <th style="width: 15%" class="text-center">
                                        <i class="fas fa-check-circle me-1"></i>Montant Payé
                                    </th>
                                    <th style="width: 15%" class="text-center">
                                        <i class="fas fa-clock me-1"></i>Montant en Attente
                                    </th>
                                    <th style="width: 15%" class="text-center">
                                        <i class="fas fa-calendar-alt me-1"></i>Prochaine Échéance
                                    </th>
                                    <th style="width: 15%" class="text-center">
                                        <i class="fas fa-cogs me-1"></i>Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clientsPaginated as $clientData)
                                    @php
                                        $client = $clientData['client'];
                                        $progressPercentage = $clientData['total_amount'] > 0 ? 
                                            ($clientData['paid_amount'] / $clientData['total_amount']) * 100 : 0;
                                            
                                        $statusClass = $clientData['overdue_schedules'] > 0 ? 'table-danger' : 
                                                      ($clientData['pending_schedules'] > 0 ? 'table-warning' : 'table-success');
                                    @endphp
                                    <tr class="{{ $statusClass }}">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                                     style="width: 50px; height: 50px; font-size: 1.2rem;">
                                                    {{ substr($client->full_name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <div class="fw-bold fs-6">{{ $client->full_name }}</div>
                                                    <small class="text-muted">
                                                        <i class="fas fa-phone me-1"></i>{{ $client->phone }}
                                                    </small>
                                                    <br>
                                                    <small class="text-info">
                                                        <i class="fas fa-file-contract me-1"></i>
                                                        {{ count($clientData['contracts']) }} contrat(s)
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="fw-bold text-primary fs-5">
                                                {{ number_format($clientData['total_amount'], 0, ',', ' ') }} F
                                            </div>
                                            <small class="text-muted">
                                                {{ $clientData['total_schedules'] }} échéances
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <div class="fw-bold text-success fs-5">
                                                {{ number_format($clientData['paid_amount'], 0, ',', ' ') }} F
                                            </div>
                                            <small class="text-muted">
                                                {{ $clientData['paid_schedules'] }} payées / {{ $clientData['total_schedules'] }}
                                            </small>
                                            <div class="progress mt-1" style="height: 6px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: {{ $progressPercentage }}%"></div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="fw-bold text-warning fs-5">
                                                {{ number_format($clientData['pending_amount'], 0, ',', ' ') }} F
                                            </div>
                                            <small class="text-muted">
                                                {{ $clientData['pending_schedules'] }} en attente
                                                @if($clientData['overdue_schedules'] > 0)
                                                    <br><span class="text-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        {{ $clientData['overdue_schedules'] }} en retard
                                                    </span>
                                                @endif
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            @if($clientData['next_due_date'])
                                                <div class="fw-bold">
                                                    {{ $clientData['next_due_date']->format('d/m/Y') }}
                                                </div>
                                                <small class="text-muted">
                                                    {{ $clientData['next_due_date']->diffForHumans() }}
                                                </small>
                                            @else
                                                <span class="text-success">
                                                    <i class="fas fa-check-circle me-1"></i>
                                                    Tout payé
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <!-- Affichage amélioré des prochaines échéances à payer -->
                                            @if(count($clientData['upcoming_schedules']) > 0)
                                                <div class="mb-3">
                                                    <div class="bg-light rounded p-2 mb-2">
                                                        <h6 class="text-primary mb-0 fw-bold">
                                                            <i class="fas fa-calendar-check me-1"></i>Prochaines échéances (🔥 Action requise)
                                                        </h6>
                                                    </div>
                                                    @foreach($clientData['upcoming_schedules'] as $schedule)
                                                        <div class="card mb-2 border-0 shadow-sm 
                                                              {{ $schedule->due_date->isPast() ? 'border-start border-danger border-4' : 'border-start border-warning border-4' }}">
                                                            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                                                <div class="flex-grow-1">
                                                                    <div class="d-flex align-items-center mb-2">
                                                                        <span class="badge 
                                                                              {{ $schedule->due_date->isPast() ? 'bg-danger' : ($schedule->due_date->isToday() ? 'bg-warning' : 'bg-primary') }} 
                                                                              me-2 px-2 py-1">
                                                                            Échéance N°{{ $schedule->installment_number }}
                                                                        </span>
                                                                        <span class="fw-bold text-success fs-6">{{ number_format($schedule->amount, 0, ',', ' ') }} F</span>
                                                                    </div>
                                                                    <div class="text-muted small">
                                                                        <i class="fas fa-calendar me-1"></i>{{ $schedule->due_date->format('d/m/Y') }}
                                                                        @if($schedule->due_date->isPast())
                                                                            <span class="text-danger ms-2">
                                                                                <i class="fas fa-exclamation-triangle"></i> En retard de {{ $schedule->due_date->diffForHumans() }}
                                                                            </span>
                                                                        @elseif($schedule->due_date->isToday())
                                                                            <span class="text-warning ms-2">
                                                                                <i class="fas fa-clock"></i> À payer aujourd'hui
                                                                            </span>
                                                                        @else
                                                                            <span class="text-info ms-2">
                                                                                <i class="fas fa-info-circle"></i> {{ $schedule->due_date->diffForHumans() }}
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <button type="button" class="btn 
                                                                    {{ $schedule->due_date->isPast() ? 'btn-danger' : 'btn-success' }} 
                                                                    btn-sm fw-bold shadow-sm px-3 py-2" 
                                                                        onclick="openSchedulePaymentModal({{ $schedule->id }}, {{ $schedule->installment_number }}, {{ $schedule->amount }}, '{{ $client->full_name }}')"
                                                                        title="Verser cette échéance">
                                                                    <i class="fas fa-money-check-alt me-1"></i>
                                                                    {{ $schedule->due_date->isPast() ? 'URGENT' : 'Verser' }}
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            
                                            <!-- Actions générales améliorées -->
                                            <div class="d-flex flex-column gap-2 mt-3">
                                                <button type="button" class="btn btn-outline-info btn-sm shadow-sm" 
                                                        onclick="showPaymentHistory({{ $client->id }}, '{{ $client->full_name }}')"
                                                        title="Voir l'historique complet des paiements">
                                                    <i class="fas fa-history me-2"></i>Historique Complet
                                                </button>
                                                <button type="button" class="btn btn-outline-primary btn-sm shadow-sm" 
                                                        onclick="viewClientSchedules({{ $client->id }}, '{{ $client->full_name }}')"
                                                        title="Voir toutes les échéances de ce client">
                                                    <i class="fas fa-list-alt me-2"></i>Toutes les Échéances
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    Affichage de {{ $clientsPaginated->firstItem() ?? 0 }} à {{ $clientsPaginated->lastItem() ?? 0 }} 
                                    sur {{ $clientsPaginated->total() }} clients
                                </small>
                            </div>
                            <div>
                                {{ $clientsPaginated->links() }}
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-users-slash fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun client trouvé</h5>
                        <p class="text-muted">Aucun client ne correspond aux critères de recherche.</p>
                        <a href="{{ route('payment-schedules.index') }}" class="btn btn-primary">
                            <i class="fas fa-refresh me-1"></i>Voir tous les clients
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal pour effectuer un versement -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-hourglass-start me-2"></i>Processus de validation du paiement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="paymentForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Nouveau processus :</strong> Ce paiement sera mis en attente de validation et devra passer par 4 étapes :
                            <br><small class="mt-2 d-block">
                                1️⃣ <strong>Caissier</strong> : Vérification du montant et du justificatif<br>
                                2️⃣ <strong>Responsable</strong> : Validation du paiement<br>
                                3️⃣ <strong>Administrateur</strong> : Validation finale<br>
                                4️⃣ <strong>Complété</strong> : Échéance automatiquement marquée comme payée
                            </small>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Client:</strong> <span id="clientNameInModal"></span><br>
                            <strong>Montant total en attente:</strong> <span id="pendingAmountInModal" class="fw-bold"></span> FCFA
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Montant reçu (FCFA)</label>
                                    <input type="number" name="amount" class="form-control" required min="0" step="100" 
                                           placeholder="Ex: 500000" id="paymentAmount">
                                    <div class="form-text">Ce montant sera validé par le caissier lors de la première étape</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Méthode de paiement</label>
                                    <select name="payment_method" class="form-select" required>
                                        <option value="">Sélectionner la méthode...</option>
                                        <option value="especes">Espèces</option>
                                        <option value="cheque">Chèque</option>
                                        <option value="virement">Virement bancaire</option>
                                        <option value="mobile_money">Mobile Money</option>
                                        <option value="carte">Carte bancaire</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Justificatif de paiement (Optionnel)</label>
                            <input type="file" name="payment_proof" class="form-control" 
                                   accept=".pdf,.jpg,.jpeg,.png" title="Formats acceptés: PDF, JPG, PNG">
                            <div class="form-text">Vous pouvez ajouter un justificatif maintenant ou le caissier le demandera plus tard</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Notes/Observations</label>
                            <textarea name="notes" class="form-control" rows="3" 
                                      placeholder="Détails sur le paiement, référence de transaction, etc."></textarea>
                        </div>
                        
                        <div class="alert alert-success">
                            <i class="fas fa-shield-alt me-2"></i>
                            <strong>Sécurité :</strong> Ce système garantit la traçabilité et la validation à plusieurs niveaux pour tous les paiements.
                            Le client sera informé à chaque étape du processus.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-warning text-white">
                            <i class="fas fa-hourglass-start me-1"></i>Démarrer la Validation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal pour le paiement d'une échéance spécifique -->
    <div class="modal fade" id="schedulePaymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-money-bill-wave me-2"></i>Paiement d'Échéance Spécifique
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="schedulePaymentForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Client:</strong> <span id="scheduleClientName"></span><br>
                            <strong>Échéance N°:</strong> <span id="scheduleInstallmentNumber"></span><br>
                            <strong>Montant de l'échéance:</strong> <span id="scheduleAmount" class="fw-bold"></span>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Processus de validation :</strong> Ce paiement sera mis en attente de validation et devra passer par 4 étapes :
                            <br><small class="mt-2 d-block">
                                1️⃣ <strong>Caissier</strong> : Vérification du montant et du justificatif<br>
                                2️⃣ <strong>Responsable</strong> : Validation du paiement<br>
                                3️⃣ <strong>Administrateur</strong> : Validation finale<br>
                                4️⃣ <strong>Complété</strong> : Échéance automatiquement marquée comme payée
                            </small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Montant reçu (FCFA)</label>
                                    <input type="number" name="amount" id="schedulePaymentAmount" class="form-control" required min="0" step="100" 
                                           placeholder="Ex: 500000">
                                    <div class="form-text">Ce montant sera validé par le caissier lors de la première étape</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Méthode de paiement</label>
                                    <select name="payment_method" class="form-select" required>
                                        <option value="">Sélectionner la méthode...</option>
                                        <option value="especes">Espèces</option>
                                        <option value="cheque">Chèque</option>
                                        <option value="virement">Virement bancaire</option>
                                        <option value="mobile_money">Mobile Money</option>
                                        <option value="carte">Carte bancaire</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Justificatif de paiement (Optionnel)</label>
                            <input type="file" name="payment_proof" class="form-control" 
                                   accept=".pdf,.jpg,.jpeg,.png" title="Formats acceptés: PDF, JPG, PNG">
                            <div class="form-text">Vous pouvez ajouter un justificatif maintenant ou le caissier le demandera plus tard</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Notes/Observations</label>
                            <textarea name="notes" class="form-control" rows="3" 
                                      placeholder="Détails sur le paiement, référence de transaction, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Annuler
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-money-bill-wave me-1"></i>Enregistrer le Paiement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal pour l'historique des paiements -->
    <div class="modal fade" id="paymentHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-history me-2"></i>Historique des Paiements
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><strong>Client:</strong> <span id="historyClientName"></span></h6>
                        <button class="btn btn-outline-success btn-sm" onclick="refreshPaymentHistory()">
                            <i class="fas fa-refresh me-1"></i>Actualiser
                        </button>
                    </div>
                    
                    <div id="paymentHistoryContent">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="mt-2 text-muted">Chargement de l'historique...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Graphique des paiements
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        new Chart(paymentCtx, {
            type: 'line',
            data: {
                labels: @json($monthlyData->pluck('month')),
                datasets: [{
                    label: 'Montants dus',
                    data: @json($monthlyData->pluck('due_amount')),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Montants payés',
                    data: @json($monthlyData->pluck('paid_amount')),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        let currentClientId = null;

        function showPaymentModal(clientId, clientName, pendingAmount) {
            currentClientId = clientId;
            document.getElementById('clientNameInModal').textContent = clientName;
            document.getElementById('pendingAmountInModal').textContent = new Intl.NumberFormat('fr-FR').format(pendingAmount);
            
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            const form = document.getElementById('paymentForm');
            form.action = `/clients/${clientId}/payment`;
            
            // Réinitialiser le formulaire
            form.reset();
            
            modal.show();
        }

        function showPaymentHistory(clientId, clientName) {
            currentClientId = clientId;
            document.getElementById('historyClientName').textContent = clientName;
            
            const modal = new bootstrap.Modal(document.getElementById('paymentHistoryModal'));
            modal.show();
            
            // Charger l'historique
            loadPaymentHistory(clientId);
        }

        function loadPaymentHistory(clientId) {
            const content = document.getElementById('paymentHistoryContent');
            content.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2 text-muted">Chargement de l'historique...</p>
                </div>
            `;

            fetch(`/clients/${clientId}/payment-history`)
                .then(response => response.json())
                .then(data => {
                    if (data.payments && data.payments.length > 0) {
                        let historyHtml = `
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Date</th>
                                            <th>Montant</th>
                                            <th>Méthode</th>
                                            <th>Statut</th>
                                            <th>Référence</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        data.payments.forEach(payment => {
                            const statusBadge = getStatusBadge(payment.validation_status);
                            historyHtml += `
                                <tr>
                                    <td>${formatDate(payment.payment_date)}</td>
                                    <td class="fw-bold">${new Intl.NumberFormat('fr-FR').format(payment.amount)} F</td>
                                    <td>${payment.payment_method || 'N/A'}</td>
                                    <td>${statusBadge}</td>
                                    <td><small class="text-muted">${payment.reference_number || 'N/A'}</small></td>
                                    <td><small>${payment.notes || 'Aucune note'}</small></td>
                                </tr>
                            `;
                        });
                        
                        historyHtml += `
                                    </tbody>
                                </table>
                            </div>
                            <div class="alert alert-info mt-3">
                                <strong>Total des paiements:</strong> ${new Intl.NumberFormat('fr-FR').format(data.total_paid)} F
                            </div>
                        `;
                        
                        content.innerHTML = historyHtml;
                    } else {
                        content.innerHTML = `
                            <div class="text-center py-4">
                                <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucun paiement trouvé</h5>
                                <p class="text-muted">Ce client n'a encore effectué aucun paiement.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement de l\'historique:', error);
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erreur lors du chargement de l'historique des paiements.
                        </div>
                    `;
                });
        }

        function refreshPaymentHistory() {
            if (currentClientId) {
                loadPaymentHistory(currentClientId);
            }
        }

        function getStatusBadge(status) {
            const statusConfig = {
                'pending': { class: 'bg-warning', text: 'En attente' },
                'caissier_validated': { class: 'bg-info', text: 'Caissier validé' },
                'responsable_validated': { class: 'bg-primary', text: 'Responsable validé' },
                'admin_validated': { class: 'bg-success', text: 'Admin validé' },
                'completed': { class: 'bg-success', text: 'Complété' },
                'rejected': { class: 'bg-danger', text: 'Rejeté' }
            };
            
            const config = statusConfig[status] || { class: 'bg-secondary', text: status };
            return `<span class="badge ${config.class}">${config.text}</span>`;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        }

        // Nouvelle fonction pour le paiement d'une échéance spécifique
        function openSchedulePaymentModal(scheduleId, installmentNumber, amount, clientName) {
            const modal = new bootstrap.Modal(document.getElementById('schedulePaymentModal'));
            const form = document.getElementById('schedulePaymentForm');
            
            // Configurer le formulaire
            form.action = `/payment-schedules/${scheduleId}/pay`;
            
            // Mettre à jour les informations dans le modal
            document.getElementById('scheduleClientName').textContent = clientName;
            document.getElementById('scheduleInstallmentNumber').textContent = installmentNumber;
            document.getElementById('scheduleAmount').textContent = new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
            
            // Pré-remplir le montant
            document.getElementById('schedulePaymentAmount').value = amount;
            
            modal.show();
        }

        function viewClientSchedules(clientId, clientName) {
            // Rediriger vers la page détaillée des échéances du client
            window.location.href = `/clients/${clientId}/payment-schedules`;
        }
    </script>
    <!-- Styles CSS personnalisés pour le design amélioré -->
    <style>
        .text-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
        
        .bg-gradient-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
        }
        
        .bg-gradient-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
        }
        
        .bg-gradient-danger {
            background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%) !important;
        }
        
        .bg-gradient-light {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%) !important;
        }
        
        .bg-gradient-dark {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%) !important;
        }
        
        .card:hover {
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            transition: all 0.2s ease;
        }
        
        .opacity-10 {
            opacity: 0.1;
        }
        
        .opacity-75 {
            opacity: 0.75;
        }
        
        .opacity-50 {
            opacity: 0.5;
        }
        
        .shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }
        
        .border-start {
            border-left: var(--bs-border-width) var(--bs-border-style) var(--bs-border-color) !important;
        }
        
        .border-4 {
            border-width: 4px !important;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .btn-danger:hover {
            animation: pulse 1s infinite;
        }
    </style>

</x-app-layout>