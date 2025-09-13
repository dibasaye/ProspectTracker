<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use App\Models\Site;
use App\Models\Lot;
use App\Models\Payment;
use App\Models\Contract;
use App\Models\User;
use App\Models\PaymentSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard based on user role
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Si c'est un caissier, rediriger ou afficher le message d'accès restreint
        if ($user->isCaissier()) {
            return view('dashboard'); // Votre vue existante gère déjà les caissiers
        }
        
        $filters = $this->getFilters($request);
        
        // Get dashboard data based on user role
        $data = $this->getDashboardData($user, $filters);
        
        // Add common data for all roles
        $data['filters'] = $filters;
        $data['user'] = $user;
        
        // Charger votre vue dashboard existante
        return view('dashboard', $data);
    }
    
    /**
     * Get dashboard data based on user role
     */
    protected function getDashboardData($user, array $filters = [])
    {
        if ($user->isAdmin()) {
            return $this->getAdminDashboardData($filters);
        }
        
        if ($user->isManager()) {
            return $this->getSalesManagerDashboardData($filters);
        }
        
        return $this->getCommercialDashboardData($user, $filters);
    }
    
    /**
     * Get admin dashboard data with comprehensive global overview
     */
    protected function getAdminDashboardData(array $filters)
    {
        // Appliquer les filtres aux prospects
        $prospectsQuery = Prospect::query();
        if (!empty($filters['site_id'])) {
            $prospectsQuery->where('interested_site_id', $filters['site_id']);
        }
        if (!empty($filters['commercial_id'])) {
            $prospectsQuery->where('assigned_to_id', $filters['commercial_id']);
        }
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $prospectsQuery->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }
        
        // Appliquer les filtres aux contrats
        $contractsQuery = $this->applyFilters(Contract::query(), $filters);
        
        $totalClients = (clone $prospectsQuery)->where('status', 'converti')->count();
            
        $totalSales = (clone $contractsQuery)->sum('total_amount');
        $totalPaid = $this->getTotalPaid($filters);
        $totalPending = max(0, $totalSales - $totalPaid);
        
        // Données globales par site
        $salesBySite = $this->getSalesBySite($filters);
        
        // Données par commercial
        $salesByCommercial = $this->getSalesByCommercial($filters);
        
        // Données par mois
        $monthlySalesData = $this->getMonthlySalesData($filters);
        
        // Compteurs de terrains vendus - obtenir le total global ET le total filtré
        $soldLots = $this->getSoldLotsCount($filters);
        $totalSoldLotsGlobal = $this->getTotalSoldLotsCount($filters);
        
        // Tous les encaissements et décaissements récents
        $allCashTransactions = \App\Models\CashTransaction::with(['user', 'payment'])
            ->latest()
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function($q) use ($filters) {
                $q->whereBetween('transaction_date', [$filters['start_date'], $filters['end_date']]);
            })
            ->take(20)
            ->get();
        
        // Statistiques complètes par site
        $comprehensiveSiteStats = $this->getComprehensiveSiteStats($filters);
        
        return [
            'stats' => [
                'total_prospects' => (clone $prospectsQuery)->count(),
                'active_prospects' => (clone $prospectsQuery)->whereIn('status', ['nouveau', 'en_relance', 'interesse'])->count(),
                'total_sites' => !empty($filters['site_id']) ? 1 : Site::count(),
                'sold_lots' => $totalSoldLotsGlobal,
                'total_sales' => $totalSales,
                'total_payments' => $totalPaid,
                'total_to_recover' => $totalPending,
                'pending_payments' => Payment::where('validation_status', 'pending')->count(),
                'total_contracts' => (clone $contractsQuery)->count(),
                'signed_contracts' => (clone $contractsQuery)->where('status', 'signe')->count(),
            ],
            'salesBySite' => $salesBySite,
            'salesByCommercial' => $salesByCommercial,
            'monthlySalesData' => $monthlySalesData,
            'comprehensiveSiteStats' => $comprehensiveSiteStats,
            'allCashTransactions' => $allCashTransactions,
            'recentProspects' => (clone $prospectsQuery)->with('interestedSite')->latest()->take(5)->get(),
            'recentPayments' => $this->getFilteredRecentPayments($filters),
            'pendingPayments' => Payment::where('validation_status', 'pending')->with(['client', 'site'])->get(),
        ];
    }
    
    /**
     * Get sales manager dashboard data with enhanced commercial oversight
     */
    protected function getSalesManagerDashboardData(array $filters)
    {
        $commercials = User::where('role', 'commercial')
            ->where('is_active', true)
            ->withCount(['generatedContracts', 'assignedProspects'])
            ->get()
            ->map(function($commercial) use ($filters) {
                $contracts = $this->getCommercialContracts($commercial->id, $filters);
                $totalSales = $contracts->sum('total_amount');
                $totalPaid = $contracts->sum(function($contract) {
                    return $contract->payments->sum('amount');
                });
                
                // Échéances à venir pour ce commercial
                $upcomingPayments = $this->getUpcomingPayments($filters, 10, $commercial->id);
                
                return [
                    'id' => $commercial->id,
                    'name' => $commercial->full_name,
                    'total_clients' => $commercial->assigned_prospects_count,
                    'total_sales' => $totalSales,
                    'total_paid' => $totalPaid,
                    'pending_amount' => max(0, $totalSales - $totalPaid),
                    'contracts_count' => $commercial->generated_contracts_count,
                    'upcoming_payments' => $upcomingPayments,
                    'pipeline_status' => $this->getCommercialPipelineStatus($commercial->id),
                ];
            });
            
        // Prospects à dispatcher (non assignés)
        $prospectsToDispatch = Prospect::whereNull('assigned_to_id')
            ->whereIn('status', ['nouveau', 'en_relance', 'interesse'])
            ->with('interestedSite')
            ->latest()
            ->get();
            
        // Statistiques complètes par site pour manager
        $comprehensiveSiteStats = $this->getComprehensiveSiteStats($filters);
        
        return [
            'stats' => [
                'total_prospects' => $this->getFilteredProspectsCount($filters),
                'active_prospects' => $this->getFilteredActiveProspectsCount($filters),
                'total_sites' => !empty($filters['site_id']) ? 1 : Site::count(),
                'sold_lots' => $this->getTotalSoldLotsCount($filters),
                'total_payments' => $this->getTotalPaid($filters),
                'pending_payments' => Payment::where('validation_status', 'pending')->count(),
                'total_contracts' => $this->getFilteredContractsCount($filters),
                'signed_contracts' => $this->getFilteredSignedContractsCount($filters),
                'commercials' => $commercials,
                'prospects_to_dispatch' => $prospectsToDispatch->count(),
            ],
            'commercials' => $commercials,
            'prospectsToDispatch' => $prospectsToDispatch,
            'comprehensiveSiteStats' => $comprehensiveSiteStats,
            'recentProspects' => $this->getFilteredRecentProspects($filters),
            'recentPayments' => $this->getFilteredRecentPayments($filters),
        ];
    }
    
    /**
     * Get commercial dashboard data with enhanced client management features
     */
    protected function getCommercialDashboardData($user, array $filters)
    {
        $filters['commercial_id'] = $user->id;
        $contracts = $this->getCommercialContracts($user->id, $filters);
        
        $totalSales = $contracts->sum('total_amount');
        $totalPaid = $contracts->sum(function($contract) {
            return $contract->payments->sum('amount');
        });
        
        // Nouvelle fonctionnalité: Liste détaillée des clients avec statuts colorés
        $clientsData = $this->getCommercialClientsData($user, $filters);
        $monthlyData = $this->getCommercialMonthlyData($user, $filters);
        
        // Prospects avec commentaires filtrés
        $prospectsWithComments = $this->getProspectsWithFilterableComments($user);
        
        return [
            'stats' => [
                'my_prospects' => $user->assignedProspects()->count(),
                'active_prospects' => $user->assignedProspects()->whereIn('status', ['nouveau', 'en_relance', 'interesse'])->count(),
                'converted_prospects' => $user->assignedProspects()->where('status', 'converti')->count(),
                'my_contracts' => $contracts->count(),
                'signed_contracts' => $contracts->where('status', 'signe')->count(),
                'my_payments' => $totalPaid,
                'total_sales' => $totalSales,
                'total_to_recover' => max(0, $totalSales - $totalPaid),
                'validated_payments' => Payment::whereHas('contract', function($q) use ($user) {
                    $q->where('generated_by', $user->id);
                })->where('validation_status', 'completed')->sum('amount'),
                'pending_payments' => Payment::whereHas('contract', function($q) use ($user) {
                    $q->where('generated_by', $user->id);
                })->where('validation_status', 'pending')->count(),
            ],
            'clientsData' => $clientsData,
            'monthlyData' => $monthlyData,
            'prospectsWithComments' => $prospectsWithComments,
            'recentProspects' => $user->assignedProspects()->with('interestedSite')->latest()->take(5)->get(),
            'recentPayments' => Payment::whereHas('contract', function($q) use ($user) {
                $q->where('generated_by', $user->id);
            })->with(['client', 'site'])->latest()->take(5)->get(),
            'pendingPayments' => Payment::whereHas('contract', function($q) use ($user) {
                $q->where('generated_by', $user->id);
            })->where('validation_status', 'pending')->with(['client', 'site'])->get(),
        ];
    }
    
    /**
     * Get commercial contracts with filters applied
     */
    protected function getCommercialContracts($commercialId, array $filters = [])
    {
        $query = Contract::where('generated_by', $commercialId)
             ->with(['payments', 'client', 'lot']);
            
        return $this->applyFilters($query, $filters)->get();
    }
    
    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters = [])
    {
        if (!empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }
        
        if (!empty($filters['commercial_id'])) {
            $query->where('generated_by', $filters['commercial_id']);
        }
        
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [
                $filters['start_date'],
                $filters['end_date']
            ]);
        }
        
        return $query;
    }
    
    /**
     * Filter by date range
     */
    protected function filterByDateRange($model, array $filters)
    {
        if (empty($filters['start_date']) || empty($filters['end_date'])) {
            return true;
        }
        
        return $model->created_at >= $filters['start_date'] && 
               $model->created_at <= $filters['end_date'];
    }
    
    /**
     * Get total paid amount with filters
     */
    protected function getTotalPaid(array $filters = [])
    {
        $query = Payment::query();
        
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('payment_date', [
                $filters['start_date'],
                $filters['end_date']
            ]);
        }
        
        if (!empty($filters['site_id'])) {
            $query->whereHas('contract', function($q) use ($filters) {
                $q->where('site_id', $filters['site_id']);
            });
        }
        
        if (!empty($filters['commercial_id'])) {
            $query->whereHas('contract', function($q) use ($filters) {
                $q->where('generated_by', $filters['commercial_id']);
            });
        }
        
        return $query->sum('amount');
    }
    
    /**
     * Get count of sold lots with filters
     */
    protected function getSoldLotsCount(array $filters = [])
    {
        $query = Lot::where('status', 'vendu');
        
        if (!empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }
        
        if (!empty($filters['commercial_id'])) {
            $query->whereHas('contract', function($q) use ($filters) {
                $q->where('generated_by', $filters['commercial_id']);
            });
        }
        
        // Correction: utiliser created_at au lieu de signature_date qui pourrait ne pas exister
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereHas('contract', function($q) use ($filters) {
                $q->whereBetween('created_at', [
                    $filters['start_date'],
                    $filters['end_date']
                ]);
            });
        }
        
        return $query->count();
    }
    
    /**
     * Get total count of sold lots (global or by site only)
     */
    protected function getTotalSoldLotsCount(array $filters = [])
    {
        $query = Lot::where('status', 'vendu');
        
        // Appliquer seulement le filtre de site si spécifié
        if (!empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }
        
        // Ne pas appliquer les filtres de date ou commercial pour le total global
        return $query->count();
    }
    
    /**
     * Get monthly sales data
     */
    protected function getMonthlySalesData(array $filters = [])
    {
        $query = Payment::query()
            ->select(
                DB::raw('YEAR(payment_date) as year'),
                DB::raw('MONTH(payment_date) as month'),
                DB::raw('SUM(amount) as total_paid')
            )
            ->where('payment_date', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month');
            
        if (!empty($filters['site_id'])) {
            $query->whereHas('contract', function($q) use ($filters) {
                $q->where('site_id', $filters['site_id']);
            });
        }
        
        if (!empty($filters['commercial_id'])) {
            $query->whereHas('contract', function($q) use ($filters) {
                $q->where('generated_by', $filters['commercial_id']);
            });
        }
        
        return $query->get()
            ->map(function($item) {
                return [
                    'month' => "{$item->year}-{$item->month}",
                    'total_paid' => (float) $item->total_paid,
                ];
            });
    }
    
    /**
     * Get sales by site
     */
    protected function getSalesBySite(array $filters = [])
    {
        $query = Contract::select(
                'sites.name as site_name',
                DB::raw('COUNT(contracts.id) as contracts_count'),
                DB::raw('SUM(contracts.total_amount) as total_sales'),
                DB::raw('SUM((SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.contract_id = contracts.id)) as total_paid')
            )
            ->join('sites', 'contracts.site_id', '=', 'sites.id')
            ->groupBy('sites.id', 'sites.name');
            
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('contracts.created_at', [
                $filters['start_date'],
                $filters['end_date']
            ]);
        }
        
        return $query->get();
    }
    
    /**
     * Get sales by commercial
     */
    protected function getSalesByCommercial(array $filters = [])
    {
        $query = User::select(
                'users.id',
                'users.first_name',
                'users.last_name',
                DB::raw('COUNT(contracts.id) as contracts_count'),
                DB::raw('SUM(contracts.total_amount) as total_sales'),
                DB::raw('SUM((SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.contract_id = contracts.id)) as total_paid')
            )
            ->leftJoin('contracts', 'users.id', '=', 'contracts.generated_by')
            ->where('users.role', 'commercial')
            ->groupBy('users.id', 'users.first_name', 'users.last_name');
            
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('contracts.created_at', [
                $filters['start_date'],
                $filters['end_date']
            ]);
        }
        
        return $query->get();
    }
    
    /**
     * Get filtered recent payments
     */
    protected function getFilteredRecentPayments(array $filters = [])
    {
        $query = Payment::with(['client', 'site']);
        
        if (!empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }
        
        if (!empty($filters['commercial_id'])) {
            $query->whereHas('client', function($q) use ($filters) {
                $q->where('assigned_to_id', $filters['commercial_id']);
            });
        }
        
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('payment_date', [$filters['start_date'], $filters['end_date']]);
        }
        
        return $query->latest()->take(5)->get();
    }
    
    /**
     * Get upcoming payments
     */
    protected function getUpcomingPayments(array $filters = [], $limit = 10, $commercialId = null)
    {
        $query = PaymentSchedule::with(['contract.client', 'contract.generatedBy'])
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(30))
            ->where('validation_status', 'pending')
            ->where('is_paid', false)
            ->orderBy('due_date')
            ->limit($limit);
            
        if (!empty($filters['site_id'])) {
            $query->whereHas('contract', function($q) use ($filters) {
                $q->where('site_id', $filters['site_id']);
            });
        }
        
        if ($commercialId) {
            $query->whereHas('contract', function($q) use ($commercialId) {
                $q->where('generated_by', $commercialId);
            });
        } elseif (!empty($filters['commercial_id'])) {
            $query->whereHas('contract', function($q) use ($filters) {
                $q->where('generated_by', $filters['commercial_id']);
            });
        }
        
        return $query->get();
    }
    
    /**
     * Get recent activities
     */
    protected function getRecentActivities($limit = 10)
    {
        if (class_exists(\App\Models\ActivityLog::class)) {
            return \App\Models\ActivityLog::with(['user'])
                ->latest()
                ->limit($limit)
                ->get();
        }
        
        return collect();
    }
    
    /**
     * Get client status
     */
    protected function getClientStatus($totalAmount, $totalPaid)
    {
        if ($totalPaid >= $totalAmount) return 'À jour';
        if ($totalPaid > 0) return 'Paiement partiel';
        return 'À relancer';
    }
    
    /**
     * Get filters from request
     */
    protected function getFilters(Request $request)
    {
        $filters = [];
        
        // Récupérer les filtres de la requête
        if ($request->has('site_id') && !empty($request->get('site_id'))) {
            $filters['site_id'] = $request->get('site_id');
        }
        
        if ($request->has('commercial_id') && !empty($request->get('commercial_id'))) {
            $filters['commercial_id'] = $request->get('commercial_id');
        }
        
        if ($request->has('period') && !empty($request->get('period'))) {
            $filters['period'] = $request->get('period');
        }
        
        if ($request->has('start_date') && !empty($request->get('start_date'))) {
            $filters['start_date'] = $request->get('start_date');
        }
        
        if ($request->has('end_date') && !empty($request->get('end_date'))) {
            $filters['end_date'] = $request->get('end_date');
        }
        
        // Gérer les périodes prédéfinies
        if (!empty($filters['period']) && $filters['period'] !== 'custom') {
            $dateRange = $this->getDateRangeFromPeriod($filters['period']);
            $filters['start_date'] = $dateRange['start'];
            $filters['end_date'] = $dateRange['end'];
        }
        
        // Définir une plage par défaut si aucune date n'est fournie
        if (empty($filters['start_date']) && empty($filters['end_date']) && empty($filters['period'])) {
            $filters['period'] = 'this_month';
            $dateRange = $this->getDateRangeFromPeriod('this_month');
            $filters['start_date'] = $dateRange['start'];
            $filters['end_date'] = $dateRange['end'];
        }
        
        return $filters;
    }
    
    /**
     * Obtenir la plage de dates selon la période sélectionnée
     */
    protected function getDateRangeFromPeriod($period)
    {
        $now = now();
        
        return match($period) {
            'today' => [
                'start' => $now->copy()->startOfDay()->toDateString(),
                'end' => $now->copy()->endOfDay()->toDateString()
            ],
            'this_week' => [
                'start' => $now->copy()->startOfWeek()->toDateString(),
                'end' => $now->copy()->endOfWeek()->toDateString()
            ],
            'this_month' => [
                'start' => $now->copy()->startOfMonth()->toDateString(),
                'end' => $now->copy()->endOfMonth()->toDateString()
            ],
            'last_month' => [
                'start' => $now->copy()->subMonth()->startOfMonth()->toDateString(),
                'end' => $now->copy()->subMonth()->endOfMonth()->toDateString()
            ],
            'this_year' => [
                'start' => $now->copy()->startOfYear()->toDateString(),
                'end' => $now->copy()->endOfYear()->toDateString()
            ],
            default => [
                'start' => $now->copy()->startOfMonth()->toDateString(),
                'end' => $now->copy()->endOfMonth()->toDateString()
            ]
        };
    }

    /**
     * Get detailed client data for commercial with status colors
     */
    protected function getCommercialClientsData($user, array $filters = [])
    {
        $contracts = Contract::where('generated_by', $user->id)
            ->with(['client', 'payments', 'site', 'lot'])
            ->whereHas('client')
            ->get();
            
        return $contracts->map(function($contract) {
            $totalAmount = $contract->total_amount;
            $totalPaid = $contract->payments->sum('amount');
            $toRecover = max(0, $totalAmount - $totalPaid);
            
            $status = $this->getClientPaymentStatus($totalAmount, $totalPaid);
            
            return [
                'client_name' => $contract->client ? $contract->client->full_name : 'Client non défini',
                'client_id' => $contract->client ? $contract->client->id : null,
                'contract_id' => $contract->id,
                'site_name' => $contract->site ? $contract->site->name : 'Site non défini',
                'lot_number' => $contract->lot ? $contract->lot->number : 'N/A',
                'total_amount' => $totalAmount,
                'total_paid' => $totalPaid,
                'to_recover' => $toRecover,
                'status' => $status,
                'status_color' => $this->getStatusColor($status),
                'last_payment_date' => $contract->payments->max('payment_date'),
            ];
        });
    }

    /**
     * Get monthly data for commercial
     */
    protected function getCommercialMonthlyData($user, array $filters = [])
    {
        $currentMonth = now()->format('Y-m');
        
        $monthlyPayments = Payment::whereHas('contract', function($q) use ($user) {
                $q->where('generated_by', $user->id);
            })
            ->whereRaw("DATE_FORMAT(payment_date, '%Y-%m') = ?", [$currentMonth])
            ->get();
        
        $monthlySchedules = PaymentSchedule::whereHas('contract', function($q) use ($user) {
                $q->where('generated_by', $user->id);
            })
            ->whereRaw("DATE_FORMAT(due_date, '%Y-%m') = ?", [$currentMonth])
            ->get();
        
        $amountToReceive = $monthlySchedules->where('is_paid', false)->sum('amount');
        $amountReceived = $monthlyPayments->sum('amount');
        $remainingBalance = max(0, $amountToReceive - $amountReceived);
        
        // Objectifs mensuels (peuvent être configurés ou calculés)
        $salesTarget = $this->getMonthlySalesTarget($user);
        $recoveryTarget = $this->getMonthlyRecoveryTarget($user);
        
        return [
            'month' => $currentMonth,
            'amount_to_receive' => $amountToReceive,
            'amount_received' => $amountReceived,
            'remaining_balance' => $remainingBalance,
            'sales_target' => $salesTarget,
            'recovery_target' => $recoveryTarget,
            'sales_progress' => $salesTarget > 0 ? ($amountReceived / $salesTarget * 100) : 0,
            'recovery_progress' => $recoveryTarget > 0 ? ($amountReceived / $recoveryTarget * 100) : 0,
        ];
    }

    /**
     * Get prospects with filterable comments
     */
    protected function getProspectsWithFilterableComments($user)
    {
        return $user->assignedProspects()
            ->with('interestedSite')
            ->whereNotNull('notes')
            ->where('notes', '!=', '')
            ->get()
            ->map(function($prospect) {
                return [
                    'id' => $prospect->id,
                    'name' => $prospect->full_name,
                    'phone' => $prospect->phone,
                    'site' => $prospect->interestedSite->name ?? 'N/A',
                    'status' => $prospect->status,
                    'comments' => $prospect->notes,
                    'comment_category' => $this->categorizeComment($prospect->notes),
                ];
            });
    }

    /**
     * Get commercial pipeline status for sales manager
     */
    protected function getCommercialPipelineStatus($commercialId)
    {
        $prospects = Prospect::where('assigned_to_id', $commercialId)->get();
        
        return [
            'total_prospects' => $prospects->count(),
            'new_prospects' => $prospects->where('status', 'nouveau')->count(),
            'follow_up_prospects' => $prospects->where('status', 'en_relance')->count(),
            'interested_prospects' => $prospects->where('status', 'interesse')->count(),
            'converted_prospects' => $prospects->where('status', 'converti')->count(),
            'abandoned_prospects' => $prospects->where('status', 'abandonne')->count(),
        ];
    }

    /**
     * Get client payment status with color coding
     */
    protected function getClientPaymentStatus($totalAmount, $totalPaid)
    {
        if ($totalPaid >= $totalAmount) {
            return 'À jour';
        } elseif ($totalPaid > 0) {
            return 'Paiement partiel';
        } else {
            return 'À relancer';
        }
    }

    /**
     * Get status color for display
     */
    protected function getStatusColor($status)
    {
        switch ($status) {
            case 'À jour':
                return 'green';
            case 'Paiement partiel':
                return 'orange';
            case 'À relancer':
                return 'red';
            default:
                return 'gray';
        }
    }

    /**
     * Categorize comments for filtering
     */
    protected function categorizeComment($comment)
    {
        $comment = strtolower($comment);
        
        if (strpos($comment, 'rappeler') !== false || strpos($comment, 'appeler') !== false) {
            return 'à rappeler';
        } elseif (strpos($comment, 'visite') !== false) {
            return 'en attente visite';
        } elseif (strpos($comment, 'intéressé') !== false) {
            return 'intéressé';
        } elseif (strpos($comment, 'réfléchir') !== false) {
            return 'en réflexion';
        } else {
            return 'autre';
        }
    }
    
    /**
     * Helper methods pour les filtres
     */
    protected function getFilteredProspectsCount(array $filters = [])
    {
        $query = Prospect::query();
        
        if (!empty($filters['site_id'])) {
            $query->where('interested_site_id', $filters['site_id']);
        }
        
        if (!empty($filters['commercial_id'])) {
            $query->where('assigned_to_id', $filters['commercial_id']);
        }
        
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }
        
        return $query->count();
    }
    
    protected function getFilteredActiveProspectsCount(array $filters = [])
    {
        $query = Prospect::whereIn('status', ['nouveau', 'en_relance', 'interesse']);
        
        if (!empty($filters['site_id'])) {
            $query->where('interested_site_id', $filters['site_id']);
        }
        
        if (!empty($filters['commercial_id'])) {
            $query->where('assigned_to_id', $filters['commercial_id']);
        }
        
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }
        
        return $query->count();
    }
    
    protected function getFilteredContractsCount(array $filters = [])
    {
        return $this->applyFilters(Contract::query(), $filters)->count();
    }
    
    protected function getFilteredSignedContractsCount(array $filters = [])
    {
        return $this->applyFilters(Contract::where('status', 'signe'), $filters)->count();
    }
    
    protected function getFilteredRecentProspects(array $filters = [])
    {
        $query = Prospect::with('interestedSite');
        
        if (!empty($filters['site_id'])) {
            $query->where('interested_site_id', $filters['site_id']);
        }
        
        if (!empty($filters['commercial_id'])) {
            $query->where('assigned_to_id', $filters['commercial_id']);
        }
        
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }
        
        return $query->latest()->take(5)->get();
    }
    
    /**
     * Get monthly sales target for commercial
     */
    protected function getMonthlySalesTarget($user)
    {
        // Objectif de base (peut être configuré en base de données)
        $baseTarget = 5000000; // 5M FCFA par mois
        
        // Ajustement selon l'expérience du commercial
        $monthsExperience = now()->diffInMonths($user->created_at);
        if ($monthsExperience < 6) {
            $baseTarget *= 0.7; // 70% pour nouveaux commerciaux
        } elseif ($monthsExperience > 24) {
            $baseTarget *= 1.3; // 130% pour commerciaux expérimentés
        }
        
        return $baseTarget;
    }
    
    /**
     * Get monthly recovery target for commercial
     */
    protected function getMonthlyRecoveryTarget($user)
    {
        // Calcul basé sur les échéances dues ce mois
        $currentMonth = now()->format('Y-m');
        
        return PaymentSchedule::whereHas('contract', function($q) use ($user) {
                $q->where('generated_by', $user->id);
            })
            ->whereRaw("DATE_FORMAT(due_date, '%Y-%m') = ?", [$currentMonth])
            ->where('is_paid', false)
            ->sum('amount');
    }
    
    /**
     * Get comprehensive site statistics
     */
    protected function getComprehensiveSiteStats(array $filters = [])
    {
        $sites = Site::with(['lots', 'contracts.payments'])
            ->when(!empty($filters['site_id']), function($q) use ($filters) {
                $q->where('id', $filters['site_id']);
            })
            ->get();
            
        return $sites->map(function($site) {
            $totalSales = $site->contracts->sum('total_amount');
            $totalRecovered = $site->contracts->sum(function($contract) {
                return $contract->payments->sum('amount');
            });
            $totalToRecover = max(0, $totalSales - $totalRecovered);
            
            return [
                'id' => $site->id,
                'name' => $site->name,
                'total_lots' => $site->lots->count(),
                'sold_lots' => $site->lots->where('status', 'vendu')->count(),
                'available_lots' => $site->lots->where('status', 'disponible')->count(),
                'total_sales' => $totalSales,
                'total_recovered' => $totalRecovered,
                'total_to_recover' => $totalToRecover,
                'recovery_rate' => $totalSales > 0 ? ($totalRecovered / $totalSales * 100) : 0,
            ];
        });
    }
}
