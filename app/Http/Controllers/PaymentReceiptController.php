<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentReceiptController extends Controller
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
     * Afficher la liste des bordereaux
     */
    public function index(Request $request)
    {
        $query = PaymentReceipt::with(['generatedBy', 'validatedBy']);
        
        // Filtres
        if ($request->filled('date_from')) {
            $query->whereDate('receipt_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('receipt_date', '<=', $request->date_to);
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        $receipts = $query->orderBy('receipt_date', 'desc')
                         ->orderBy('id', 'desc')
                         ->paginate(20);
        
        // Statistiques
        $stats = [
            'total_count' => PaymentReceipt::count(),
            'draft_count' => PaymentReceipt::where('status', 'draft')->count(),
            'finalized_count' => PaymentReceipt::where('status', 'finalized')->count(),
            'today_amount' => PaymentReceipt::whereDate('receipt_date', today())->sum('total_amount'),
        ];
        
        return view('receipts.index', compact('receipts', 'stats'));
    }

    /**
     * Créer un nouveau bordereau journalier
     */
    public function createDaily()
    {
        $today = now()->toDateString();
        
        // Vérifier s'il existe déjà un bordereau pour aujourd'hui
        $existingReceipt = PaymentReceipt::where('type', 'daily')
                                        ->whereDate('receipt_date', $today)
                                        ->where('status', 'draft')
                                        ->first();
        
        if ($existingReceipt) {
            return redirect()->route('receipts.show', $existingReceipt)
                           ->with('info', 'Un bordereau journalier existe déjà pour aujourd\'hui.');
        }
        
        // Récupérer les paiements validés d'aujourd'hui qui ne sont pas encore dans un bordereau
        $validatedPayments = Payment::whereDate('updated_at', $today)
                                  ->where('validation_status', 'completed')
                                  ->whereDoesntHave('paymentReceipts')
                                  ->with(['client', 'site', 'lot'])
                                  ->get();
        
        if ($validatedPayments->isEmpty()) {
            return redirect()->route('receipts.index')
                           ->with('warning', 'Aucun paiement validé aujourd\'hui à inclure dans le bordereau.');
        }
        
        return view('receipts.create-daily', compact('validatedPayments'));
    }

    /**
     * Enregistrer un bordereau journalier
     */
    public function storeDaily(Request $request)
    {
        $request->validate([
            'receipt_date' => 'required|date',
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'exists:payments,id',
            'notes' => 'nullable|string|max:1000'
        ]);
        
        DB::transaction(function () use ($request) {
            // Créer le bordereau
            $receipt = new PaymentReceipt([
                'receipt_number' => PaymentReceipt::generateReceiptNumber($request->receipt_date),
                'receipt_date' => $request->receipt_date,
                'type' => 'daily',
                'period_start' => $request->receipt_date,
                'period_end' => $request->receipt_date,
                'generated_by' => auth()->id(),
                'generated_at' => now(),
                'notes' => $request->notes
            ]);
            
            $receipt->save();
            
            // Attacher les paiements
            $receipt->payments()->attach($request->payment_ids);
            
            // Calculer les totaux
            $receipt->calculateTotals();
            $receipt->save();
        });
        
        return redirect()->route('receipts.index')
                       ->with('success', 'Bordereau journalier créé avec succès.');
    }

    /**
     * Créer un bordereau par période
     */
    public function createPeriod()
    {
        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->endOfMonth()->toDateString();
        
        return view('receipts.create-period', compact('startDate', 'endDate'));
    }

    /**
     * Enregistrer un bordereau de période
     */
    public function storePeriod(Request $request)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'notes' => 'nullable|string|max:1000'
        ]);
        
        // Récupérer les paiements validés de la période qui ne sont pas encore dans un bordereau
        $validatedPayments = Payment::whereBetween('updated_at', [
                                    Carbon::parse($request->period_start)->startOfDay(),
                                    Carbon::parse($request->period_end)->endOfDay()
                                ])
                                ->where('validation_status', 'completed')
                                ->whereDoesntHave('paymentReceipts')
                                ->get();
        
        if ($validatedPayments->isEmpty()) {
            return redirect()->back()
                           ->with('warning', 'Aucun paiement validé trouvé pour cette période.');
        }
        
        DB::transaction(function () use ($request, $validatedPayments) {
            // Créer le bordereau
            $receipt = new PaymentReceipt([
                'receipt_number' => PaymentReceipt::generateReceiptNumber(now()),
                'receipt_date' => now()->toDateString(),
                'type' => 'period',
                'period_start' => $request->period_start,
                'period_end' => $request->period_end,
                'generated_by' => auth()->id(),
                'generated_at' => now(),
                'notes' => $request->notes
            ]);
            
            $receipt->save();
            
            // Attacher les paiements
            $receipt->payments()->attach($validatedPayments->pluck('id'));
            
            // Calculer les totaux
            $receipt->calculateTotals();
            $receipt->save();
        });
        
        return redirect()->route('receipts.index')
                       ->with('success', 'Bordereau de période créé avec succès.');
    }

    /**
     * Afficher un bordereau
     */
    public function show(PaymentReceipt $receipt)
    {
        $receipt->load([
            'payments.client',
            'payments.site', 
            'payments.lot',
            'generatedBy',
            'validatedBy'
        ]);
        
        return view('receipts.show', compact('receipt'));
    }

    /**
     * Finaliser un bordereau
     */
    public function finalize(PaymentReceipt $receipt)
    {
        if (!$receipt->canBeFinalized()) {
            return redirect()->back()
                           ->with('error', 'Ce bordereau ne peut pas être finalisé.');
        }
        
        if ($receipt->finalize(auth()->user())) {
            return redirect()->back()
                           ->with('success', 'Bordereau finalisé avec succès.');
        }
        
        return redirect()->back()
                       ->with('error', 'Erreur lors de la finalisation du bordereau.');
    }

    /**
     * Générer le PDF d'un bordereau
     */
    public function generatePdf(PaymentReceipt $receipt)
    {
        $receipt->load([
            'payments.client',
            'payments.site',
            'payments.lot',
            'generatedBy',
            'validatedBy'
        ]);
        
        $pdf = Pdf::loadView('receipts.pdf', compact('receipt'));
        
        $filename = "bordereau_{$receipt->receipt_number}.pdf";
        
        return $pdf->download($filename);
    }

    /**
     * Supprimer un bordereau (seulement en brouillon)
     */
    public function destroy(PaymentReceipt $receipt)
    {
        if ($receipt->status !== 'draft') {
            return redirect()->back()
                           ->with('error', 'Seuls les bordereaux en brouillon peuvent être supprimés.');
        }
        
        $receipt->delete();
        
        return redirect()->route('receipts.index')
                       ->with('success', 'Bordereau supprimé avec succès.');
    }

    /**
     * API pour récupérer les paiements d'une période
     */
    public function getPaymentsByPeriod(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);
        
        $payments = Payment::whereBetween('updated_at', [
                                Carbon::parse($request->start_date)->startOfDay(),
                                Carbon::parse($request->end_date)->endOfDay()
                            ])
                           ->where('validation_status', 'completed')
                           ->whereDoesntHave('paymentReceipts')
                           ->with(['client', 'site', 'lot'])
                           ->get();
        
        $summary = [
            'total_count' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'adhesion_count' => $payments->where('type', 'adhesion')->count(),
            'adhesion_amount' => $payments->where('type', 'adhesion')->sum('amount'),
            'reservation_count' => $payments->where('type', 'reservation')->count(),
            'reservation_amount' => $payments->where('type', 'reservation')->sum('amount'),
            'mensualite_count' => $payments->where('type', 'mensualite')->count(),
            'mensualite_amount' => $payments->where('type', 'mensualite')->sum('amount'),
        ];
        
        return response()->json([
            'payments' => $payments,
            'summary' => $summary
        ]);
    }
}