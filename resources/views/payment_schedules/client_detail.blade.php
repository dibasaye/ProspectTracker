<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold">
                    <i class="fas fa-calendar-alt me-2"></i>Échéances de {{ $client->full_name }}
                </h2>
                <small class="text-muted">
                    <i class="fas fa-phone me-1"></i>{{ $client->phone }} | 
                    <i class="fas fa-envelope me-1"></i>{{ $client->email ?? 'Non renseigné' }}
                </small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('payment-schedules.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Retour à l'échéancier
                </a>
                <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
                    <i class="fas fa-print me-1"></i>Imprimer
                </button>
            </div>
        </div>
    </x-slot>

    <div class="container-fluid py-4">
        <!-- Informations du client -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">
                    <i class="fas fa-user me-2"></i>Informations du Client
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                 style="width: 60px; height: 60px; font-size: 24px;">
                                {{ substr($client->full_name, 0, 1) }}
                            </div>
                            <div>
                                <h5 class="mb-1">{{ $client->full_name }}</h5>
                                <p class="mb-1 text-muted">
                                    <i class="fas fa-phone me-1"></i>{{ $client->phone }}
                                </p>
                                @if($client->email)
                                    <p class="mb-0 text-muted">
                                        <i class="fas fa-envelope me-1"></i>{{ $client->email }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center">
                                    <h4 class="text-primary mb-1">{{ $stats['total_contracts'] }}</h4>
                                    <small class="text-muted">Contrats</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <h4 class="text-success mb-1">{{ $stats['paid_installments'] }}</h4>
                                    <small class="text-muted">Échéances payées</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques détaillées -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                        <h4 class="mb-0 text-primary">{{ $stats['total_installments'] }}</h4>
                        <small class="text-muted">Total Échéances</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <h4 class="mb-0 text-success">{{ $stats['paid_installments'] }}</h4>
                        <small class="text-muted">Payées</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                        <h4 class="mb-0 text-warning">{{ $stats['pending_installments'] }}</h4>
                        <small class="text-muted">En attente</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                        <h4 class="mb-0 text-danger">{{ $stats['overdue_installments'] }}</h4>
                        <small class="text-muted">En retard</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Montants -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="text-primary mb-1">{{ number_format($stats['total_amount'], 0, ',', ' ') }} F</h5>
                        <small class="text-muted">Montant total des échéances</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="text-success mb-1">{{ number_format($stats['paid_amount'], 0, ',', ' ') }} F</h5>
                        <small class="text-muted">Montant déjà payé</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="text-warning mb-1">{{ number_format($stats['pending_amount'], 0, ',', ' ') }} F</h5>
                        <small class="text-muted">Montant en attente</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Échéances par contrat -->
        @foreach($contracts as $contract)
            <div class="card mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-file-contract me-2"></i>
                        Contrat {{ $contract->contract_number }}
                    </h6>
                    <div class="d-flex gap-2">
                        <span class="badge bg-info">{{ $contract->site->name ?? 'Site N/A' }}</span>
                        @if($contract->lot)
                            <span class="badge bg-secondary">{{ $contract->lot->lot_number ?? 'Lot sans référence' }}</span>
                        @else
                            <span class="badge bg-warning text-dark">Lot non assigné</span>
                        @endif
                        <span class="badge bg-{{ $contract->status === 'signe' ? 'success' : 'warning' }}">
                            {{ $contract->status === 'signe' ? 'Signé' : 'En cours' }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @php
                        $contractSchedules = $schedulesByContract->get($contract->id, collect());
                        $paidCount = $contractSchedules->where('is_paid', true)->count();
                        $totalCount = $contractSchedules->count();
                    @endphp
                    
                    <div class="p-3 border-bottom">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Progression :</strong> {{ $paidCount }}/{{ $totalCount }} échéances payées
                            </div>
                            <div class="col-md-6">
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: {{ $totalCount > 0 ? ($paidCount / $totalCount) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($contractSchedules->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Échéance</th>
                                        <th>Date d'échéance</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                        <th>Date de paiement</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contractSchedules as $schedule)
                                        <tr class="{{ $schedule->is_paid ? 'table-success' : ($schedule->due_date->isPast() ? 'table-danger' : 'table-warning') }}">
                                            <td>
                                                <div class="fw-bold">Échéance N°{{ $schedule->installment_number }}</div>
                                                <small class="text-muted">{{ $schedule->due_date->format('d/m/Y') }}</small>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $schedule->due_date->format('d/m/Y') }}</div>
                                                <small class="text-muted">
                                                    @if($schedule->due_date->isPast() && !$schedule->is_paid)
                                                        <span class="text-danger">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                                            Retard de {{ $schedule->due_date->diffForHumans() }}
                                                        </span>
                                                    @elseif($schedule->due_date->isToday())
                                                        <span class="text-warning">
                                                            <i class="fas fa-clock me-1"></i>
                                                            Aujourd'hui
                                                        </span>
                                                    @else
                                                        <span class="text-info">
                                                            <i class="fas fa-calendar me-1"></i>
                                                            {{ $schedule->due_date->diffForHumans() }}
                                                        </span>
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-primary fs-5">
                                                    <span id="amount-display-{{ $schedule->id }}">{{ number_format($schedule->amount, 0, ',', ' ') }}</span> F
                                                </div>
                                            </td>
                                            <td>
                                                @if($schedule->is_paid)
                                                    <span class="badge bg-success fs-6">
                                                        <i class="fas fa-check me-1"></i>Payé
                                                    </span>
                                                @elseif($schedule->due_date->isPast())
                                                    <span class="badge bg-danger fs-6">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>En retard
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning text-dark fs-6">
                                                        <i class="fas fa-clock me-1"></i>En attente
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($schedule->is_paid && $schedule->paid_date)
                                                    <div class="text-success fw-bold">
                                                        {{ $schedule->paid_date->format('d/m/Y') }}
                                                    </div>
                                                    <small class="text-muted">{{ $schedule->paid_date->format('H:i') }}</small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group-vertical" role="group">
                                                    @if(!$schedule->is_paid)
                                                        <button type="button" class="btn btn-sm btn-success mb-1 fw-bold" 
                                                                onclick="openPaymentModal({{ $schedule->id }}, {{ $schedule->amount }})"
                                                                title="Effectuer un versement">
                                                            <i class="fas fa-money-bill-wave me-1"></i>Verser
                                                        </button>
                                                    @endif
                                                    <a href="{{ route('schedules.receipt', $schedule) }}" 
                                                       class="btn btn-sm btn-outline-primary mb-1" 
                                                       title="Télécharger le reçu">
                                                        <i class="fas fa-download"></i> Reçu
                                                    </a>
                                                    <a href="{{ route('contracts.show', $schedule->contract) }}" 
                                                       class="btn btn-sm btn-outline-info" 
                                                       title="Voir le contrat">
                                                        <i class="fas fa-eye"></i> Contrat
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">Aucune échéance pour ce contrat</h6>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        @if($contracts->count() === 0)
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-file-contract fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Aucun contrat trouvé</h5>
                    <p class="text-muted">Ce client n'a pas encore de contrats signés.</p>
                    <a href="{{ route('contracts.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Créer un contrat
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal pour marquer comme payé -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-hourglass-start me-2"></i>Processus de validation du paiement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="paymentForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Nouveau processus :</strong> Ce paiement sera mis en attente de validation et devra passer par 4 étapes :
                            <br><small class="mt-2 d-block">
                                1️⃣ <strong>Caissier</strong> : Vérification et justificatif<br>
                                2️⃣ <strong>Responsable</strong> : Validation du paiement<br>
                                3️⃣ <strong>Administrateur</strong> : Validation finale<br>
                                4️⃣ <strong>Complété</strong> : Échéance automatiquement marquée comme payée
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Montant reçu (FCFA)</label>
                            <input type="number" id="amount-input" name="amount" class="form-control" required min="0" step="100" 
                                   placeholder="Ex: 500000" oninput="updateAmountDisplay(this.value)">
                            <div class="form-text">Ce montant sera validé par le caissier lors de la première étape</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Justificatif de paiement (Optionnel)</label>
                            <input type="file" name="payment_proof" class="form-control" 
                                   accept=".pdf,.jpg,.jpeg,.png" title="Formats acceptés: PDF, JPG, PNG">
                            <div class="form-text">Vous pouvez ajouter un justificatif maintenant ou le caissier le demandera plus tard</div>
                        </div>
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
                        <div class="mb-3">
                            <label class="form-label fw-bold">Notes/Observations</label>
                            <textarea name="notes" class="form-control" rows="3" 
                                      placeholder="Observations, commentaires, références..."></textarea>
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

    <script>
        let currentScheduleId = null;

        function openPaymentModal(scheduleId, currentAmount) {
            currentScheduleId = scheduleId;
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            const form = document.getElementById('paymentForm');
            form.action = `/payment-schedules/${scheduleId}/pay`;
            
            // Pré-remplir le champ avec le montant actuel
            const amountInput = document.getElementById('amount-input');
            amountInput.value = currentAmount;
            
            // Mettre à jour l'affichage immédiatement
            updateAmountDisplay(currentAmount);
            
            modal.show();
        }

        function updateAmountDisplay(value) {
            if (currentScheduleId) {
                const amountSpan = document.getElementById(`amount-display-${currentScheduleId}`);
                if (amountSpan) {
                    // Formatage du nombre avec espace comme séparateur de milliers
                    const formattedValue = parseInt(value || 0).toLocaleString('fr-FR');
                    amountSpan.textContent = formattedValue;
                }
            }
        }
    </script>
</x-app-layout>