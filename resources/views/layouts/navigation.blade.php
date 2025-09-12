<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom py-3">
    <div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand fw-bold fs-4" href="{{ route('dashboard') }}" style="color: #6f4e37;">
            YAYE DIA BTP
        </a>

        <!-- Burger -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                @php
                    $user = auth()->user();
                    $nav = [];
                    
                    // Navigation de base pour tous les utilisateurs
                    $nav[] = ['route' => 'dashboard', 'icon' => 'home', 'label' => 'Tableau de bord', 'priority' => 1];
                    
                    // Navigation spécifique selon le rôle
                    if ($user->isAgent()) {
                        $nav = array_merge($nav, [
                            ['route' => 'prospects.index', 'icon' => 'user-plus', 'label' => 'Prospects', 'priority' => 2],
                            ['route' => 'sites.index', 'icon' => 'map-marker-alt', 'label' => 'Sites', 'priority' => 3],
                            ['route' => 'payment-schedules.index', 'icon' => 'calendar-alt', 'label' => 'Échéancier', 'priority' => 4],
                            ['route' => 'payments.my', 'icon' => 'credit-card', 'label' => 'Mes Paiements', 'priority' => 5]
                        ]);
                    }
                    
                    elseif ($user->isCaissier()) {
                        $nav = array_merge($nav, [
                            ['route' => 'payments.validation.index', 'icon' => 'check-circle', 'label' => 'Validation', 'priority' => 2],
                            ['route' => 'receipts.index', 'icon' => 'receipt', 'label' => 'Bordereaux', 'priority' => 3],
                            ['route' => 'cash.index', 'icon' => 'cash-register', 'label' => 'Caisse', 'priority' => 4]
                        ]);
                    }
                    
                    elseif ($user->isManager() || $user->isAdmin()) {
                        $baseManagerNav = [
                            ['route' => 'prospects.index', 'icon' => 'user-plus', 'label' => 'Prospects', 'priority' => 2],
                            ['route' => 'sites.index', 'icon' => 'map-marker-alt', 'label' => 'Sites', 'priority' => 3],
                            ['route' => 'payment-schedules.index', 'icon' => 'calendar-alt', 'label' => 'Échéancier', 'priority' => 4],
                            ['route' => 'payments.validation.index', 'icon' => 'check-circle', 'label' => 'Validation', 'priority' => 5],
                            ['route' => 'receipts.index', 'icon' => 'receipt', 'label' => 'Bordereaux', 'priority' => 6],
                            ['route' => 'cash.index', 'icon' => 'cash-register', 'label' => 'Caisse', 'priority' => 7],
                            ['route' => 'commercial.performance', 'icon' => 'chart-line', 'label' => 'Performance', 'priority' => 8]
                        ];
                        
                        $nav = array_merge($nav, $baseManagerNav);
                        
                        // Navigation supplémentaire pour Admin uniquement
                        if ($user->isAdmin()) {
                            $nav[] = ['route' => 'admin.users.index', 'icon' => 'users-cog', 'label' => 'Utilisateurs', 'priority' => 9];
                        }
                    }
                    
                    // Gestion spéciale pour le responsable commercial (si ce n'est ni manager ni admin)
                    elseif ($user->role === 'responsable_commercial') {
                        $nav = array_merge($nav, [
                            ['route' => 'prospects.index', 'icon' => 'user-plus', 'label' => 'Prospects', 'priority' => 2],
                            ['route' => 'sites.index', 'icon' => 'map-marker-alt', 'label' => 'Sites', 'priority' => 3],
                            ['route' => 'cash.index', 'icon' => 'cash-register', 'label' => 'Caisse', 'priority' => 4]
                        ]);
                    }
                    
                    // Trier par priorité
                    usort($nav, function($a, $b) {
                        return $a['priority'] <=> $b['priority'];
                    });
                    
                    // Supprimer les doublons basés sur la route (au cas où)
                    $uniqueNav = [];
                    $routes = [];
                    foreach ($nav as $item) {
                        if (!in_array($item['route'], $routes)) {
                            $uniqueNav[] = $item;
                            $routes[] = $item['route'];
                        }
                    }
                    $nav = $uniqueNav;
                @endphp

                @foreach ($nav as $item)
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-1 {{ request()->routeIs($item['route']) || (request()->route() && str_contains(request()->route()->getName(), explode('.', $item['route'])[0])) ? 'active fw-bold text-brown' : 'text-secondary' }}"
                           href="{{ route($item['route']) }}" title="{{ $item['label'] }}">
                            <i class="fas fa-{{ $item['icon'] }}" style="color: #6f4e37;"></i> 
                            <span class="nav-text">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <!-- Notifications -->
            @php $notifications = auth()->user()->unreadNotifications; @endphp
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative" href="#" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="color: #6f4e37;" title="Notifications">
                        <i class="fas fa-bell fa-lg"></i>
                        @if($notifications->count() > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ $notifications->count() > 99 ? '99+' : $notifications->count() }}
                            </span>
                        @endif
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow p-2" style="min-width: 300px; max-height: 400px; overflow-y: auto;" aria-labelledby="notificationsDropdown">
                        @forelse($notifications->take(10) as $notification)
                            <li>
                                <a class="dropdown-item small text-wrap py-2" href="{{ route('notifications.read', $notification->id) }}">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            {{ $notification->data['message'] ?? 'Nouvelle notification' }}
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-phone me-1"></i>{{ $notification->data['phone'] ?? 'N/A' }}
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            </li>
                            @if(!$loop->last)
                                <li><hr class="dropdown-divider"></li>
                            @endif
                        @empty
                            <li class="dropdown-item text-muted text-center py-3">
                                <i class="fas fa-bell-slash fa-2x mb-2"></i><br>
                                Aucune notification
                            </li>
                        @endforelse
                        
                        @if($notifications->count() > 10)
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <span class="dropdown-item text-center small text-muted">
                                    {{ $notifications->count() }} notifications au total
                                </span>
                            </li>
                        @endif
                    </ul>
                </li>

                <!-- Profil -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: #6f4e37;">
                        <div class="rounded-circle bg-brown text-white d-flex justify-content-center align-items-center" style="width: 38px; height: 38px;">
                            @if(Auth::user()->avatar)
                                <img src="{{ Storage::url(Auth::user()->avatar) }}" alt="Avatar" class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
                            @else
                                <i class="fas fa-user"></i>
                            @endif
                        </div>
                        <div class="d-none d-lg-block text-start">
                            <span class="fw-semibold">{{ Auth::user()->full_name ?? Auth::user()->first_name.' '.Auth::user()->last_name ?? Auth::user()->email }}</span><br>
                            <small class="text-muted">
                                @php
                                    $roleLabels = [
                                        'admin' => 'Administrateur',
                                        'manager' => 'Manager',
                                        'caissier' => 'Caissier',
                                        'agent' => 'Agent',
                                        'responsable_commercial' => 'Resp. Commercial'
                                    ];
                                @endphp
                                {{ $roleLabels[Auth::user()->role] ?? ucfirst(Auth::user()->role) ?? 'Utilisateur' }}
                            </small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">
                        <li>
                            <div class="dropdown-item-text d-lg-none">
                                <strong>{{ Auth::user()->full_name ?? Auth::user()->email }}</strong><br>
                                <small class="text-muted">{{ $roleLabels[Auth::user()->role] ?? ucfirst(Auth::user()->role) ?? 'Utilisateur' }}</small>
                            </div>
                        </li>
                        <li class="d-lg-none"><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-user me-2"></i> Mon Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
    .text-brown { color: #6f4e37 !important; }
    .bg-brown { background-color: #6f4e37 !important; }
    
    .navbar-brand {
        font-weight: bold;
        font-size: 1.5rem;
        transition: transform 0.3s ease;
    }
    
    .navbar-brand:hover {
        transform: scale(1.05);
    }
    
    .nav-link {
        transition: all 0.3s ease;
        white-space: nowrap;
        border-radius: 5px;
        margin: 0 2px;
    }
    
    .nav-link:hover {
        color: #6f4e37 !important;
        transform: translateY(-1px);
        background-color: rgba(111, 78, 55, 0.05);
    }
    
    .nav-link.active {
        font-weight: 600;
        color: #6f4e37 !important;
        background-color: rgba(111, 78, 55, 0.1);
        border-bottom: 2px solid #6f4e37;
    }
    
    .dropdown-menu {
        border: none;
        box-shadow: 0 4px 15px rgba(111, 78, 55, 0.15);
        border-radius: 8px;
    }
    
    .dropdown-item:hover {
        background-color: rgba(111, 78, 55, 0.1);
        color: #6f4e37;
    }
    
    .badge {
        font-size: 0.7rem;
    }
    
    /* Responsive Design */
    @media (max-width: 1200px) {
        .nav-item .nav-link {
            font-size: 0.9rem;
            padding: 0.5rem 0.7rem;
        }
        
        /* Masquer le texte sur écrans moyens, garder seulement les icônes */
        .nav-text {
            display: none;
        }
        
        .nav-link i {
            margin-right: 0 !important;
        }
        
        .nav-link {
            min-width: 40px;
            justify-content: center;
        }
    }
    
    @media (max-width: 992px) {
        /* Réafficher le texte sur mobile dans le menu collapsed */
        .nav-text {
            display: inline;
        }
        
        .navbar-nav {
            gap: 0.5rem;
            padding-top: 1rem;
        }
        
        .nav-link {
            padding: 0.7rem 1rem;
            border-radius: 8px;
            justify-content: flex-start;
        }
        
        .nav-link.active {
            background-color: rgba(111, 78, 55, 0.15);
            border-bottom: none;
            border-left: 4px solid #6f4e37;
        }
        
        .nav-link i {
            margin-right: 0.5rem !important;
        }
    }
    
    @media (max-width: 768px) {
        .d-lg-block {
            display: none !important;
        }
        
        .navbar-brand {
            font-size: 1.2rem;
        }
        
        .dropdown-menu {
            min-width: 250px !important;
        }
    }
    
    /* Animation pour les notifications */
    .fa-bell {
        animation: none;
    }
    
    .badge {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    /* Amélioration du scroll dans les notifications */
    .dropdown-menu::-webkit-scrollbar {
        width: 4px;
    }
    
    .dropdown-menu::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .dropdown-menu::-webkit-scrollbar-thumb {
        background: #6f4e37;
        border-radius: 10px;
    }
    
    .dropdown-menu::-webkit-scrollbar-thumb:hover {
        background: #5a3e2b;
    }
</style>