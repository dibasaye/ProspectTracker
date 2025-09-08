<!-- Sidebar -->
<aside class="bg-indigo-700 text-white w-64 min-h-screen flex-shrink-0">
    <div class="p-4">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold">ProspectTracker</h1>
                <p class="text-xs text-indigo-200">Gestion Commerciale</p>
            </div>
        </div>
    </div>
    
    <nav class="mt-6">
        <div class="px-4 mb-6">
            <p class="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Menu Principal</p>
        </div>
        
        <!-- Dashboard Link -->
        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-white bg-indigo-800 border-r-4 border-white">
            <i class="ri-dashboard-line text-lg mr-3"></i>
            <span>Tableau de bord</span>
        </a>
        
        @can('view clients')
        <a href="{{ route('clients.index') }}" class="flex items-center px-4 py-3 text-indigo-200 hover:bg-indigo-600">
            <i class="ri-contacts-line text-lg mr-3"></i>
            <span>Clients</span>
        </a>
        @endcan
        
        @can('view prospects')
        <a href="{{ route('prospects.index') }}" class="flex items-center px-4 py-3 text-indigo-200 hover:bg-indigo-600">
            <i class="ri-user-search-line text-lg mr-3"></i>
            <span>Prospects</span>
            @if(auth()->user()->hasRole('responsable commercial') && $unassignedProspects = \App\Models\Prospect::doesntHave('assignedTo')->where('status', '!=', 'converti')->count())
            <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                {{ $unassignedProspects }}
            </span>
            @endif
        </a>
        @endcan
        
        @can('view contracts')
        <a href="{{ route('contracts.index') }}" class="flex items-center px-4 py-3 text-indigo-200 hover:bg-indigo-600">
            <i class="ri-file-paper-2-line text-lg mr-3"></i>
            <span>Contrats</span>
        </a>
        @endcan
        
        @can('view payments')
        <a href="{{ route('payments.index') }}" class="flex items-center px-4 py-3 text-indigo-200 hover:bg-indigo-600">
            <i class="ri-money-euro-circle-line text-lg mr-3"></i>
            <span>Paiements</span>
        </a>
        @endcan
        
        @can('view sites')
        <a href="{{ route('sites.index') }}" class="flex items-center px-4 py-3 text-indigo-200 hover:bg-indigo-600">
            <i class="ri-map-pin-line text-lg mr-3"></i>
            <span>Sites</span>
        </a>
        @endcan
        
        @can('view reports')
        <div class="px-4 mt-6 mb-2">
            <p class="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Rapports</p>
        </div>
        
        <a href="{{ route('reports.sales') }}" class="flex items-center px-4 py-2 text-indigo-200 hover:bg-indigo-600 text-sm">
            <i class="ri-bar-chart-line text-lg mr-3"></i>
            <span>Ventes</span>
        </a>
        
        <a href="{{ route('reports.payments') }}" class="flex items-center px-4 py-2 text-indigo-200 hover:bg-indigo-600 text-sm">
            <i class="ri-line-chart-line text-lg mr-3"></i>
            <span>Encaissements</span>
        </a>
        
        <a href="{{ route('reports.commercials') }}" class="flex items-center px-4 py-2 text-indigo-200 hover:bg-indigo-600 text-sm">
            <i class="ri-team-line text-lg mr-3"></i>
            <span>Performance Commerciale</span>
        </a>
        @endcan
        
        @can('view settings')
        <div class="px-4 mt-6 mb-2">
            <p class="text-xs font-semibold text-indigo-300 uppercase tracking-wider">Administration</p>
        </div>
        
        <a href="{{ route('users.index') }}" class="flex items-center px-4 py-2 text-indigo-200 hover:bg-indigo-600 text-sm">
            <i class="ri-user-settings-line text-lg mr-3"></i>
            <span>Utilisateurs</span>
        </a>
        
        <a href="{{ route('settings') }}" class="flex items-center px-4 py-2 text-indigo-200 hover:bg-indigo-600 text-sm">
            <i class="ri-settings-3-line text-lg mr-3"></i>
            <span>Paramètres</span>
        </a>
        @endcan
    </nav>
    
    <!-- User Profile -->
    <div class="absolute bottom-0 w-64 p-4 bg-indigo-800">
        <div class="flex items-center">
            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-600 flex items-center justify-center">
                <span class="text-indigo-100 font-medium text-lg">{{ substr(auth()->user()->name, 0, 1) }}</span>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                <p class="text-xs font-medium text-indigo-200">{{ auth()->user()->getRoleNames()->first() }}</p>
            </div>
            <div class="ml-auto">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-indigo-300 hover:text-white">
                        <i class="ri-logout-box-r-line"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
