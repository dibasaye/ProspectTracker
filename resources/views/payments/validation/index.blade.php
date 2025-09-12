<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 fw-bold">
                <i class="fas fa-check-circle me-2"></i>Validation des Paiements
            </h2>
            <div>
                <a href="{{ route('payments.validation.history') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-history me-1"></i>Historique
                </a>
                <a href="{{ route('payments.validation.statistics') }}" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-chart-bar me-1"></i>Statistiques
                </a>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistiques rapides -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-start border-4 border-warning shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">En attente</div>
                            <div class="h3 mb-0">{{ $stats['pending_count'] ?? 0 }}</div>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-clock fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-4 border-success shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Validés aujourd'hui</div>
                            <div class="h3 mb-0">{{ $stats['validated_today'] ?? 0 }}</div>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-4 border-info shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Montant total</div>
                            <div class="h3 mb-0">{{ number_format($stats['total_amount'] ?? 0, 0, ',', ' ') }} FCFA</div>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-4 border-primary shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Votre rôle</div>
                            <div class="h6 mb-0">{{ ucfirst(auth()->user()->getRoleNames()->first() ?? 'Utilisateur') }}</div>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-user-shield fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des paiements en attente -->
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <h6 class="mb-0 text-gray-800">
                <i class="fas fa-list me-2"></i>Paiements en attente de validation
            </h6>
        </div>
        <div class="card-body p-0">
            @if($pendingPayments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Référence</th>
                                <th>Client</th>
                                <th>Site/Lot</th>
                                <th>Montant</th>
                                <th>Type</th>
                                <th>Méthode</th>
                                <th>Date Paiement</th>
                                <th>Statut</th>
                                <th>Dernière action</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingPayments as $payment)
                            <tr>
                                <td>
                                    <span class="badge bg-secondary">{{ $payment->reference_number }}</span>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $payment->client->full_name }}</div>
                                    <small class="text-muted">{{ $payment->client->phone }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $payment->site->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $payment->lot->reference ?? 'N/A' }}</small>
                                </td>
                                <td class="text-end">
                                    <div class="fw-bold text-success">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</div>
                                    <small class="text-muted">{{ $payment->getFormattedAmountAttribute() }}</small>
                                </td>
                                <td>
                             <span class="badge bg-info text-dark">{{ ucfirst($payment->type ?? 'N/A') }}</span>
                             </td>


                                <td>
                                    @php
                                        $methodColors = [
                                            'especes' => 'success',
                                            'virement' => 'primary',
                                            'cheque' => 'info',
                                            'mobile_money' => 'warning'
                                        ];
                                        $color = $methodColors[$payment->payment_method] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">
                                        {{ ucfirst($payment->payment_method) }}
                                    </span>
                                </td>
                                <td>
                                    <div>{{ $payment->payment_date->format('d/m/Y') }}</div>
                                    <small class="text-muted">{{ $payment->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'caissier_validated' => 'info',
                                            'responsable_validated' => 'primary',
                                            'admin_validated' => 'success',
                                            'completed' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $statusText = $payment->getValidationStatusText();
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$payment->validation_status] ?? 'secondary' }}">
                                        {{ $statusText }}
                                    </span>
                                </td>
                                <td>
                                    @if($payment->adminValidatedBy)
                                        <div>Admin: {{ $payment->adminValidatedBy->name }}</div>
                                    @elseif($payment->responsableValidatedBy)
                                        <div>Resp: {{ $payment->responsableValidatedBy->name }}</div>
                                    @elseif($payment->caissierValidatedBy)
                                        <div>Caissier: {{ $payment->caissierValidatedBy->name }}</div>
                                    @else
                                        <span class="text-muted">En attente</span>
                                    @endif
                                    <small class="text-muted">
                                        {{ $payment->updated_at->format('d/m/Y H:i') }}
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('payments.validation.show', $payment) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>Voir
                                        </a>
                                        @if(auth()->user()->role === 'caissier' && $payment->validation_status === 'pending')
                                            <button type="button" class="btn btn-sm btn-success" 
                                                onclick="event.preventDefault(); showValidationModal({{ $payment->id }}, '{{ $payment->reference_number }}', {{ $payment->amount }});">
                                            <i class="fas fa-check me-1"></i>Valider (Caissier)
                                            </button>
                                        @elseif(auth()->user()->role === 'responsable_commercial' && $payment->validation_status === 'caissier_validated')
                                            <button type="button" class="btn btn-sm btn-success"
                                                onclick="event.preventDefault(); showValidationModal(
                                                    {{ $payment->id }}, 
                                                    '{{ $payment->reference_number }}', 
                                                    {{ $payment->amount }}, 
                                                    '{{ $payment->payment_proof_path ? asset('storage/' . $payment->payment_proof_path) : '' }}'
                                                );">
                                                <i class="fas fa-check me-1"></i>Valider (Manager)
                                            </button>
                                        @elseif(auth()->user()->role === 'administrateur' && $payment->validation_status === 'responsable_validated')
                                            <button type="button" class="btn btn-sm btn-success"
                                                onclick="event.preventDefault(); showAdminValidationModal(
                                                    {{ $payment->id }}, 
                                                    '{{ $payment->reference_number }}', 
                                                    '{{ $payment->payment_proof_path ? asset('storage/' . $payment->payment_proof_path) : '' }}'
                                                );">
                                                <i class="fas fa-check-circle me-1"></i>Valider (Admin)
                                            </button>
                                        @endif
                                        
                                        @if($payment->receipt_url)
                                            <a href="{{ asset('storage/' . $payment->receipt_url) }}" 
                                               class="btn btn-sm btn-info" target="_blank">
                                                <i class="fas fa-file-invoice me-1"></i>Facture
                                            </a>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="showRejectionModal({{ $payment->id }}, '{{ $payment->reference_number }}')">
                                            <i class="fas fa-times me-1"></i>Rejeter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="card-footer bg-light">
                    {{ $pendingPayments->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 class="text-muted">Aucun paiement en attente</h5>
                    <p class="text-muted">Tous les paiements ont été validés !</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal de validation -->
    <div class="modal fade" id="validationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Valider le paiement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="validationForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Référence</label>
                            <input type="text" id="paymentReference" class="form-control" readonly>
                        </div>
                        @if(auth()->user()->isCaissier())
                            <div class="mb-3">
                                <label for="actual_amount_received" class="form-label">Montant réel reçu (FCFA)</label>
                                <input type="number" name="actual_amount_received" id="actual_amount_received" 
                                       class="form-control" required min="0" step="100">
                                <div class="form-text">Confirmez le montant exact reçu</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirmation_notes" class="form-label">Notes de confirmation</label>
                                <textarea name="confirmation_notes" id="confirmation_notes" 
                                          class="form-control" rows="3" 
                                          placeholder="Observations, commentaires..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="payment_proof" class="form-label">Justificatif de paiement</label>
                                <input type="file" name="payment_proof" id="payment_proof" 
                                       class="form-control" required>
                                <div class="form-text">Format accepté : JPG, PNG, PDF (max 2MB)</div>
                            </div>
                        @else
                            <div class="mb-3">
                                <h6>Justificatif fourni par le caissier :</h6>
                                <div id="paymentProofPreview" class="text-center mb-3 p-3 bg-light rounded">
                                    <!-- Preview du justificatif -->
                                    <p class="text-muted">Chargement du justificatif...</p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="manager_notes" class="form-label">Notes pour la validation</label>
                                <textarea name="manager_notes" id="manager_notes" 
                                          class="form-control" rows="3" 
                                          placeholder="Observations, commentaires..."></textarea>
                            </div>
                            <!-- Champ de notes uniquement, pas de justificatif de validation nécessaire -->
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>Confirmer la validation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de rejet -->
    <div class="modal fade" id="rejectionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rejeter le paiement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="rejectionForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Référence</label>
                            <input type="text" id="rejectionReference" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label">Raison du rejet *</label>
                            <textarea name="rejection_reason" id="rejection_reason" 
                                      class="form-control" rows="3" required
                                      placeholder="Expliquez pourquoi ce paiement est rejeté..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-1"></i>Confirmer le rejet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de validation Admin -->
    <div class="modal fade" id="adminValidationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Validation Administrateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="adminValidationForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Référence</label>
                                    <input type="text" id="adminPaymentReference" class="form-control" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="admin_notes" class="form-label">Notes de validation</label>
                                    <textarea name="admin_notes" id="admin_notes" 
                                              class="form-control" rows="5" 
                                              placeholder="Observations, commentaires..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Justificatif de paiement</label>
                                    <div id="adminPaymentProofPreview" class="text-center mb-3 p-3 bg-light rounded" style="min-height: 200px;">
                                        <p class="text-muted">Chargement du justificatif...</p>
                                    </div>
                                    <div class="text-center">
                                        <a href="#" id="adminDownloadProofBtn" class="btn btn-sm btn-outline-primary" target="_blank" style="display: none;">
                                            <i class="fas fa-download me-1"></i>Télécharger le justificatif
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            En validant ce paiement, vous finalisez le processus de validation.
                            Une facture sera générée automatiquement.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check-circle me-1"></i>Valider définitivement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

   @push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.showValidationModal = function(paymentId, reference, amount, paymentProofUrl = null) {
            const paymentReferenceInput = document.getElementById('paymentReference');
            const amountInput = document.getElementById('actual_amount_received');
            const form = document.getElementById('validationForm');
            const paymentProofPreview = document.getElementById('paymentProofPreview');
            const paymentProofSection = document.getElementById('paymentProofSection');
            
            // Réinitialiser le formulaire
            if (form) form.reset();
            
            // Mettre à jour les champs de base
            if (paymentReferenceInput) paymentReferenceInput.value = reference;
            if (amountInput) amountInput.value = amount;
            
            // Mettre à jour l'action du formulaire avec l'ID du paiement
            const isCaissier = {{ auth()->user()->isCaissier() ? 'true' : 'false' }};
            if (form) {
                form.action = isCaissier 
                    ? `/payments/${paymentId}/validate` 
                    : `/payments/validation/${paymentId}/validate`;
            }
            
            // Gestion du mode caissier vs manager
            if (!isCaissier) {
                // Mode Manager - Afficher le justificatif du caissier
                if (paymentProofPreview) {
                    if (paymentProofUrl && paymentProofUrl.trim() !== '') {
                        // Afficher un indicateur de chargement
                        paymentProofPreview.innerHTML = `
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p class="mt-2">Chargement du justificatif...</p>
                            </div>
                        `;
                        
                        // Charger l'aperçu du justificatif
                        if (paymentProofUrl.match(/\.(jpe?g|png|gif)$/i)) {
                            // Pour les images, afficher directement
                            const img = new Image();
                            img.onload = function() {
                                paymentProofPreview.innerHTML = `
                                    <div class="d-flex justify-content-center">
                                        <img src="${paymentProofUrl}" class="img-fluid rounded border" 
                                             style="max-height: 400px; max-width: 100%;" 
                                             alt="Justificatif de paiement">
                                    </div>
                                    <div class="mt-2 text-center">
                                        <a href="${paymentProofUrl}" class="btn btn-sm btn-outline-primary" 
                                           target="_blank" download>
                                            <i class="fas fa-download me-1"></i>Télécharger l'image
                                        </a>
                                    </div>
                                `;
                            };
                            img.onerror = function() {
                                paymentProofPreview.innerHTML = `
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Impossible de charger le justificatif.
                                        <a href="${paymentProofUrl}" class="alert-link" target="_blank">
                                            Essayer d'ouvrir dans un nouvel onglet
                                        </a>
                                    </div>
                                `;
                            };
                            img.src = paymentProofUrl;
                        } else if (paymentProofUrl.match(/\.pdf$/i)) {
                            // Pour les PDF, utiliser un iframe
                            paymentProofPreview.innerHTML = `
                                <div style="height: 500px;">
                                    <iframe src="${paymentProofUrl}" 
                                            class="w-100 h-100 border rounded" 
                                            style="min-height: 400px;">
                                    </iframe>
                                </div>
                                <div class="mt-2 text-center">
                                    <a href="${paymentProofUrl}" class="btn btn-sm btn-outline-primary" 
                                       target="_blank" download>
                                        <i class="fas fa-download me-1"></i>Télécharger le document
                                    </a>
                                </div>
                            `;
                        } else {
                            // Pour les autres types de fichiers
                            paymentProofPreview.innerHTML = `
                                <div class="alert alert-info">
                                    <i class="fas fa-file-alt me-2"></i>
                                    Fichier joint : 
                                    <a href="${paymentProofUrl}" class="alert-link" target="_blank" download>
                                        Télécharger le document
                                    </a>
                                </div>
                            `;
                        }
                    } else {
                        // Aucun justificatif disponible
                        paymentProofPreview.innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Aucun justificatif disponible pour ce paiement.
                            </div>
                        `;
                    }
                }
            } else if (paymentProofSection) {
                // Mode Caissier - Cacher la section du justificatif si elle existe
                paymentProofSection.classList.add('d-none');
            }
            
            // Afficher le modal
            const modalElement = document.getElementById('validationModal');
            if (modalElement) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        };

        window.showRejectionModal = function(paymentId, reference) {
            const rejectionReferenceInput = document.getElementById('rejectionReference');
            const form = document.getElementById('rejectionForm');
            
            // Réinitialiser le formulaire
            if (form) form.reset();
            
            if (rejectionReferenceInput) rejectionReferenceInput.value = reference;
            if (form) form.action = `/payments/validation/${paymentId}/reject`;
            
            const modal = new bootstrap.Modal(document.getElementById('rejectionModal'));
            modal.show();
        };

        // Fonction simplifiée pour la validation admin (même structure que les autres)
        window.showAdminValidationModal = function(paymentId, reference, paymentProofUrl = null) {
            const modal = new bootstrap.Modal(document.getElementById('adminValidationModal'));
            const form = document.getElementById('adminValidationForm');
            const referenceInput = document.getElementById('adminPaymentReference');
            const paymentProofPreview = document.getElementById('adminPaymentProofPreview');
            const downloadProofBtn = document.getElementById('adminDownloadProofBtn');
            
            // Réinitialiser le formulaire
            if (form) form.reset();
            
            // Mettre à jour les valeurs du formulaire
            if (referenceInput) referenceInput.value = reference;
            
            // Mettre à jour l'action du formulaire
            if (form) form.action = `/payments/validation/${paymentId}/admin`;
            
            // Afficher le justificatif de paiement s'il existe
            if (paymentProofPreview) {
                if (paymentProofUrl && paymentProofUrl.trim() !== '') {
                    // Afficher un indicateur de chargement
                    paymentProofPreview.innerHTML = `
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="mt-2 mb-0">Chargement du justificatif...</p>
                        </div>`;
                    
                    // Vérifier le type de fichier et l'afficher appropriément
                    if (paymentProofUrl.match(/\.(jpe?g|png|gif)$/i)) {
                        // C'est une image
                        const img = new Image();
                        img.onload = function() {
                            paymentProofPreview.innerHTML = `
                                <div class="d-flex justify-content-center">
                                    <img src="${paymentProofUrl}" class="img-fluid rounded border" 
                                         style="max-height: 400px; max-width: 100%;" 
                                         alt="Justificatif de paiement">
                                </div>
                                <p class="text-muted mt-2 mb-0 text-center">Justificatif fourni par le caissier</p>
                            `;
                            // Afficher le bouton de téléchargement
                            if (downloadProofBtn) {
                                downloadProofBtn.href = paymentProofUrl;
                                downloadProofBtn.style.display = 'inline-block';
                            }
                        };
                        
                        img.onerror = function() {
                            paymentProofPreview.innerHTML = `
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Impossible de charger l'image. 
                                    <a href="${paymentProofUrl}" class="alert-link" target="_blank">
                                        Ouvrir dans un nouvel onglet
                                    </a>
                                </div>
                            `;
                            if (downloadProofBtn) {
                                downloadProofBtn.href = paymentProofUrl;
                                downloadProofBtn.style.display = 'inline-block';
                            }
                        };
                        
                        img.src = paymentProofUrl;
                    } else if (paymentProofUrl.match(/\.pdf$/i)) {
                        // C'est un PDF
                        paymentProofPreview.innerHTML = `
                            <div style="height: 400px;">
                                <iframe src="${paymentProofUrl}" 
                                        class="w-100 h-100 border rounded" 
                                        style="min-height: 300px;">
                                </iframe>
                            </div>
                            <p class="text-muted mt-2 mb-0 text-center">Justificatif fourni par le caissier (PDF)</p>
                        `;
                        if (downloadProofBtn) {
                            downloadProofBtn.href = paymentProofUrl;
                            downloadProofBtn.style.display = 'inline-block';
                        }
                    } else {
                        // Autre type de fichier
                        paymentProofPreview.innerHTML = `
                            <div class="text-center py-3">
                                <i class="fas fa-file-alt fa-3x text-primary mb-2"></i>
                                <p class="mb-1">Document de justificatif disponible</p>
                                <p class="text-muted small mb-0">Format: ${paymentProofUrl.split('.').pop().toUpperCase()}</p>
                            </div>
                        `;
                        if (downloadProofBtn) {
                            downloadProofBtn.href = paymentProofUrl;
                            downloadProofBtn.style.display = 'inline-block';
                        }
                    }
                } else {
                    // Aucun justificatif disponible
                    paymentProofPreview.innerHTML = `
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Aucun justificatif n'a été fourni avec ce paiement.
                        </div>
                    `;
                    if (downloadProofBtn) {
                        downloadProofBtn.style.display = 'none';
                    }
                }
            }
            
            // Afficher la modale
            modal.show();
        };
    });
</script>
@endpush
</x-app-layout>