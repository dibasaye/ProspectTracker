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
        
        if ($user->isSalesManager()) {
            return $this->getSalesManagerDashboardData($filters);
        }
        
        return $this->getCommercialDashboardData($user, $filters);
    }
    
    /**
     * Get admin dashboard data
     */
    protected function getAdminDashboardData(array $filters)
    {
        $query = $this->applyFilters(Contract::query(), $filters);
        
        $totalClients = Prospect::where('status', 'client')
            ->when(method_exists(Prospect::class, 'filter'), function($q) use ($filters) {
                $q->filter($filters);
            })
            ->count();
            
        $totalSales = (clone $query)->sum('total_amount');
        $totalPaid = $this->getTotalPaid($filters);
        $totalPending = max(0, $totalSales - $totalPaid);
        
        // Préparer les données pour votre vue existante
        return [
            'stats' => [
                'total_prospects' => Prospect::count(),
                'active_prospects' => Prospect::whereIn('status', ['nouveau', 'en_relance', 'interesse'])->count(),
                'total_sites' => Site::count(),
                'sold_lots' => $this->getSoldLotsCount($filters),
                'total_payments' => Payment::sum('amount'),
                'pending_payments' => Payment::where('validation_status', 'pending')->count(),
                'total_contracts' => Contract::count(),
                'signed_contracts' => Contract::where('status', 'signe')->count(),
            ],
            'recentProspects' => Prospect::with('interestedSite')->latest()->take(5)->get(),
            'recentPayments' => Payment::with(['client', 'site'])->latest()->take(5)->get(),
            'pendingPayments' => Payment::where('validation_status', 'pending')->with(['client', 'site'])->get(),
        ];
    }
    
    /**
     * Get sales manager dashboard data
     */
    protected function getSalesManagerDashboardData(array $filters)
    {
        $commercials = User::where('role', 'commercial')
            ->where('is_active', true)
            ->withCount(['contracts', 'assignedProspects'])
            ->get()
            ->map(function($commercial) use ($filters) {
                $commercialFilters = array_merge($filters, ['commercial_id' => $commercial->id]);
                $contracts = $this->getCommercialContracts($commercial->id, $filters);
                $totalSales = $contracts->sum('total_amount');
                $totalPaid = $contracts->sum(function($contract) {
                    return $contract->payments->sum('amount');
                });
                
                return [
                    'id' => $commercial->id,
                    'name' => $commercial->full_name,
                    'total_clients' => $commercial->assigned_prospects_count,
                    'total_sales' => $totalSales,
                    'total_paid' => $totalPaid,
                    'pending_amount' => $totalSales - $totalPaid,
                    'contracts_count' => $commercial->contracts_count,
                ];
            });
            
        // Préparer les données pour votre vue existante
        return [
            'stats' => [
                'total_prospects' => Prospect::count(),
                'active_prospects' => Prospect::whereIn('status', ['nouveau', 'en_relance', 'interesse'])->count(),
                'total_sites' => Site::count(),
                'sold_lots' => $this->getSoldLotsCount($filters),
                'total_payments' => Payment::sum('amount'),
                'pending_payments' => Payment::where('validation_status', 'pending')->count(),
                'total_contracts' => Contract::count(),
                'signed_contracts' => Contract::where('status', 'signe')->count(),
                'commercials' => $commercials,
            ],
            'recentProspects' => Prospect::with('interestedSite')->latest()->take(5)->get(),
            'recentPayments' => Payment::with(['client', 'site'])->latest()->take(5)->get(),
            'commercials' => $commercials,
        ];
    }
    
    /**
     * Get commercial dashboard data
     */
    protected function getCommercialDashboardData($user, array $filters)
    {
        $filters['commercial_id'] = $user->id;
        $contracts = $this->getCommercialContracts($user->id, $filters);
        
        $totalSales = $contracts->sum('total_amount');
        $totalPaid = $contracts->sum(function($contract) {
            return $contract->payments->sum('amount');
        });
        
        // Préparer les données pour votre vue existante
        return [
            'stats' => [
                'my_prospects' => $user->assignedProspects()->count(),
                'active_prospects' => $user->assignedProspects()->whereIn('status', ['nouveau', 'en_relance', 'interesse'])->count(),
                'converted_prospects' => $user->assignedProspects()->where('status', 'converti')->count(),
                'my_contracts' => $contracts->count(),
                'signed_contracts' => $contracts->where('status', 'signe')->count(),
                'my_payments' => $totalPaid,
                'validated_payments' => Payment::whereHas('contract', function($q) use ($user) {
                    $q->where('generated_by', $user->id);
                })->where('validation_status', 'completed')->sum('amount'),
                'pending_payments' => Payment::whereHas('contract', function($q) use ($user) {
                    $q->where('generated_by', $user->id);
                })->where('validation_status', 'pending')->count(),
            ],
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
            ->with(['payments', 'prospect', 'lots']);
            
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
        
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereHas('contract', function($q) use ($filters) {
                $q->whereBetween('signature_date', [
                    $filters['start_date'],
                    $filters['end_date']
                ]);
            });
        }
        
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
     * Get upcoming payments
     */
    protected function getUpcomingPayments(array $filters = [], $limit = 10, $commercialId = null)
    {
        $query = PaymentSchedule::with(['contract.prospect', 'contract.generator'])
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
        $filters = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'commercial_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        
        // Set default date range if not provided
        if (empty($filters['start_date']) || empty($filters['end_date'])) {
            $filters['start_date'] = now()->startOfMonth()->toDateString();
            $filters['end_date'] = now()->endOfMonth()->toDateString();
        }
        
        return $filters;
    }
}