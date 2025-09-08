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
    $nav = [
        ['route' => 'dashboard', 'icon' => 'home', 'label' => 'Tableau de bord'],
        ['route' => 'prospects.index', 'icon' => 'user-plus', 'label' => 'Prospects'],
        ['route' => 'sites.index', 'icon' => 'map-marker-alt', 'label' => 'Sites'],
        ['route' => 'payment-schedules.index', 'icon' => 'calendar-alt', 'label' => 'Échéancier'],
    ];

    if (auth()->user()->isAgent()) {
        $nav[] = ['route' => 'payments.my', 'icon' => 'credit-card', 'label' => 'Mes Paiements'];
    }

    if (auth()->user()->isManager() || auth()->user()->isAdmin()) {
        $nav[] = ['route' => 'payments.validation.index', 'icon' => 'check-circle', 'label' => 'Validation Paiements'];
        $nav[] = ['route' => 'receipts.index', 'icon' => 'receipt', 'label' => 'Bordereaux'];
        $nav[] = ['route' => 'cash.index', 'icon' => 'cash-register', 'label' => 'Gestion Caisse'];
        $nav[] = ['route' => 'commercial.performance', 'icon' => 'chart-line', 'label' => 'Performance Commerciaux'];
    }

    // Caissiers et responsables commerciaux ont accès à la gestion de caisse
    if (auth()->user()->isCaissier() || auth()->user()->role === 'responsable_commercial') {
        $nav[] = ['route' => 'cash.index', 'icon' => 'cash-register', 'label' => 'Gestion Caisse'];
    }

    if (auth()->user()->isCaissier()) {
    // Le caissier voit la validation des paiements et les bordereaux
    $nav = [
        ['route' => 'payments.validation.index', 'icon' => 'check-circle', 'label' => 'Validation Paiements'],
        ['route' => 'receipts.index', 'icon' => 'receipt', 'label' => 'Bordereaux'],
        ['route' => 'cash.index', 'icon' => 'cash-register', 'label' => 'Gestion Caisse']
    ];
}

    if (auth()->user()->isAdmin()) {
        $nav[] = ['route' => 'admin.users.index', 'icon' => 'users-cog', 'label' => 'Utilisateurs'];
    }


@endphp


                @foreach ($nav as $item)
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-1 {{ request()->routeIs($item['route']) || str_contains(request()->route()->getName(), explode('.', $item['route'])[0]) ? 'active fw-bold text-brown' : 'text-secondary' }}"
                           href="{{ route($item['route']) }}">
                            <i class="fas fa-{{ $item['icon'] }}" style="color: #6f4e37;"></i> {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <!-- Notifications -->
            @php $notifications = auth()->user()->unreadNotifications; @endphp
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative" href="#" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="color: #6f4e37;">
                        <i class="fas fa-bell fa-lg"></i>
                        @if($notifications->count() > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ $notifications->count() }}
                            </span>
                        @endif
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow p-2" style="min-width: 300px;" aria-labelledby="notificationsDropdown">
                        @forelse($notifications as $notification)
                            <li>
                                <a class="dropdown-item small text-wrap" href="{{ route('notifications.read', $notification->id) }}">
                                    {{ $notification->data['message'] }}<br>
                                    <small class="text-muted">📞 {{ $notification->data['phone'] ?? 'N/A' }}</small>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                        @empty
                            <li class="dropdown-item text-muted">Aucune notification</li>
                        @endforelse
                    </ul>
                </li>

                <!-- Profil -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: #6f4e37;">
                        <div class="rounded-circle bg-brown text-white d-flex justify-content-center align-items-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="d-none d-lg-block text-start">
                            <span class="fw-semibold">{{ Auth::user()->full_name ?? Auth::user()->email }}</span><br>
                            <small class="text-muted">{{ ucfirst(Auth::user()->role ?? 'Utilisateur') }}</small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="fas fa-user me-2"></i> Profil</a></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</button>
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
    .nav-link.active {
        font-weight: 600;
        color: #6f4e37 !important;
        border-bottom: 2px solid #6f4e37;
    }
</style>
