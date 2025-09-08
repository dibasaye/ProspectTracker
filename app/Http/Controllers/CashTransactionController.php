<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Payment;
use App\Models\Prospect;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CashTransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        
        // Seuls les caissiers, responsables et admins peuvent accéder
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!in_array($user->role, ['caissier', 'responsable_commercial', 'administrateur'])) {
                abort(403, 'Accès non autorisé.');
            }
            return $next($request);
        });
    }

    /**
     * Afficher le tableau de bord de la caisse avec filtres avancés
     */
    public function index(Request $request)
    {
        // Déterminer la période de filtrage
        $filterDate = $this->getFilterDate($request);
        
        // Statistiques pour la période sélectionnée
        $periodEncaissements = CashTransaction::encaissements()
                                           ->validated()
                                           ->whereBetween('transaction_date', [$filterDate['start'], $filterDate['end']])
                                           ->sum('amount');
        
        $periodDecaissements = CashTransaction::decaissements()
                                           ->validated()
                                           ->whereBetween('transaction_date', [$filterDate['start'], $filterDate['end']])
                                           ->sum('amount');
        
        $soldePeriode = $periodEncaissements - $periodDecaissements;
        
        // Statistiques du jour
        $today = now()->toDateString();
        $todayEncaissements = CashTransaction::encaissements()
                                           ->validated()
                                           ->whereDate('transaction_date', $today)
                                           ->sum('amount');
        
        $todayDecaissements = CashTransaction::decaissements()
                                           ->validated()
                                           ->whereDate('transaction_date', $today)
                                           ->sum('amount');
        
        // Transactions avec filtres avancés
        $query = CashTransaction::with(['createdBy', 'validatedBy', 'client', 'site', 'payment']);
        
        // Filtres par période
        if ($request->filled('filter_period')) {
            $query->whereBetween('transaction_date', [$filterDate['start'], $filterDate['end']]);
        }
        
        // Filtres par dates personnalisées
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }
        
        // Filtres par type, statut, catégorie
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        
        // Filtrer seulement les transactions validées si demandé
        if ($request->filled('only_validated') && $request->only_validated == '1') {
            $query->validated();
        }
        
        $transactions = $query->orderBy('transaction_date', 'desc')
                             ->orderBy('id', 'desc')
                             ->paginate(20);
        
        // Statistiques générales
        $stats = [
            'today_encaissements' => $todayEncaissements,
            'today_decaissements' => $todayDecaissements,
            'solde_jour' => $todayEncaissements - $todayDecaissements,
            'period_encaissements' => $periodEncaissements,
            'period_decaissements' => $periodDecaissements,
            'solde_periode' => $soldePeriode,
            'pending_count' => CashTransaction::pending()->count(),
            'pending_decaissements' => CashTransaction::decaissements()->pending()->count(),
            'total_encaissements' => CashTransaction::encaissements()->validated()->sum('amount'),
            'total_decaissements' => CashTransaction::decaissements()->validated()->sum('amount'),
            'filter_period' => $request->filter_period ?? 'today',
            'filter_start' => $filterDate['start'],
            'filter_end' => $filterDate['end']
        ];
        
        $stats['solde_total'] = $stats['total_encaissements'] - $stats['total_decaissements'];
        
        return view('cash.index', compact('transactions', 'stats'));
    }
    
    /**
     * Déterminer les dates de début et fin selon le filtre de période
     */
    private function getFilterDate(Request $request)
    {
        $today = now();
        
        switch ($request->filter_period) {
            case 'yesterday':
                return [
                    'start' => $today->copy()->subDay()->startOfDay(),
                    'end' => $today->copy()->subDay()->endOfDay()
                ];
                
            case 'this_week':
                return [
                    'start' => $today->copy()->startOfWeek(),
                    'end' => $today->copy()->endOfWeek()
                ];
                
            case 'last_week':
                return [
                    'start' => $today->copy()->subWeek()->startOfWeek(),
                    'end' => $today->copy()->subWeek()->endOfWeek()
                ];
                
            case 'this_month':
                return [
                    'start' => $today->copy()->startOfMonth(),
                    'end' => $today->copy()->endOfMonth()
                ];
                
            case 'last_month':
                return [
                    'start' => $today->copy()->subMonth()->startOfMonth(),
                    'end' => $today->copy()->subMonth()->endOfMonth()
                ];
                
            case 'this_year':
                return [
                    'start' => $today->copy()->startOfYear(),
                    'end' => $today->copy()->endOfYear()
                ];
                
            case 'last_year':
                return [
                    'start' => $today->copy()->subYear()->startOfYear(),
                    'end' => $today->copy()->subYear()->endOfYear()
                ];
                
            case 'today':
            default:
                return [
                    'start' => $today->copy()->startOfDay(),
                    'end' => $today->copy()->endOfDay()
                ];
        }
    }

    /**
     * Afficher le formulaire de création d'encaissement
     */
    public function createEncaissement()
    {
        $clients = Prospect::all();
        $sites = Site::all();
        $payments = Payment::where('validation_status', 'completed')
                          ->whereDoesntHave('cashTransactions')
                          ->with(['client', 'site'])
                          ->get();
        
        return view('cash.create-encaissement', compact('clients', 'sites', 'payments'));
    }

    /**
     * Enregistrer un encaissement
     */
    public function storeEncaissement(Request $request)
    {
        $request->validate([
            'transaction_date' => 'required|date',
            'category' => 'required|in:vente_terrain,adhesion,reservation,mensualite',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:1000',
            'reference' => 'nullable|string|max:255',
            'client_id' => 'nullable|exists:prospects,id',
            'site_id' => 'nullable|exists:sites,id',
            'payment_id' => 'nullable|exists:payments,id',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'notes' => 'nullable|string|max:1000'
        ]);
        
        DB::transaction(function () use ($request) {
            $receiptPath = null;
            
            // Gestion du fichier justificatif
            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')->store('cash/receipts', 'public');
            }
            
            // Créer la transaction
            CashTransaction::create([
                'transaction_number' => CashTransaction::generateTransactionNumber('encaissement', $request->transaction_date),
                'transaction_date' => $request->transaction_date,
                'type' => 'encaissement',
                'category' => $request->category,
                'amount' => $request->amount,
                'reference' => $request->reference,
                'description' => $request->description,
                'payment_id' => $request->payment_id,
                'client_id' => $request->client_id,
                'site_id' => $request->site_id,
                'created_by' => auth()->id(),
                'receipt_path' => $receiptPath,
                'notes' => $request->notes
            ]);
        });
        
        return redirect()->route('cash.index')
                       ->with('success', 'Encaissement enregistré avec succès.');
    }

    /**
     * Afficher le formulaire de création de décaissement
     */
    public function createDecaissement()
    {
        $suppliers = User::where('role', '!=', 'client')->get();
        
        return view('cash.create-decaissement', compact('suppliers'));
    }

    /**
     * Enregistrer un décaissement
     */
    public function storeDecaissement(Request $request)
    {
        $request->validate([
            'transaction_date' => 'required|date',
            'category' => 'required|in:salaire,charge_social,fourniture,transport,maintenance,marketing,administration,autre',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:1000',
            'reference' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|exists:users,id',
            'receipt' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048', // OBLIGATOIRE pour les décaissements
            'notes' => 'nullable|string|max:1000'
        ], [
            'receipt.required' => 'La pièce justificative est obligatoire pour tous les décaissements.',
            'receipt.file' => 'La pièce justificative doit être un fichier valide.',
            'receipt.mimes' => 'La pièce justificative doit être au format JPG, JPEG, PNG ou PDF.',
            'receipt.max' => 'La taille de la pièce justificative ne doit pas dépasser 2 Mo.'
        ]);
        
        DB::transaction(function () use ($request) {
            // Stocker le fichier justificatif (obligatoire)
            $receiptPath = $request->file('receipt')->store('cash/receipts', 'public');
            
            // Créer la transaction avec le statut en attente pour validation admin
            CashTransaction::create([
                'transaction_number' => CashTransaction::generateTransactionNumber('decaissement', $request->transaction_date),
                'transaction_date' => $request->transaction_date,
                'type' => 'decaissement',
                'category' => $request->category,
                'amount' => $request->amount,
                'reference' => $request->reference,
                'description' => $request->description,
                'supplier_id' => $request->supplier_id,
                'created_by' => auth()->id(),
                'status' => 'pending', // Tous les décaissements doivent être validés par l'admin
                'receipt_path' => $receiptPath,
                'notes' => $request->notes
            ]);
        });
        
        return redirect()->route('cash.index')
                       ->with('success', 'Décaissement enregistré avec succès. Il sera soumis à validation administrative.');
    }

    /**
     * Afficher les détails d'une transaction
     */
    public function show(CashTransaction $transaction)
    {
        $transaction->load([
            'createdBy',
            'validatedBy',
            'client',
            'site',
            'supplier',
            'payment.client'
        ]);
        
        return view('cash.show', compact('transaction'));
    }

    /**
     * Valider une transaction (seuls les administrateurs peuvent valider les décaissements)
     */
    public function validateTransaction(CashTransaction $transaction)
    {
        if (!$transaction->canBeValidated()) {
            return redirect()->back()
                           ->with('error', 'Cette transaction ne peut pas être validée.');
        }
        
        $user = auth()->user();
        
        // Vérification stricte : seuls les administrateurs peuvent valider les décaissements
        if ($transaction->type === 'decaissement' && $user->role !== 'administrateur') {
            return redirect()->back()
                           ->with('error', 'Seuls les administrateurs peuvent valider les décaissements.');
        }
        
        // Les encaissements peuvent être validés par les caissiers, responsables et administrateurs
        if ($transaction->type === 'encaissement' && !in_array($user->role, ['caissier', 'responsable_commercial', 'administrateur'])) {
            return redirect()->back()
                           ->with('error', 'Vous n\'avez pas les permissions pour valider cette transaction.');
        }
        
        if ($transaction->validate($user)) {
            $message = $transaction->type === 'decaissement' 
                ? 'Décaissement validé par l\'administrateur avec succès.'
                : 'Encaissement validé avec succès.';
                
            return redirect()->back()->with('success', $message);
        }
        
        return redirect()->back()
                       ->with('error', 'Erreur lors de la validation.');
    }

    /**
     * Annuler une transaction
     */
    public function cancel(CashTransaction $transaction)
    {
        if ($transaction->cancel()) {
            return redirect()->back()
                           ->with('success', 'Transaction annulée avec succès.');
        }
        
        return redirect()->back()
                       ->with('error', 'Impossible d\'annuler cette transaction.');
    }

    /**
     * Rapport de caisse
     */
    public function rapport(Request $request)
    {
        $startDate = $request->filled('start_date') ? 
                    Carbon::parse($request->start_date) : 
                    now()->startOfMonth();
        
        $endDate = $request->filled('end_date') ? 
                  Carbon::parse($request->end_date) : 
                  now()->endOfMonth();
        
        // Transactions de la période
        $transactions = CashTransaction::whereBetween('transaction_date', [$startDate, $endDate])
                                     ->validated()
                                     ->with(['createdBy', 'client', 'site'])
                                     ->orderBy('transaction_date')
                                     ->get();
        
        // Grouper par date
        $transactionsByDate = $transactions->groupBy('transaction_date');
        
        // Statistiques par catégorie
        $encaissementsByCategory = $transactions->where('type', 'encaissement')
                                              ->groupBy('category')
                                              ->map->sum('amount');
        
        $decaissementsByCategory = $transactions->where('type', 'decaissement')
                                              ->groupBy('category')
                                              ->map->sum('amount');
        
        $rapport = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_encaissements' => $transactions->where('type', 'encaissement')->sum('amount'),
            'total_decaissements' => $transactions->where('type', 'decaissement')->sum('amount'),
            'transactions_by_date' => $transactionsByDate,
            'encaissements_by_category' => $encaissementsByCategory,
            'decaissements_by_category' => $decaissementsByCategory
        ];
        
        $rapport['solde'] = $rapport['total_encaissements'] - $rapport['total_decaissements'];
        
        return view('cash.rapport', compact('rapport'));
    }
}