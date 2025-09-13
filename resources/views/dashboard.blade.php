@if(auth()->user()->isCaissier())
    <!-- Redirection ou message pour le caissier -->
    <x-app-layout>
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Accès non autorisé') }}
            </h2>
        </x-slot>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="mb-4">
                            <svg class="mx-auto h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Accès restreint</h3>
                        <p class="text-gray-600 mb-6">En tant que caissier, vous n'avez pas accès au tableau de bord. Votre rôle se limite à la validation des paiements.</p>
                        <a href="{{ route('payments.validation.index') }}" 
                           class="btn btn-primary">
                            <i class="fas fa-check-circle me-2"></i>
                            Aller à la validation des paiements
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </x-app-layout>

    <script>
        // Redirection automatique après 3 secondes
        setTimeout(function() {
            window.location.href = "{{ route('payments.validation.index') }}";
        }, 3000);
    </script>
@else
    <!-- Tableau de bord principal avec Bootstrap -->
    <x-app-layout>
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tableau de bord') }} - 
                @if(auth()->user()->isAdmin())
                    Super Admin
                @elseif(auth()->user()->isManager())
                    Responsable Commercial
                @else
                    Commercial
                @endif
            </h2>
        </x-slot>

        <!-- Custom CSS pour les couleurs orange, marron et blanc -->
        <style>
            :root {
                --primary-orange: #FF8C00;
                --secondary-brown: #8B4513;
                --light-orange: #FFA500;
                --dark-brown: #654321;
                --cream-white: #FFFAF0;
                --light-brown: #D2B48C;
            }

            .bg-primary-orange { background-color: var(--primary-orange) !important; }
            .bg-secondary-brown { background-color: var(--secondary-brown) !important; }
            .bg-light-orange { background-color: var(--light-orange) !important; }
            .bg-cream-white { background-color: var(--cream-white) !important; }
            .bg-light-brown { background-color: var(--light-brown) !important; }

            .text-primary-orange { color: var(--primary-orange) !important; }
            .text-secondary-brown { color: var(--secondary-brown) !important; }
            .text-dark-brown { color: var(--dark-brown) !important; }

            .border-primary-orange { border-color: var(--primary-orange) !important; }
            .border-secondary-brown { border-color: var(--secondary-brown) !important; }

            .btn-orange {
                background-color: var(--primary-orange);
                border-color: var(--primary-orange);
                color: white;
            }

            .btn-orange:hover {
                background-color: var(--light-orange);
                border-color: var(--light-orange);
                color: white;
            }

            .btn-brown {
                background-color: var(--secondary-brown);
                border-color: var(--secondary-brown);
                color: white;
            }

            .btn-brown:hover {
                background-color: var(--dark-brown);
                border-color: var(--dark-brown);
                color: white;
            }

            .card-orange {
                border-left: 4px solid var(--primary-orange);
                background: linear-gradient(135deg, #FFFAF0 0%, #FFF8DC 100%);
            }

            .card-brown {
                border-left: 4px solid var(--secondary-brown);
                background: linear-gradient(135deg, #FFFAF0 0%, #F5E6D3 100%);
            }

            .progress-orange .progress-bar {
                background-color: var(--primary-orange);
            }

            .progress-brown .progress-bar {
                background-color: var(--secondary-brown);
            }

            .status-circle {
                width: 12px;
                height: 12px;
                border-radius: 50%;
                display: inline-block;
                margin-right: 5px;
            }

            .status-green { background-color: #28a745; }
            .status-orange { background-color: var(--primary-orange); }
            .status-red { background-color: #dc3545; }

            .chart-container {
                position: relative;
                height: 300px;
                margin: 20px 0;
            }

            .metric-card {
                transition: transform 0.2s;
            }

            .metric-card:hover {
                transform: translateY(-5px);
            }
        </style>

        <div class="container-fluid py-4">

            <!-- Section des filtres -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card card-orange shadow">
                        <div class="card-header bg-light-orange text-white">
                            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtres du Dashboard
                            @if(config('app.debug') && (request()->has('site_id') || request()->has('commercial_id') || request()->has('period') || request()->has('year')))
                                <small class="ms-2">[Filtres actifs: 
                                @if(request('site_id')) Site: {{ request('site_id') }} @endif
                                @if(request('commercial_id')) Commercial: {{ request('commercial_id') }} @endif
                                @if(request('period')) Mois: {{ ucfirst(request('period')) }} @endif
                                @if(request('year')) Année: {{ request('year') }} @endif
                                ]</small>
                            @endif
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="{{ route('dashboard') }}">
                                <div class="row g-3">
                                    @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                                        <div class="col-lg-3 col-md-6">
                                            <label for="site_filter" class="form-label text-secondary-brown">Site</label>
                                            <select name="site_id" id="site_filter" class="form-select">
                                                <option value="">Tous les sites</option>
                                                @foreach(\App\Models\Site::all() as $site)
                                                    <option value="{{ $site->id }}" {{ request('site_id') == $site->id ? 'selected' : '' }}>
                                                        {{ $site->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        <div class="col-lg-3 col-md-6">
                                            <label for="commercial_filter" class="form-label text-secondary-brown">Commercial</label>
                                            <select name="commercial_id" id="commercial_filter" class="form-select">
                                                <option value="">Tous les commerciaux</option>
                                                @foreach(\App\Models\User::where('role', 'commercial')->get() as $commercial)
                                                    <option value="{{ $commercial->id }}" {{ request('commercial_id') == $commercial->id ? 'selected' : '' }}>
                                                        {{ $commercial->full_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                    
                                    <div class="col-lg-3 col-md-6">
                                        <label for="period_filter" class="form-label text-secondary-brown">Mois</label>
                                        <select name="period" id="period_filter" class="form-select">
                                            <option value="">Tous les mois</option>
                                            <option value="january" {{ request('period') == 'january' ? 'selected' : '' }}>Janvier</option>
                                            <option value="february" {{ request('period') == 'february' ? 'selected' : '' }}>Février</option>
                                            <option value="march" {{ request('period') == 'march' ? 'selected' : '' }}>Mars</option>
                                            <option value="april" {{ request('period') == 'april' ? 'selected' : '' }}>Avril</option>
                                            <option value="may" {{ request('period') == 'may' ? 'selected' : '' }}>Mai</option>
                                            <option value="june" {{ request('period') == 'june' ? 'selected' : '' }}>Juin</option>
                                            <option value="july" {{ request('period') == 'july' ? 'selected' : '' }}>Juillet</option>
                                            <option value="august" {{ request('period') == 'august' ? 'selected' : '' }}>Août</option>
                                            <option value="september" {{ request('period') == 'september' ? 'selected' : '' }}>Septembre</option>
                                            <option value="october" {{ request('period') == 'october' ? 'selected' : '' }}>Octobre</option>
                                            <option value="november" {{ request('period') == 'november' ? 'selected' : '' }}>Novembre</option>
                                            <option value="december" {{ request('period') == 'december' ? 'selected' : '' }}>Décembre</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-6">
                                        <label for="year_filter" class="form-label text-secondary-brown">Année</label>
                                        <select name="year" id="year_filter" class="form-select">
                                            @php
                                                $currentYear = date('Y');
                                                $startYear = $currentYear - 2; // 2 ans en arrière
                                                $endYear = $currentYear + 1;   // 1 an en avant
                                            @endphp
                                            <option value="">Toutes les années</option>
                                            @for($year = $endYear; $year >= $startYear; $year--)
                                                <option value="{{ $year }}" {{ request('year', $currentYear) == $year ? 'selected' : '' }}>{{ $year }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Boutons sur une nouvelle ligne -->
                                <div class="row mt-3">
                                    <div class="col-12 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-orange me-2">
                                            <i class="fas fa-filter me-1"></i>Appliquer les filtres
                                        </button>
                                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-redo me-1"></i>Réinitialiser
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if(auth()->user()->isAdmin())
                <!-- Dashboard Super Admin / Direction -->
                <div data-section="admin">
                    <!-- Métriques principales -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="card metric-card card-orange shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-secondary-brown mb-1">Terrains Vendus</h6>
                                            <h3 class="counter text-primary-orange mb-0">{{ $stats['sold_lots'] }}</h3>
                                            <small class="text-muted">
                                                @if(request('period') || request('year') || request('site_id') || request('commercial_id'))
                                                    Selon filtres appliqués
                                                @else
                                                    Total global
                                                @endif
                                            </small>
                                        </div>
                                        <div class="text-primary-orange">
                                            <i class="fas fa-map-marked-alt fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="card metric-card card-brown shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-secondary-brown mb-1">Total Ventes</h6>
                                            <h3 class="counter text-secondary-brown mb-0">{{ number_format($stats['total_sales'], 0, ',', ' ') }}</h3>
                                            <small class="text-muted">FCFA</small>
                                        </div>
                                        <div class="text-secondary-brown">
                                            <i class="fas fa-chart-line fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="card metric-card card-orange shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-secondary-brown mb-1">Total Encaissé</h6>
                                            <h3 class="counter text-primary-orange mb-0">{{ number_format($stats['total_payments'], 0, ',', ' ') }}</h3>
                                            <small class="text-muted">FCFA</small>
                                        </div>
                                        <div class="text-primary-orange">
                                            <i class="fas fa-money-bill-wave fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6">
                            <div class="card metric-card card-brown shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-secondary-brown mb-1">À Recouvrer</h6>
                                            <h3 class="counter text-secondary-brown mb-0">{{ number_format($stats['total_to_recover'], 0, ',', ' ') }}</h3>
                                            <small class="text-muted">FCFA</small>
                                        </div>
                                        <div class="text-secondary-brown">
                                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Graphiques globaux -->
                    <div class="row mb-4">
                        <div class="col-lg-4">
                            <div class="card card-orange shadow">
                                <div class="card-header bg-light-orange text-white">
                                    <h6 class="mb-0">Ventes par Site</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="salesBySiteChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card card-brown shadow">
                                <div class="card-header bg-secondary-brown text-white">
                                    <h6 class="mb-0">Performance Commerciaux</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="salesByCommercialChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card card-orange shadow">
                                <div class="card-header bg-light-orange text-white">
                                    <h6 class="mb-0">Évolution Mensuelle</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="monthlySalesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques détaillées par site -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card card-brown shadow">
                                <div class="card-header bg-secondary-brown text-white">
                                    <h6 class="mb-0">Situation Détaillée par Site</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Site</th>
                                                    <th>Lots Total</th>
                                                    <th>Lots Vendus</th>
                                                    <th>Lots Disponibles</th>
                                                    <th>Ventes Total</th>
                                                    <th>Recouvrements</th>
                                                    <th>À Recouvrer</th>
                                                    <th>Taux Recouvrement</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($comprehensiveSiteStats as $site)
                                                    <tr>
                                                        <td class="fw-bold text-secondary-brown">{{ $site['name'] }}</td>
                                                        <td>{{ $site['total_lots'] }}</td>
                                                        <td><span class="badge bg-success">{{ $site['sold_lots'] }}</span></td>
                                                        <td><span class="badge bg-info">{{ $site['available_lots'] }}</span></td>
                                                        <td class="text-primary-orange">{{ number_format($site['total_sales'], 0, ',', ' ') }} F</td>
                                                        <td class="text-success">{{ number_format($site['total_recovered'], 0, ',', ' ') }} F</td>
                                                        <td class="text-danger">{{ number_format($site['total_to_recover'], 0, ',', ' ') }} F</td>
                                                        <td>
                                                            <div class="progress progress-orange" style="height: 20px;">
                                                                <div class="progress-bar" role="progressbar" 
                                                                    style="width: {{ $site['recovery_rate'] }}%">
                                                                    {{ number_format($site['recovery_rate'], 1) }}%
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Récents encaissements et décaissements -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card card-orange shadow">
                                <div class="card-header bg-light-orange text-white">
                                    <h6 class="mb-0">Récentes Transactions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="list-group list-group-flush">
                                        @foreach($allCashTransactions as $transaction)
                                            <div class="list-group-item border-0 px-0">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="mb-1">
                                                            @if($transaction->type == 'encaissement')
                                                                <i class="fas fa-arrow-down text-success me-2"></i>
                                                            @else
                                                                <i class="fas fa-arrow-up text-danger me-2"></i>
                                                            @endif
                                                            {{ ucfirst($transaction->type) }}
                                                        </h6>
                                                        <p class="mb-1 text-muted">{{ $transaction->description }}</p>
                                                        <small class="text-muted">{{ $transaction->transaction_date->format('d/m/Y H:i') }}</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="fw-bold {{ $transaction->type == 'encaissement' ? 'text-success' : 'text-danger' }}">
                                                            {{ $transaction->type == 'encaissement' ? '+' : '-' }}{{ number_format($transaction->amount, 0, ',', ' ') }} F
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card card-brown shadow">
                                <div class="card-header bg-secondary-brown text-white">
                                    <h6 class="mb-0">Paiements en Attente</h6>
                                </div>
                                <div class="card-body">
                                    <div class="list-group list-group-flush">
                                        @foreach($pendingPayments as $payment)
                                            <div class="list-group-item border-0 px-0">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="mb-1">{{ $payment->client ? $payment->client->full_name : 'Client non défini' }}</h6>
                                                        <p class="mb-1 text-muted">{{ $payment->site ? $payment->site->name : 'Site non défini' }}</p>
                                                        <small class="text-muted">{{ $payment->payment_date->format('d/m/Y') }}</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="fw-bold text-primary-orange">{{ number_format($payment->amount, 0, ',', ' ') }} F</span>
                                                        <br>
                                                        <span class="badge bg-warning">En attente</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif(auth()->user()->isManager())
                <!-- Dashboard Responsable Commercial -->
                <div data-section="manager">
                    <!-- Métriques pour Manager -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="card metric-card card-orange shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-secondary-brown">Total Prospects</h6>
                                    <h3 class="counter text-primary-orange">{{ $stats['total_prospects'] }}</h3>
                                    <small class="text-muted">Actifs: {{ $stats['active_prospects'] }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card metric-card card-brown shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-secondary-brown">Commerciaux</h6>
                                    <h3 class="counter text-secondary-brown">{{ count($commercials) }}</h3>
                                    <small class="text-muted">Actifs</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card metric-card card-orange shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-secondary-brown">À Dispatcher</h6>
                                    <h3 class="counter text-primary-orange">{{ $stats['prospects_to_dispatch'] }}</h3>
                                    <small class="text-muted">Prospects non assignés</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card metric-card card-brown shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-secondary-brown">Contrats Signés</h6>
                                    <h3 class="counter text-secondary-brown">{{ $stats['signed_contracts'] }}</h3>
                                    <small class="text-muted">Total</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance des commerciaux -->
                    <div class="row mb-4">
                        <div class="col-lg-8">
                            <div class="card card-orange shadow">
                                <div class="card-header bg-light-orange text-white">
                                    <h6 class="mb-0">Situation de Chaque Commercial</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Commercial</th>
                                                    <th>Clients</th>
                                                    <th>Ventes Réalisées</th>
                                                    <th>Montants Encaissés</th>
                                                    <th>À Recouvrer</th>
                                                    <th>Contrats</th>
                                                    <th>Pipeline</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($commercials as $commercial)
                                                    <tr>
                                                        <td class="fw-bold text-secondary-brown">{{ $commercial['name'] }}</td>
                                                        <td>{{ $commercial['total_clients'] }}</td>
                                                        <td class="text-primary-orange">{{ number_format($commercial['total_sales'], 0, ',', ' ') }} F</td>
                                                        <td class="text-success">{{ number_format($commercial['total_paid'], 0, ',', ' ') }} F</td>
                                                        <td class="text-danger">{{ number_format($commercial['pending_amount'], 0, ',', ' ') }} F</td>
                                                        <td>{{ $commercial['contracts_count'] }}</td>
                                                        <td>
                                                            <div class="d-flex">
                                                                <span class="badge bg-primary me-1">{{ $commercial['pipeline_status']['new_prospects'] }}</span>
                                                                <span class="badge bg-warning me-1">{{ $commercial['pipeline_status']['follow_up_prospects'] }}</span>
                                                                <span class="badge bg-success">{{ $commercial['pipeline_status']['converted_prospects'] }}</span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card card-brown shadow">
                                <div class="card-header bg-secondary-brown text-white">
                                    <h6 class="mb-0">Vue Par Site</h6>
                                </div>
                                <div class="card-body">
                                    @foreach($comprehensiveSiteStats as $site)
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span class="fw-bold">{{ $site['name'] }}</span>
                                                <span class="text-primary-orange">{{ number_format($site['recovery_rate'], 1) }}%</span>
                                            </div>
                                            <div class="progress progress-orange mb-2">
                                                <div class="progress-bar" style="width: {{ $site['recovery_rate'] }}%"></div>
                                            </div>
                                            <small class="text-muted">Ventes: {{ number_format($site['total_sales'], 0, ',', ' ') }} F | Recouvré: {{ number_format($site['total_recovered'], 0, ',', ' ') }} F</small>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prospects à dispatcher -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card card-orange shadow">
                                <div class="card-header bg-light-orange text-white d-flex justify-content-between">
                                    <h6 class="mb-0">Prospects à Dispatcher ({{ count($prospectsToDispatch) }})</h6>
                                    <a href="{{ route('prospects.index') }}" class="btn btn-sm btn-light">
                                        <i class="fas fa-plus me-1"></i>Gérer
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Nom</th>
                                                    <th>Téléphone</th>
                                                    <th>Site Intéressé</th>
                                                    <th>Statut</th>
                                                    <th>Date Création</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($prospectsToDispatch as $prospect)
                                                    <tr>
                                                        <td class="fw-bold">{{ $prospect->full_name }}</td>
                                                        <td>{{ $prospect->phone }}</td>
                                                        <td>{{ $prospect->interestedSite->name ?? 'N/A' }}</td>
                                                        <td>
                                                            <span class="badge bg-info">{{ ucfirst($prospect->status) }}</span>
                                                        </td>
                                                        <td>{{ $prospect->created_at->format('d/m/Y') }}</td>
                                                        <td>
                                                            <a href="{{ route('prospects.assign', $prospect->id) }}" class="btn btn-sm btn-orange">
                                                                <i class="fas fa-user-plus"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Échéances à venir par commercial -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-brown shadow">
                                <div class="card-header bg-secondary-brown text-white">
                                    <h6 class="mb-0">Échéances à Venir par Commercial</h6>
                                </div>
                                <div class="card-body">
                                    @foreach($commercials as $commercial)
                                        @if($commercial['upcoming_payments']->count() > 0)
                                            <h6 class="text-secondary-brown border-bottom pb-2">{{ $commercial['name'] }}</h6>
                                            <div class="row mb-3">
                                                @foreach($commercial['upcoming_payments'] as $payment)
                                                    <div class="col-md-4 mb-2">
                                                        <div class="card border-left-orange">
                                                            <div class="card-body py-2">
                                                                <small class="text-muted">{{ $payment->contract->client->full_name ?? 'N/A' }}</small>
                                                                <div class="fw-bold text-primary-orange">{{ number_format($payment->amount, 0, ',', ' ') }} F</div>
                                                                <small class="text-muted">Échéance: {{ $payment->due_date->format('d/m/Y') }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Dashboard Commercial -->
                <div data-section="commercial">
                    <!-- Métriques mensuelles avec objectifs -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="card metric-card card-orange shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-secondary-brown">Mes Prospects</h6>
                                    <h3 class="counter text-primary-orange">{{ $stats['my_prospects'] }}</h3>
                                    <small class="text-muted">Actifs: {{ $stats['active_prospects'] }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card metric-card card-brown shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-secondary-brown">Convertis</h6>
                                    <h3 class="counter text-secondary-brown">{{ $stats['converted_prospects'] }}</h3>
                                    <small class="text-muted">Ce mois</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card metric-card card-orange shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-secondary-brown">Mes Ventes</h6>
                                    <h3 class="counter text-primary-orange">{{ number_format($stats['total_sales'], 0, ',', ' ') }}</h3>
                                    <small class="text-muted">FCFA</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card metric-card card-brown shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-secondary-brown">À Recouvrer</h6>
                                    <h3 class="counter text-secondary-brown">{{ number_format($stats['total_to_recover'], 0, ',', ' ') }}</h3>
                                    <small class="text-muted">FCFA</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vue mensuelle avec objectifs -->
                    <div class="row mb-4">
                        <div class="col-lg-6">
                            <div class="card card-orange shadow">
                                <div class="card-header bg-light-orange text-white">
                                    <h6 class="mb-0">Objectifs du Mois ({{ $monthlyData['month'] }})</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Ventes</span>
                                            <span>{{ number_format($monthlyData['sales_progress'], 1) }}%</span>
                                        </div>
                                        <div class="progress progress-orange">
                                            <div class="progress-bar" style="width: {{ min($monthlyData['sales_progress'], 100) }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ number_format($monthlyData['amount_received'], 0, ',', ' ') }} / {{ number_format($monthlyData['sales_target'], 0, ',', ' ') }} F</small>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>Recouvrement</span>
                                            <span>{{ number_format($monthlyData['recovery_progress'], 1) }}%</span>
                                        </div>
                                        <div class="progress progress-brown">
                                            <div class="progress-bar" style="width: {{ min($monthlyData['recovery_progress'], 100) }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ number_format($monthlyData['amount_received'], 0, ',', ' ') }} / {{ number_format($monthlyData['recovery_target'], 0, ',', ' ') }} F</small>
                                    </div>
                                    <div class="text-center">
                                        <div class="chart-container" style="height: 200px;">
                                            <canvas id="targetProgressChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card card-brown shadow">
                                <div class="card-header bg-secondary-brown text-white">
                                    <h6 class="mb-0">Statut de Mes Clients</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height: 250px;">
                                        <canvas id="clientStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Liste détaillée des clients avec statuts colorés -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card card-orange shadow">
                                <div class="card-header bg-light-orange text-white">
                                    <h6 class="mb-0">Mes Clients avec Codes Couleurs</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Statut</th>
                                                    <th>Client</th>
                                                    <th>Site</th>
                                                    <th>Lot</th>
                                                    <th>Montant Total</th>
                                                    <th>Total Versé</th>
                                                    <th>À Recouvrer</th>
                                                    <th>Dernier Paiement</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($clientsData as $client)
                                                    <tr>
                                                        <td>
                                                            <span class="status-circle status-{{ $client['status_color'] }}"></span>
                                                            @if($client['status'] == 'À jour')
                                                                🟢 À jour
                                                            @elseif($client['status'] == 'Paiement partiel')
                                                                🟠 Paiement partiel
                                                            @else
                                                                🔴 À relancer
                                                            @endif
                                                        </td>
                                                        <td class="fw-bold">{{ $client['client_name'] }}</td>
                                                        <td>{{ $client['site_name'] }}</td>
                                                        <td>{{ $client['lot_number'] }}</td>
                                                        <td class="text-primary-orange">{{ number_format($client['total_amount'], 0, ',', ' ') }} F</td>
                                                        <td class="text-success">{{ number_format($client['total_paid'], 0, ',', ' ') }} F</td>
                                                        <td class="text-danger">{{ number_format($client['to_recover'], 0, ',', ' ') }} F</td>
                                                        <td>{{ $client['last_payment_date'] ? \Carbon\Carbon::parse($client['last_payment_date'])->format('d/m/Y') : 'Aucun' }}</td>
                                                        <td>
                                                            @if($client['status'] == 'À relancer')
                                                                <button class="btn btn-sm btn-danger" onclick="contactClient({{ $client['client_id'] }})">
                                                                    <i class="fas fa-phone"></i>
                                                                </button>
                                                            @endif
                                                            <a href="{{ route('contracts.show', $client['contract_id']) }}" class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prospects avec commentaires filtrables -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card card-brown shadow">
                                <div class="card-header bg-secondary-brown text-white d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Mes Prospects - Commentaires Filtrables</h6>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-sm btn-light filter-btn active" onclick="filterComments('all')">Tous</button>
                                        <button class="btn btn-sm btn-light filter-btn" onclick="filterComments('à rappeler')">À rappeler</button>
                                        <button class="btn btn-sm btn-light filter-btn" onclick="filterComments('en attente visite')">Visite</button>
                                        <button class="btn btn-sm btn-light filter-btn" onclick="filterComments('intéressé')">Intéressé</button>
                                        <button class="btn btn-sm btn-light filter-btn" onclick="filterComments('en réflexion')">Réflexion</button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach($prospectsWithComments as $prospect)
                                            <div class="col-lg-6 mb-3 comment-item" data-category="{{ $prospect['comment_category'] }}">
                                                <div class="card border-left-{{ $prospect['comment_category'] == 'à rappeler' ? 'warning' : ($prospect['comment_category'] == 'intéressé' ? 'success' : 'info') }}">
                                                    <div class="card-body">
                                                        <h6 class="card-title">{{ $prospect['name'] }}</h6>
                                                        <p class="card-text">
                                                            <i class="fas fa-phone me-2"></i>{{ $prospect['phone'] }}<br>
                                                            <i class="fas fa-map-marker-alt me-2"></i>{{ $prospect['site'] }}<br>
                                                            <span class="badge bg-{{ $prospect['status'] == 'interesse' ? 'success' : 'info' }}">{{ ucfirst($prospect['status']) }}</span>
                                                        </p>
                                                        <blockquote class="blockquote-footer mt-2">
                                                            <i class="fas fa-comment me-2"></i>{{ $prospect['comments'] }}
                                                            <br><small class="text-muted">Catégorie: {{ ucfirst($prospect['comment_category']) }}</small>
                                                        </blockquote>
                                                        <div class="mt-2">
                                                            <a href="{{ route('prospects.show', $prospect['id']) }}" class="btn btn-sm btn-orange me-2">
                                                                <i class="fas fa-eye"></i> Voir
                                                            </a>
                                                            @if($prospect['comment_category'] == 'à rappeler')
                                                                <button class="btn btn-sm btn-success" onclick="callProspect('{{ $prospect['phone'] }}')">
                                                                    <i class="fas fa-phone"></i> Appeler
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paiements récents et en attente -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card card-orange shadow">
                                <div class="card-header bg-light-orange text-white">
                                    <h6 class="mb-0">Mes Paiements Récents</h6>
                                </div>
                                <div class="card-body">
                                    @foreach($recentPayments as $payment)
                                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                            <div>
                                                <h6 class="mb-0">{{ $payment->client ? $payment->client->full_name : 'Client non défini' }}</h6>
                                                <small class="text-muted">{{ $payment->payment_date->format('d/m/Y') }}</small>
                                            </div>
                                            <div class="text-end">
                                                <span class="fw-bold text-success">{{ number_format($payment->amount, 0, ',', ' ') }} F</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card card-brown shadow">
                                <div class="card-header bg-secondary-brown text-white">
                                    <h6 class="mb-0">Paiements en Attente de Validation</h6>
                                </div>
                                <div class="card-body">
                                    @foreach($pendingPayments as $payment)
                                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                            <div>
                                                <h6 class="mb-0">{{ $payment->client ? $payment->client->full_name : 'Client non défini' }}</h6>
                                                <small class="text-muted">{{ $payment->payment_date->format('d/m/Y') }}</small>
                                            </div>
                                            <div class="text-end">
                                                <span class="fw-bold text-warning">{{ number_format($payment->amount, 0, ',', ' ') }} F</span>
                                                <br>
                                                <span class="badge bg-warning">En attente</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <!-- Scripts pour les graphiques -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Configuration des couleurs
                const colors = {
                    orange: '#FF8C00',
                    brown: '#8B4513',
                    lightOrange: '#FFA500',
                    lightBrown: '#D2B48C',
                    white: '#FFFFFF',
                    green: '#28a745',
                    red: '#dc3545'
                };

                @if(auth()->user()->isAdmin())
                    // Graphique des ventes par site
                    if (document.getElementById('salesBySiteChart')) {
                        const ctx = document.getElementById('salesBySiteChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: {!! json_encode($salesBySite->pluck('site_name')->toArray()) !!},
                                datasets: [{
                                    data: {!! json_encode($salesBySite->pluck('total_sales')->toArray()) !!},
                                    backgroundColor: [colors.orange, colors.brown, colors.lightOrange, colors.lightBrown]
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    }
                                }
                            }
                        });
                    }

                    // Graphique des ventes par commercial
                    if (document.getElementById('salesByCommercialChart')) {
                        const ctx2 = document.getElementById('salesByCommercialChart').getContext('2d');
                        new Chart(ctx2, {
                            type: 'bar',
                            data: {
                                labels: {!! json_encode($salesByCommercial->pluck('first_name')->toArray()) !!},
                                datasets: [{
                                    label: 'Ventes',
                                    data: {!! json_encode($salesByCommercial->pluck('total_sales')->toArray()) !!},
                                    backgroundColor: colors.orange
                                }, {
                                    label: 'Recouvrements',
                                    data: {!! json_encode($salesByCommercial->pluck('total_paid')->toArray()) !!},
                                    backgroundColor: colors.brown
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    }

                    // Graphique des ventes mensuelles
                    if (document.getElementById('monthlySalesChart')) {
                        const ctx3 = document.getElementById('monthlySalesChart').getContext('2d');
                        new Chart(ctx3, {
                            type: 'line',
                            data: {
                                labels: {!! json_encode($monthlySalesData->pluck('month')->toArray()) !!},
                                datasets: [{
                                    label: 'Ventes Mensuelles',
                                    data: {!! json_encode($monthlySalesData->pluck('total_paid')->toArray()) !!},
                                    borderColor: colors.orange,
                                    backgroundColor: colors.orange + '20',
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    }
                @elseif(auth()->user()->isManager())
                    // Graphique de performance des commerciaux pour manager
                    if (document.getElementById('commercialPerformanceChart')) {
                        const ctx = document.getElementById('commercialPerformanceChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'radar',
                            data: {
                                labels: ['Prospects', 'Conversions', 'Ventes', 'Recouvrements', 'Contrats'],
                                datasets: {!! json_encode($commercials->map(function($c, $index) {
                                    $chartColors = ['#FF8C00', '#8B4513', '#FFA500', '#D2B48C'];
                                    $color = $chartColors[$index % count($chartColors)];
                                    return [
                                        'label' => $c['name'],
                                        'data' => [
                                            $c['total_clients'],
                                            $c['pipeline_status']['converted_prospects'],
                                            $c['total_sales'] / 1000000, // En millions
                                            $c['total_paid'] / 1000000,
                                            $c['contracts_count']
                                        ],
                                        'borderColor' => $color,
                                        'backgroundColor' => $color . '20'
                                    ];
                                })) !!}
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    r: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    }
                @else
                    // Graphique des objectifs pour commercial
                    if (document.getElementById('targetProgressChart')) {
                        const ctx = document.getElementById('targetProgressChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Réalisé', 'Restant'],
                                datasets: [{
                                    data: [{{ $monthlyData['sales_progress'] }}, {{ 100 - $monthlyData['sales_progress'] }}],
                                    backgroundColor: [colors.orange, '#f0f0f0']
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                cutout: '70%',
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                }
                            }
                        });
                    }

                    // Graphique du statut des clients
                    if (document.getElementById('clientStatusChart')) {
                        const statusCounts = {!! json_encode($clientsData->groupBy('status')->map->count()) !!};
                        const ctx2 = document.getElementById('clientStatusChart').getContext('2d');
                        new Chart(ctx2, {
                            type: 'pie',
                            data: {
                                labels: Object.keys(statusCounts),
                                datasets: [{
                                    data: Object.values(statusCounts),
                                    backgroundColor: [colors.green, colors.orange, colors.red]
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    }
                                }
                            }
                        });
                    }
                @endif
            });

            // Fonctions pour les filtres de commentaires
            function filterComments(category) {
                const items = document.querySelectorAll('.comment-item');
                items.forEach(item => {
                    if (category === 'all' || item.dataset.category === category) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });

                // Update active filter button
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                document.querySelector(`[onclick="filterComments('${category}')"]`).classList.add('active');
            }

            // Animation pour les cartes métriques
function animateCounters() {
    const counters = document.querySelectorAll('.counter');
    counters.forEach(counter => {
        const targetText = counter.textContent.trim();
        
        // Si le texte contient des espaces (montant formaté), on ne l'anime pas
        if (targetText.includes(' ')) {
            return; // Garder le formatage original
        }
        
        // Animation seulement pour les nombres simples sans formatage
        const target = parseInt(targetText);
        if (isNaN(target)) return;
        
        let current = 0;
        const increment = target / 100;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                counter.textContent = target;
                clearInterval(timer);
            } else {
                counter.textContent = Math.floor(current);
            }
        }, 20);
    });
}

            // Initialiser l'animation au chargement
            setTimeout(animateCounters, 500);
        </script>
    </x-app-layout>


    <!-- JavaScript functions -->
    <script>
        function contactClient(clientId) {
            // Logic to contact client
            alert('Contacter le client ID: ' + clientId);
        }

        function callProspect(phone) {
            // Logic to call prospect
            if (confirm('Appeler le ' + phone + ' ?')) {
                window.open('tel:' + phone);
            }
        }

        // Show appropriate section based on user role
        document.addEventListener('DOMContentLoaded', function() {
            @if(auth()->user()->isAdmin())
                document.querySelector('[data-section="admin"]')?.style.setProperty('display', 'block', 'important');
            @elseif(auth()->user()->isManager())
                document.querySelector('[data-section="manager"]')?.style.setProperty('display', 'block', 'important');
            @else
                document.querySelector('[data-section="commercial"]')?.style.setProperty('display', 'block', 'important');
            @endif
        });
    </script>

@endif

<!-- Include Bootstrap CSS and JS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">