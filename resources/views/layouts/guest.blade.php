
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-3">
    <div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand fw-bold text-primary fs-4" href="{{ route('dashboard') }}">
            <i class="fas fa-tools me-2"></i> YAYE DIA BTP
        </a>

        <!-- Bouton hamburger -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Contenu du menu -->
        <div class="collapse navbar-collapse" id="mainNavbar">
            <!-- Menu gauche -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-bold text-primary' : '' }}" href="{{ route('dashboard') }}">
                        <i class="fas fa-home me-1"></i> Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('prospects.*') ? 'active fw-bold text-primary' : '' }}" href="{{ route('prospects.index') }}">
                        <i class="fas fa-user-plus me-1"></i> Prospects
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('sites.*') ? 'active fw-bold text-primary' : '' }}" href="{{ route('sites.index') }}">
                        <i class="fas fa-map-marker-alt me-1"></i> Sites
                    </a>
                </li>
                @if(auth()->user()->isAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users.index') ? 'active fw-bold text-primary' : '' }}" href="{{ route('admin.users.index') }}">
                            <i class="fas fa-users-cog me-1"></i> Utilisateurs
                        </a>
                    </li>
                @endif
            </ul>

            <!-- Menu droite -->
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- Notifications -->
                @php $notifications = auth()->user()->unreadNotifications; @endphp
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative dropdown-toggle" href="#" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fa-lg"></i>
                        @if($notifications->count() > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                {{ $notifications->count() }}
                            </span>
                        @endif
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width: 300px;" aria-labelledby="notificationsDropdown">
                        @forelse($notifications as $notification)
                            <li>
                                <a class="dropdown-item small" href="{{ route('prospects.show', $notification->data['prospect_id']) }}">
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

                <!-- Profil utilisateur avec initiales dynamiques -->
                <li class="nav-item dropdown">
                    @php
                        $fullName = Auth::user()->full_name ?? Auth::user()->email;
                        $initials = collect(explode(' ', $fullName))->map(fn($word) => strtoupper(substr($word, 0, 1)))->implode('');
                    @endphp
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle bg-primary text-white fw-bold d-flex justify-content-center align-items-center shadow-sm" style="width: 38px; height: 38px;">
                            {{ $initials }}
                        </div>
                        <div class="d-flex flex-column align-items-start">
                            <span class="fw-semibold">{{ $fullName }}</span>
                            <small class="text-muted">{{ ucfirst(Auth::user()->role ?? 'Utilisateur') }}</small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
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
