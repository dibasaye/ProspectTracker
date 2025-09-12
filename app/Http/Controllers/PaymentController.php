<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Prospect;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf; 
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function create(Prospect $prospect)
    {
        // Récupérer les sites disponibles (tu peux adapter selon ta logique)
        $sites = Site::all();

        return view('payments.create', compact('prospect', 'sites'));
    }

    public function store(Request $request, Prospect $prospect)
    {
        $request->validate([
            'site_id' => 'required|exists:sites,id',
            'type' => 'required|in:adhesion,reservation,mensualite',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:255',
            'payment_date' => 'required|date',

            'description' => 'nullable|string|max:1000',
        ]);

        // Générer une référence unique
        $reference_number = 'PAY-' . strtoupper(Str::random(8));

        Payment::create([
            'client_id' => $prospect->id,
            'site_id' => $request->site_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_date' => $request->payment_date,
            'reference_number' => $reference_number,
            'is_confirmed' => false, // En attente de validation par le caissier
            'validation_status' => 'pending', // Statut initial
            'description' => $request->description,
        ]);

        return redirect()->route('prospects.show', $prospect)->with('success', 'Paiement enregistré avec succès. En attente de validation par le caissier.');
    }

    public function invoice(Payment $payment)
{
    $pdf = Pdf::loadView('payments.invoice', compact('payment'));
    return $pdf->stream('facture-'.$payment->reference_number.'.pdf');
}

public function createReservationPayment(Prospect $prospect)
{
    $amount = $prospect->interestedSite->reservation_fee ?? 500000;

    return view('payments.create_reservation', compact('prospect', 'amount'));
}

public function storeReservationPayment(Request $request, Prospect $prospect)
{
    $request->validate([
        'amount' => 'required|numeric|min:1',
        'payment_method' => 'required|string',
        'payment_date' => 'required|date',
    ]);

    $payment = Payment::create([
        'client_id' => $prospect->id,
        'site_id' => $prospect->interested_site_id,
        'lot_id' => optional($prospect->reservation)->lot_id,
        'type' => 'reservation',
        'amount' => $request->amount,
        'payment_method' => $request->payment_method,
        'payment_date' => $request->payment_date,
        'reference_number' => strtoupper(uniqid('RSV-')),
        'description' => 'Paiement d\'acompte de réservation',
        'is_confirmed' => false,
        'validation_status' => 'pending', // Statut initial en attente de validation
    ]);

    // Marquer le prospect comme réservataire
    $prospect->markAsReservataire();

    // Marquer le prospect comme intéressé s'il ne l'était pas déjà
    if (!$prospect->isInteresse()) {
        $prospect->markAsInteresse();
    }

    // Verrouiller définitivement le lot
    if ($prospect->reservation && $prospect->reservation->lot) {
        $lot = $prospect->reservation->lot;
        $lot->status = 'vendu';
        $lot->save();
    }

    return redirect()->route('prospects.show', $prospect)
        ->with('success', 'Paiement de réservation enregistré avec succès. Le prospect a été marqué comme réservataire.');
}

public function myPayments()
{
    $user = Auth::user();
    
    if (!$user->isAgent()) {
        abort(403, 'Accès non autorisé.');
    }
    
    $payments = Payment::whereHas('client', function($q) use ($user) {
        $q->where('assigned_to_id', $user->id);
    })->with(['client', 'site', 'caissierValidatedBy', 'managerValidatedBy'])
    ->orderBy('created_at', 'desc')
    ->paginate(20);
    
    return view('payments.my_payments', compact('payments'));
}

    /**
     * Génère une facture globale pour un client avec tous ses lots
     */
    public function globalInvoice(Prospect $prospect)
    {
        // Charger tous les lots du client avec leurs sites
        $lots = $prospect->lots()->with('site')->get();

        if ($lots->isEmpty()) {
            return back()->with('error', 'Ce client n\'a aucun lot associé.');
        }

        // Calculer le total
        $totalAmount = $lots->sum('final_price');

        // Préparer les données pour la facture
        $data = [
            'prospect' => $prospect,
            'lots' => $lots,
            'totalAmount' => $totalAmount,
            'invoiceNumber' => 'INV-' . str_pad($prospect->id, 4, '0', STR_PAD_LEFT),
            'invoiceDate' => now()->format('d/m/Y'),
        ];

        // Générer le PDF
        $pdf = PDF::loadView('prospects.global-invoice', $data);
        
        return $pdf->stream('facture-globale-' . $prospect->full_name . '.pdf');
    }

}
