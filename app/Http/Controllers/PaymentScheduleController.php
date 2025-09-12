<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentSchedule;
use App\Models\Contract;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Prospect;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentScheduleController extends Controller
{
    /**
     * Créer un paiement en attente pour une échéance (processus de validation à 4 étapes)
     */
    public function pay(Request $request, \App\Models\PaymentSchedule $schedule)
    {
        // Vérification d'autorisation - s'assurer que l'utilisateur peut effectuer ce paiement
        $user = auth()->user();
        $client = $schedule->contract->client;
        
        // Seuls les administrateurs, responsables commerciaux ou le commercial assigné au client peuvent effectuer un paiement
        if (!($user->isAdmin() || $user->isManager()) && $client->assigned_to_id !== $user->id) {
            abort(403, 'Vous n\'êtes pas autorisé à effectuer ce paiement pour ce client.');
        }
        
        if ($schedule->is_paid) {
            return back()->with('info', 'Ce paiement a déjà été effectué.');
        }

        // Vérifier s'il n'y a pas déjà un paiement en cours de validation pour cette échéance
        $existingPayment = \App\Models\Payment::where('payment_schedule_id', $schedule->id)
            ->whereIn('validation_status', ['pending', 'caissier_validated', 'responsable_validated', 'admin_validated'])
            ->first();

        if ($existingPayment) {
            return back()->with('info', 'Un paiement pour cette échéance est déjà en cours de validation.');
        }

        $request->validate([
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'payment_proof' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ], [
            'payment_proof.mimes' => 'Le fichier doit être au format PDF, JPG ou PNG',
            'payment_proof.max' => 'Le fichier ne doit pas dépasser 2 Mo',
            'amount.required' => 'Le montant est obligatoire',
            'amount.numeric' => 'Le montant doit être un nombre',
            'amount.min' => 'Le montant ne peut pas être négatif',
        ]);

        // Gérer le téléchargement du justificatif si fourni
        $paymentProofPath = null;
        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $paymentProofPath = $file->store('payment_proofs', 'public');
        }

        // Créer un paiement en attente de validation
        $payment = \App\Models\Payment::create([
            'client_id' => $schedule->contract->client_id,
            'site_id' => $schedule->contract->site_id,
            'lot_id' => $schedule->contract->lot_id,
            'contract_id' => $schedule->contract_id,
            'payment_schedule_id' => $schedule->id,
            'type' => 'mensualite',
            'amount' => $request->amount,
            'payment_date' => now(),
            'due_date' => $schedule->due_date,
            'payment_method' => $request->payment_method,
            'notes' => $request->notes,
            'payment_proof_path' => $paymentProofPath,
            'reference_number' => 'PAY-' . strtoupper(uniqid()),
            // Statut initial en attente de validation
            'validation_status' => 'pending',
            'caissier_validated' => false,
            'responsable_validated' => false,
            'admin_validated' => false,
            'is_confirmed' => false,
            'created_by' => auth()->id(),
        ]);

        // Enregistrer l'activité
        activity()
            ->performedOn($payment)
            ->causedBy(auth()->user())
            ->withProperties([
                'payment_schedule_id' => $schedule->id,
                'installment_number' => $schedule->installment_number,
                'contract_number' => $schedule->contract->contract_number,
            ])
            ->log('Paiement d\'échéance créé et mis en attente de validation');

        return redirect()->route('schedules.payment-proof', $payment->id)
            ->with('success', 'Versement de ' . number_format($request->amount, 0, ',', ' ') . ' F enregistré avec succès pour l\'échéance #' . $schedule->installment_number . '. Le paiement est maintenant en attente de validation par le caissier.');
    }


    /**
     * Générer le reçu de versement initial (paiement en attente de validation)
     */
    public function paymentProof(\App\Models\Payment $payment)
    {
        // Vérifier l'autorisation - l'utilisateur doit être autorisé à voir ce paiement
        $user = auth()->user();
        $client = $payment->client;
        
        if (!($user->isAdmin() || $user->isManager()) && $client->assigned_to_id !== $user->id) {
            abort(403, 'Vous n\'êtes pas autorisé à voir ce reçu.');
        }
        
        // Récupérer l'échéance associée
        $schedule = $payment->paymentSchedule;
        
        if (!$schedule) {
            return back()->with('error', 'Échéance non trouvée pour ce paiement.');
        }

        $pdf = Pdf::loadView('receipts.payment_proof', compact('payment', 'schedule'));
        return $pdf->download('recu_versement_'.$payment->reference_number.'.pdf');
    }

    /**
     * Télécharger le reçu de paiement final (échéance complètement payée)
     */
    public function downloadReceipt(\App\Models\PaymentSchedule $schedule)
    {
        // Vérifier l'autorisation - même logique que pour les autres méthodes
        $user = auth()->user();
        $client = $schedule->contract->client;
        
        if (!($user->isAdmin() || $user->isManager()) && $client->assigned_to_id !== $user->id) {
            abort(403, 'Vous n\'êtes pas autorisé à télécharger ce reçu.');
        }
        
        if (!$schedule->is_paid) {
            return back()->with('error', 'Ce paiement n\'a pas encore été effectué.');
        }

        $pdf = Pdf::loadView('receipts.pdf', compact('schedule'));
        return $pdf->download('recu_paiement_'.$schedule->id.'.pdf');
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $status = $request->get('status', 'all');
        $month = $request->get('month', now()->format('Y-m'));
        $commercial = $request->get('commercial', 'all');

        // Requête de base pour les échéances
        $query = PaymentSchedule::with(['contract.client', 'contract.site', 'contract.lot'])
            ->whereHas('contract', function($q) {
                $q->where('status', 'signe');
            });

        if ($status !== 'all') {
            $query->where('is_paid', $status === 'paid');
        }

        if ($month) {
            $query->whereYear('due_date', substr($month, 0, 4))
                  ->whereMonth('due_date', substr($month, 5, 2));
        }

        if ($user->isManager() || $user->isAdmin()) {
            if ($commercial !== 'all') {
                $query->whereHas('contract.client', function($q) use ($commercial) {
                    $q->where('assigned_to_id', $commercial);
                });
            }
        } else {
            $query->whereHas('contract.client', function($q) use ($user) {
                $q->where('assigned_to_id', $user->id);
            });
        }

        $schedules = $query->orderBy('due_date')->get();

        // Grouper par client et calculer les totaux avec détails des échéances
        $clientsData = [];
        foreach ($schedules as $schedule) {
            $clientId = $schedule->contract->client->id;
            $client = $schedule->contract->client;
            
            if (!isset($clientsData[$clientId])) {
                $clientsData[$clientId] = [
                    'client' => $client,
                    'total_amount' => 0,
                    'total_amount_due' => 0,
                    'paid_amount' => 0,
                    'pending_amount' => 0,
                    'total_schedules' => 0,
                    'paid_schedules' => 0,
                    'pending_schedules' => 0,
                    'overdue_schedules' => 0,
                    'next_due_date' => null,
                    'contracts' => [],
                    'schedules' => [],
                    'upcoming_schedules' => [], // Prochaines échéances à payer
                    'current_installments' => [] // Échéances actuelles par numéro
                ];
            }
            
            $clientsData[$clientId]['total_amount'] += $schedule->amount;
            $clientsData[$clientId]['total_amount_due'] += $schedule->amount;
            $clientsData[$clientId]['total_schedules'] += 1;
            $clientsData[$clientId]['schedules'][] = $schedule;
            
            // Organiser les échéances par numéro pour un meilleur affichage
            $installmentNumber = $schedule->installment_number;
            if (!isset($clientsData[$clientId]['current_installments'][$installmentNumber])) {
                $clientsData[$clientId]['current_installments'][$installmentNumber] = [];
            }
            $clientsData[$clientId]['current_installments'][$installmentNumber][] = $schedule;
            
            if ($schedule->is_paid) {
                $clientsData[$clientId]['paid_amount'] += $schedule->amount;
                $clientsData[$clientId]['paid_schedules'] += 1;
            } else {
                $clientsData[$clientId]['pending_amount'] += $schedule->amount;
                $clientsData[$clientId]['pending_schedules'] += 1;
                
                // Ajouter aux échéances à venir (max 3 prochaines échéances non payées)
                if (count($clientsData[$clientId]['upcoming_schedules']) < 3) {
                    $clientsData[$clientId]['upcoming_schedules'][] = $schedule;
                }
                
                if ($schedule->due_date->isPast()) {
                    $clientsData[$clientId]['overdue_schedules'] += 1;
                }
                
                // Prochaine échéance
                if (!$clientsData[$clientId]['next_due_date'] || 
                    $schedule->due_date < $clientsData[$clientId]['next_due_date']) {
                    $clientsData[$clientId]['next_due_date'] = $schedule->due_date;
                }
            }
            
            // Ajouter les contrats uniques
            $contractId = $schedule->contract_id;
            if (!isset($clientsData[$clientId]['contracts'][$contractId])) {
                $clientsData[$clientId]['contracts'][$contractId] = $schedule->contract;
            }
        }

        // Trier les échéances à venir par date d'échéance pour chaque client
        foreach ($clientsData as &$clientData) {
            usort($clientData['upcoming_schedules'], function($a, $b) {
                return $a->due_date <=> $b->due_date;
            });
            // Trier les échéances par numéro d'installment
            ksort($clientData['current_installments']);
        }

        // Convertir en collection et paginer
        $clientsCollection = collect($clientsData)->values();
        $currentPage = $request->get('page', 1);
        $perPage = 10;
        $clientsPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $clientsCollection->forPage($currentPage, $perPage),
            $clientsCollection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $stats = [
            'total_installments' => PaymentSchedule::whereHas('contract', function($q) {
                $q->where('status', 'signe');
            })->count(),
            'paid_installments' => PaymentSchedule::whereHas('contract', function($q) {
                $q->where('status', 'signe');
            })->where('is_paid', true)->count(),
            'pending_installments' => PaymentSchedule::whereHas('contract', function($q) {
                $q->where('status', 'signe');
            })->where('is_paid', false)->count(),
            'total_amount' => PaymentSchedule::whereHas('contract', function($q) {
                $q->where('status', 'signe');
            })->sum('amount'),
            'paid_amount' => PaymentSchedule::whereHas('contract', function($q) {
                $q->where('status', 'signe');
            })->where('is_paid', true)->sum('amount'),
            'pending_amount' => PaymentSchedule::whereHas('contract', function($q) {
                $q->where('status', 'signe');
            })->where('is_paid', false)->sum('amount'),
        ];

        $commercials = User::where('role', 'commercial')
            ->where('is_active', true)
            ->get();

        $monthlyData = collect();
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyData->push([
                'month' => $month->format('M Y'),
                'due_amount' => PaymentSchedule::whereHas('contract', function($q) {
                    $q->where('status', 'signe');
                })->whereYear('due_date', $month->year)
                  ->whereMonth('due_date', $month->month)
                  ->sum('amount'),
                'paid_amount' => PaymentSchedule::whereHas('contract', function($q) {
                    $q->where('status', 'signe');
                })->where('is_paid', true)
                  ->whereYear('paid_date', $month->year)
                  ->whereMonth('paid_date', $month->month)
                  ->sum('amount'),
            ]);
        }

        return view('payment_schedules.index', compact(
            'clientsPaginated', 
            'stats', 
            'commercials', 
            'monthlyData',
            'status',
            'month',
            'commercial'
        ));
    }

    public function export(Request $request)
    {
        $user = Auth::user();

        $status = $request->get('status', 'all');
        $month = $request->get('month', now()->format('Y-m'));
        $commercial = $request->get('commercial', 'all');

        $query = PaymentSchedule::with(['contract.client', 'contract.site', 'contract.lot'])
            ->whereHas('contract', function($q) {
                $q->where('status', 'signe');
            });

        if ($status !== 'all') {
            $query->where('is_paid', $status === 'paid');
        }

        if ($month) {
            $query->whereYear('due_date', substr($month, 0, 4))
                  ->whereMonth('due_date', substr($month, 5, 2));
        }

        if ($user->isManager() || $user->isAdmin()) {
            if ($commercial !== 'all') {
                $query->whereHas('contract.client', function($q) use ($commercial) {
                    $q->where('assigned_to_id', $commercial);
                });
            }
        } else {
            $query->whereHas('contract.client', function($q) use ($user) {
                $q->where('assigned_to_id', $user->id);
            });
        }

        $schedules = $query->orderBy('due_date')->get();

        $filename = 'echeancier_paiements_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($schedules) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Client',
                'Téléphone',
                'Contrat',
                'Site',
                'Lot',
                'Échéance N°',
                'Date d\'échéance',
                'Montant',
                'Statut',
                'Date de paiement',
                'Méthode de paiement',
                'Notes'
            ]);

            foreach ($schedules as $schedule) {
                fputcsv($file, [
                    $schedule->contract->client->full_name,
                    $schedule->contract->client->phone,
                    $schedule->contract->contract_number,
                    $schedule->contract->site->name ?? 'N/A',
                    $schedule->contract->lot->reference ?? 'N/A',
                    $schedule->installment_number,
                    $schedule->due_date->format('d/m/Y'),
                    number_format($schedule->amount, 0, ',', ' ') . ' FCFA',
                    $schedule->is_paid ? 'Payé' : 'En attente',
                    $schedule->paid_date ? $schedule->paid_date->format('d/m/Y') : 'N/A',
                    $schedule->payment_method ?? 'N/A',
                    $schedule->notes ?? 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function clientSchedules(Request $request, Prospect $client)
    {
        if (auth()->user()->isAgent() && $client->assigned_to_id !== auth()->id()) {
            abort(403, 'Accès non autorisé.');
        }

        $contracts = $client->contracts()
            ->with([
                'site',
                'lot' => function($query) {
                    $query->select('id', 'lot_number');
                }
            ])
            ->get();

        \Log::info('Contrats chargés:', [
            'client_id' => $client->id,
            'contrats' => $contracts->map(function($contract) {
                return [
                    'contrat_id' => $contract->id,
                    'lot_id' => $contract->lot_id,
                    'lot_number' => $contract->lot ? $contract->lot->lot_number : null
                ];
            })->toArray()
        ]);

        $schedules = PaymentSchedule::whereIn('contract_id', $contracts->pluck('id'))
            ->with(['contract.site', 'contract.lot'])
            ->orderBy('due_date', 'asc')
            ->get();

        $stats = [
            'total_contracts' => $contracts->count(),
            'total_installments' => $schedules->count(),
            'paid_installments' => $schedules->where('is_paid', true)->count(),
            'pending_installments' => $schedules->where('is_paid', false)->count(),
            'overdue_installments' => $schedules->where('is_paid', false)->filter(function($schedule) {
                return $schedule->due_date->isPast();
            })->count(),
            'total_amount' => $schedules->sum('amount'),
            'paid_amount' => $schedules->where('is_paid', true)->sum('amount'),
            'pending_amount' => $schedules->where('is_paid', false)->sum('amount'),
        ];

        $schedulesByContract = $schedules->groupBy('contract_id');

        return view('payment_schedules.client_detail', compact('client', 'contracts', 'schedules', 'schedulesByContract', 'stats'));
    }

    /**
     * Effectuer un paiement pour un client (trouvera automatiquement la prochaine échéance)
     */
    public function makeClientPayment(Request $request, \App\Models\Prospect $client)
    {
        if (auth()->user()->isAgent() && $client->assigned_to_id !== auth()->id()) {
            abort(403, 'Accès non autorisé.');
        }

        $request->validate([
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'payment_proof' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ], [
            'payment_proof.mimes' => 'Le fichier doit être au format PDF, JPG ou PNG',
            'payment_proof.max' => 'Le fichier ne doit pas dépasser 2 Mo',
            'amount.required' => 'Le montant est obligatoire',
            'amount.numeric' => 'Le montant doit être un nombre',
            'amount.min' => 'Le montant ne peut pas être négatif',
        ]);

        // Trouver la prochaine échéance non payée du client
        $nextSchedule = PaymentSchedule::whereHas('contract', function($q) use ($client) {
                $q->where('client_id', $client->id)->where('status', 'signe');
            })
            ->where('is_paid', false)
            ->orderBy('due_date')
            ->first();

        if (!$nextSchedule) {
            return back()->with('error', 'Aucune échéance en attente trouvée pour ce client.');
        }

        // Vérifier s'il n'y a pas déjà un paiement en cours de validation
        $existingPayment = \App\Models\Payment::where('payment_schedule_id', $nextSchedule->id)
            ->whereIn('validation_status', ['pending', 'caissier_validated', 'responsable_validated', 'admin_validated'])
            ->first();

        if ($existingPayment) {
            return back()->with('info', 'Un paiement pour la prochaine échéance de ce client est déjà en cours de validation.');
        }

        // Gérer le téléchargement du justificatif
        $paymentProofPath = null;
        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $paymentProofPath = $file->store('payment_proofs', 'public');
        }

        // Créer le paiement
        $payment = \App\Models\Payment::create([
            'client_id' => $client->id,
            'site_id' => $nextSchedule->contract->site_id,
            'lot_id' => $nextSchedule->contract->lot_id,
            'contract_id' => $nextSchedule->contract_id,
            'payment_schedule_id' => $nextSchedule->id,
            'type' => 'mensualite',
            'amount' => $request->amount,
            'payment_date' => now(),
            'due_date' => $nextSchedule->due_date,
            'payment_method' => $request->payment_method,
            'notes' => $request->notes,
            'payment_proof_path' => $paymentProofPath,
            'reference_number' => 'PAY-' . strtoupper(uniqid()),
            'validation_status' => 'pending',
            'caissier_validated' => false,
            'responsable_validated' => false,
            'admin_validated' => false,
            'is_confirmed' => false,
            'created_by' => auth()->id(),
            // Ajout des champs requis pour le système de validation à 4 étapes
            'caissier_amount_received' => $request->amount,
            'description' => 'Versement mensualité - Échéance #' . $nextSchedule->installment_number,
        ]);

        // Enregistrer l'activité
        activity()
            ->performedOn($payment)
            ->causedBy(auth()->user())
            ->withProperties([
                'client_name' => $client->full_name,
                'payment_schedule_id' => $nextSchedule->id,
                'installment_number' => $nextSchedule->installment_number,
                'contract_number' => $nextSchedule->contract->contract_number,
            ])
            ->log('Versement client créé et mis en attente de validation');

        return back()->with('success', 'Versement de ' . number_format($request->amount, 0, ',', ' ') . ' F enregistré avec succès pour ' . $client->full_name . '. Le paiement est maintenant en cours de validation.');
    }

    /**
     * Récupérer l'historique des paiements d'un client
     */
    public function getClientPaymentHistory(\App\Models\Prospect $client)
    {
        if (auth()->user()->isAgent() && $client->assigned_to_id !== auth()->id()) {
            abort(403, 'Accès non autorisé.');
        }

        $payments = \App\Models\Payment::where('client_id', $client->id)
            ->with(['contract', 'paymentSchedule'])
            ->orderBy('payment_date', 'desc')
            ->get();

        $totalPaid = $payments->where('validation_status', 'completed')->sum('amount');

        return response()->json([
            'payments' => $payments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'payment_date' => $payment->payment_date,
                    'payment_method' => $payment->payment_method,
                    'validation_status' => $payment->validation_status,
                    'reference_number' => $payment->reference_number,
                    'notes' => $payment->notes,
                    'contract_number' => $payment->contract ? $payment->contract->contract_number : null,
                    'installment_number' => $payment->paymentSchedule ? $payment->paymentSchedule->installment_number : null,
                ];
            }),
            'total_paid' => $totalPaid,
            'client' => [
                'id' => $client->id,
                'name' => $client->full_name,
                'phone' => $client->phone,
            ]
        ]);
    }
}