<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold">
                    <i class="fas fa-receipt me-2"></i>Bordereaux de versement
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Bordereaux</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('receipts.create-daily') }}" class="btn btn-primary">
                    <i class="fas fa-calendar-day me-2"></i>Bordereau journalier
                </a>
                <a href="{{ route('receipts.create-period') }}" class="btn btn-success">
                    <i class="fas fa-calendar-alt me-2"></i>Bordereau période
                </a>
            </div>
        </div>
    </x-slot>

    <div class="container py-4">
        <!-- Statistiques -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-receipt fa-2x text-primary mb-3"></i>
                        <h5 class="mb-1">{{ $stats['total_count'] }}</h5>
                        <small class="text-muted">Total bordereaux</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-edit fa-2x text-warning mb-3"></i>
                        <h5 class="mb-1">{{ $stats['draft_count'] }}</h5>
                        <small class="text-muted">Brouillons</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                        <h5 class="mb-1">{{ $stats['finalized_count'] }}</h5>
                        <small class="text-muted">Finalisés</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="fas fa-money-bill-wave fa-2x text-info mb-3"></i>
                        <h5 class="mb-1">{{ number_format($stats['today_amount'], 0, ',', ' ') }}</h5>
                        <small class="text-muted">FCFA aujourd'hui</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtres</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('receipts.index') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Date début</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="{{ request('date_from') }}">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Date fin</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="{{ request('date_to') }}">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="status" class="form-label">Statut</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Tous</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                                <option value="finalized" {{ request('status') === 'finalized' ? 'selected' : '' }}>Finalisé</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulé</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">Tous</option>
                                <option value="daily" {{ request('type') === 'daily' ? 'selected' : '' }}>Journalier</option>
                                <option value="period" {{ request('type') === 'period' ? 'selected' : '' }}>Période</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Filtrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des bordereaux -->
        <div class="card border-0 shadow-sm">
            <div class="card-header">
                <h6 class="mb-0">Liste des bordereaux</h6>
            </div>
            <div class="card-body p-0">
                @if($receipts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Numéro</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Période</th>
                                    <th>Montant</th>
                                    <th>Paiements</th>
                                    <th>Statut</th>
                                    <th>Généré par</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($receipts as $receipt)
                                    <tr>
                                        <td>
                                            <strong class="text-primary">{{ $receipt->receipt_number }}</strong>
                                        </td>
                                        <td>{{ $receipt->receipt_date->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="badge {{ $receipt->type === 'daily' ? 'bg-info' : 'bg-warning' }}">
                                                {{ $receipt->type === 'daily' ? 'Journalier' : 'Période' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($receipt->type === 'daily')
                                                {{ $receipt->period_start->format('d/m/Y') }}
                                            @else
                                                {{ $receipt->period_start->format('d/m') }} - {{ $receipt->period_end->format('d/m/Y') }}
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ number_format($receipt->total_amount, 0, ',', ' ') }} FCFA</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $receipt->payment_count }} paiements</span>
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: {{ $receipt->status_color }}">
                                                {{ $receipt->status_label }}
                                            </span>
                                        </td>
                                        <td>{{ $receipt->generatedBy->name }}</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('receipts.show', $receipt) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                @if($receipt->status === 'draft')
                                                    <form action="{{ route('receipts.finalize', $receipt) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success"
                                                                onclick="return confirm('Finaliser ce bordereau ?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                
                                                @if($receipt->status === 'finalized')
                                                    <a href="{{ route('receipts.pdf', $receipt) }}" 
                                                       class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                @endif
                                                
                                                @if($receipt->status === 'draft')
                                                    <form action="{{ route('receipts.destroy', $receipt) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('Supprimer ce bordereau ?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-3 py-2">
                        {{ $receipts->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucun bordereau trouvé</h5>
                        <p class="text-muted">Créez votre premier bordereau de versement</p>
                        <a href="{{ route('receipts.create-daily') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Créer un bordereau
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>