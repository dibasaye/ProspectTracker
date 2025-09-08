<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold">
                    <i class="fas fa-calendar-day me-2"></i>Créer un bordereau journalier
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('receipts.index') }}">Bordereaux</a></li>
                        <li class="breadcrumb-item active">Bordereau journalier</li>
                    </ol>
                </nav>
            </div>
            <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour
            </a>
        </div>
    </x-slot>

    <div class="container py-4">
        <form action="{{ route('receipts.store-daily') }}" method="POST">
            @csrf
            
            <div class="row">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-list-check me-2"></i>Paiements à inclure
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($validatedPayments->count() > 0)
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                        <label class="form-check-label fw-bold" for="selectAll">
                                            Sélectionner tous les paiements
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="50">
                                                    <input type="checkbox" id="selectAllTable" class="form-check-input">
                                                </th>
                                                <th>Client</th>
                                                <th>Site/Lot</th>
                                                <th>Type</th>
                                                <th>Montant</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($validatedPayments as $payment)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" name="payment_ids[]" 
                                                               value="{{ $payment->id }}" 
                                                               class="form-check-input payment-checkbox" 
                                                               checked>
                                                    </td>
                                                    <td>
                                                        <strong>{{ $payment->client->first_name }} {{ $payment->client->last_name }}</strong>
                                                        <br><small class="text-muted">{{ $payment->client->phone }}</small>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold">{{ $payment->site->name }}</span>
                                                        @if($payment->lot)
                                                            <br><small class="text-primary">Lot {{ $payment->lot->number }}</small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $payment->type === 'adhesion' ? 'bg-primary' : ($payment->type === 'reservation' ? 'bg-success' : 'bg-info') }}">
                                                            {{ ucfirst($payment->type) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <strong>{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</strong>
                                                    </td>
                                                    <td>
                                                        {{ $payment->payment_date->format('d/m/Y') }}
                                                        <br><small class="text-muted">{{ $payment->updated_at->format('H:i') }}</small>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Aucun paiement à inclure</h5>
                                    <p class="text-muted">Tous les paiements validés d'aujourd'hui sont déjà dans des bordereaux</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-cog me-2"></i>Configuration
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="receipt_date" class="form-label fw-bold">
                                    <i class="fas fa-calendar me-1"></i>Date du bordereau
                                </label>
                                <input type="date" class="form-control @error('receipt_date') is-invalid @enderror" 
                                       id="receipt_date" name="receipt_date" 
                                       value="{{ old('receipt_date', now()->toDateString()) }}" required>
                                @error('receipt_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label fw-bold">
                                    <i class="fas fa-sticky-note me-1"></i>Notes (optionnel)
                                </label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" 
                                          id="notes" name="notes" rows="4" 
                                          placeholder="Notes ou observations...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Résumé automatique -->
                            <div class="border-top pt-3">
                                <h6 class="fw-bold text-dark">Résumé</h6>
                                <div id="summary">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Paiements sélectionnés:</span>
                                        <span class="fw-bold" id="selected-count">{{ $validatedPayments->count() }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Montant total:</span>
                                        <span class="fw-bold text-success" id="total-amount">
                                            {{ number_format($validatedPayments->sum('amount'), 0, ',', ' ') }} FCFA
                                        </span>
                                    </div>
                                    
                                    <!-- Détails par type -->
                                    @php
                                        $adhesions = $validatedPayments->where('type', 'adhesion');
                                        $reservations = $validatedPayments->where('type', 'reservation');
                                        $mensualites = $validatedPayments->where('type', 'mensualite');
                                    @endphp
                                    
                                    @if($adhesions->count() > 0)
                                        <div class="small text-muted mb-1">
                                            <i class="fas fa-circle text-primary me-1"></i>
                                            Adhésions: {{ $adhesions->count() }} ({{ number_format($adhesions->sum('amount'), 0, ',', ' ') }} FCFA)
                                        </div>
                                    @endif
                                    
                                    @if($reservations->count() > 0)
                                        <div class="small text-muted mb-1">
                                            <i class="fas fa-circle text-success me-1"></i>
                                            Réservations: {{ $reservations->count() }} ({{ number_format($reservations->sum('amount'), 0, ',', ' ') }} FCFA)
                                        </div>
                                    @endif
                                    
                                    @if($mensualites->count() > 0)
                                        <div class="small text-muted mb-1">
                                            <i class="fas fa-circle text-info me-1"></i>
                                            Mensualités: {{ $mensualites->count() }} ({{ number_format($mensualites->sum('amount'), 0, ',', ' ') }} FCFA)
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($validatedPayments->count() > 0)
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Créer le bordereau
                            </button>
                            <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllMain = document.getElementById('selectAll');
            const selectAllTable = document.getElementById('selectAllTable');
            const checkboxes = document.querySelectorAll('.payment-checkbox');
            const selectedCount = document.getElementById('selected-count');
            const totalAmount = document.getElementById('total-amount');

            // Synchroniser les checkboxes "Sélectionner tout"
            selectAllMain?.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                if (selectAllTable) selectAllTable.checked = this.checked;
                updateSummary();
            });

            selectAllTable?.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                if (selectAllMain) selectAllMain.checked = this.checked;
                updateSummary();
            });

            // Écouter les changements individuels
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSummary);
            });

            function updateSummary() {
                const selectedCheckboxes = document.querySelectorAll('.payment-checkbox:checked');
                let count = selectedCheckboxes.length;
                let total = 0;

                selectedCheckboxes.forEach(cb => {
                    const row = cb.closest('tr');
                    const amountText = row.querySelector('td:nth-child(5) strong').textContent;
                    const amount = parseInt(amountText.replace(/[^\d]/g, ''));
                    total += amount;
                });

                selectedCount.textContent = count;
                totalAmount.textContent = new Intl.NumberFormat('fr-FR').format(total) + ' FCFA';

                // Mettre à jour les checkboxes "Sélectionner tout"
                const allSelected = count === checkboxes.length && count > 0;
                if (selectAllMain) selectAllMain.checked = allSelected;
                if (selectAllTable) selectAllTable.checked = allSelected;
            }
        });
    </script>
</x-app-layout>