<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Prospect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentValidationController extends Controller
{
    public function __construct()
    {
        // Seuls les caissiers, responsables commerciaux et administrateurs peuvent accéder
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!in_array($user->role, ['caissier', 'responsable_commercial', 'administrateur'])) {
                abort(403, 'Accès non autorisé. Seuls les caissiers, responsables commerciaux et administrateurs peuvent valider les paiements.');
            }
            return $next($request);
        });
    }

    /**
     * Afficher la liste des paiements en attente de validation
     */
    public function index()
    {
        $user = auth()->user();
        
        // Requête de base pour les paiements en attente
        $query = Payment::query();
        
        // Filtrer selon le rôle de l'utilisateur
        if ($user->role === 'caissier') {
            $query->where('validation_status', 'pending')
                  ->where('caissier_validated', 0);
        } 
        elseif ($user->role === 'responsable_commercial') {
            // Afficher les paiements validés par le caissier et en attente du responsable
            $query->where(function($q) {
                $q->where('validation_status', 'caissier_validated')
                  ->orWhere(function($q2) {
                      $q2->where('caissier_validated', 1)
                         ->where('responsable_validated', 0);
                  });
            })->where('responsable_validated', 0);
        } 
        elseif ($user->role === 'administrateur') {
            // Afficher les paiements validés par le responsable et en attente de l'admin
            $query->where('validation_status', 'responsable_validated')
                  ->where('admin_validated', 0);
        }
        
        // Charger les relations nécessaires
        $query->with([
            'client', 
            'site', 
            'lot',
            'caissierValidatedBy',
            'responsableValidatedBy',
            'adminValidatedBy'
        ]);
        
        // Ordonner par date de création décroissante
        $pendingPayments = $query->orderBy('created_at', 'desc')
                               ->paginate(15);

        // Statistiques - Compter les paiements en attente selon le rôle
        $pendingCount = $pendingPayments->total();
        
        // Compter les validations d'aujourd'hui
        $validatedQuery = Payment::where('validation_status', 'completed')
            ->whereDate('updated_at', today());
            
        $stats = [
            'pending_count' => $pendingCount,
            'validated_today' => $validatedQuery->count(),
            'total_amount' => $validatedQuery->sum('amount')
        ];

        return view('payments.validation.index', [
            'pendingPayments' => $pendingPayments,
            'stats' => $stats
        ]);
    }

    /**
     * Afficher les détails d'un paiement pour validation
     */
    public function show(Payment $payment)
    {
        // Vérifier les permissions
        if (!$this->canValidatePayment($payment)) {
            abort(403, 'Vous n\'êtes pas autorisé à valider ce paiement.');
        }

        return view('payments.validation.show', compact('payment'));
    }

    /**
     * Valider un paiement (double validation)
     */
    public function validatePayment(Request $request, Payment $payment)
    {
        $user = auth()->user();
        
        // Vérifier que l'utilisateur peut valider ce paiement
        if ($user->role === 'caissier' && $payment->canBeValidatedByCaissier()) {
            return $this->validateByCaissier($request, $payment);
        } elseif ($user->role === 'responsable_commercial' && $payment->canBeValidatedByResponsable()) {
            return $this->validateByResponsable($request, $payment);
        } elseif ($user->role === 'administrateur' && $payment->canBeValidatedByAdmin()) {
            return $this->validateByAdmin($request, $payment);
        }
        
        return back()->with('error', 'Action non autorisée ou étape de validation incorrecte.');
    }

    /**
     * Validation par le caissier
     */
    private function validateByCaissier(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'confirmation_notes' => 'nullable|string|max:1000',
            'actual_amount_received' => 'required|numeric|min:0',
            'payment_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ], [
            'payment_proof.required' => 'Le justificatif de paiement est obligatoire',
            'payment_proof.mimes' => 'Le fichier doit être au format PDF, JPG ou PNG',
            'payment_proof.max' => 'Le fichier ne doit pas dépasser 2 Mo',
            'actual_amount_received.required' => 'Le montant reçu est obligatoire',
            'actual_amount_received.numeric' => 'Le montant doit être un nombre',
            'actual_amount_received.min' => 'Le montant ne peut pas être négatif',
        ]);

        // Gérer le téléchargement du justificatif
        $file = $request->file('payment_proof');
        $paymentProofPath = $file->store('payment_proofs', 'public');
        
        // Mettre à jour le paiement avec la validation caissier
        $updateData = [
            'caissier_validated' => true,
            'caissier_validated_by' => Auth::id(),
            'responsable_validated_at' => now(), // Utilisation de responsable_validated_at à la place de caissier_validated_at
            'caissier_notes' => $request->confirmation_notes,
            'caissier_amount_received' => $request->actual_amount_received,
            'payment_proof_path' => $paymentProofPath,
            'validation_status' => 'caissier_validated',
            'updated_at' => now(),
        ];
        
        // Mettre à jour le statut du paiement
        $payment->update($updateData);

        // Notifier le responsable
        if ($payment->client && $payment->client->assignedTo) {
            // Ici vous pourriez ajouter une notification par email ou SMS
        }

        return redirect()->route('payments.validation.index')
            ->with('success', 'Paiement validé avec succès par le caissier. En attente de validation par le responsable.');
    }
    
    private function validateByResponsable(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);
        
        // Mettre à jour le paiement avec la validation responsable
        $updateData = [
            'responsable_validated' => true,
            'responsable_validated_by' => Auth::id(),
            'responsable_validated_at' => now(),
            'responsable_notes' => $request->notes,
            'validation_status' => 'responsable_validated',
        ];
        
        $payment->update($updateData);
        
        // Notifier l'administrateur
        // Ici vous pourriez ajouter une notification par email ou SMS
        
        return redirect()->route('payments.validation.index')
            ->with('success', 'Paiement validé avec succès par le responsable. En attente de validation par l\'administrateur.');
    }

    /**
     * Valider un paiement par l'administrateur
     */
    public function validateByAdmin(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        try {
            // Mettre à jour le statut du paiement
            $updateData = [
                'admin_validated' => true,
                'admin_validated_by' => Auth::id(),
                'admin_validated_at' => now(),
                'admin_notes' => $request->admin_notes,
                'validation_status' => 'admin_validated',
                'is_confirmed' => true,
                'confirmed_by' => Auth::id(),
                'confirmed_at' => now(),
                'completed_at' => now(),
            ];
            
            $payment->update($updateData);
            
            // Marquer comme complètement validé
            $this->completeValidation($payment);
            
            // Générer la facture
            $invoicePath = $this->generateInvoice($payment);
            
            // Mettre à jour le paiement avec le chemin de la facture
            $payment->update([
                'receipt_url' => $invoicePath,
                'completed_at' => now()
            ]);
            
            // Réponse pour les requêtes AJAX
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Paiement validé avec succès par l\'administrateur.',
                    'invoice_url' => $invoicePath
                ]);
            }
            
            return redirect()->route('payments.validation.index')
                ->with('success', 'Paiement validé avec succès par l\'administrateur. Le processus de validation est terminé et la facture a été générée.')
                ->with('invoice_url', $invoicePath);
                
        } catch (\Exception $e) {
            // En cas d'erreur, retourner une réponse d'erreur
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la validation du paiement: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Une erreur est survenue lors de la validation du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Rejeter un paiement à n'importe quelle étape de la validation
     */
    public function reject(Request $request, Payment $payment)
    {
        $user = Auth::user();
        
        // Vérifier que l'utilisateur a le droit de rejeter ce paiement
        $canReject = false;
        
        if ($user->hasRole('admin')) {
            $canReject = true;
        } elseif ($user->hasRole('responsable') && $payment->validation_status === 'caissier_validated') {
            $canReject = true;
        } elseif ($user->hasRole('caissier') && $payment->validation_status === 'pending') {
            $canReject = true;
        }
        
        if (!$canReject) {
            abort(403, 'Vous n\'êtes pas autorisé à rejeter ce paiement à cette étape de validation.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        // Enregistrer qui a rejeté et pourquoi
        $rejectionNote = 'REJETÉ par ' . $user->name . ' (' . $user->role . ') le ' . now()->format('d/m/Y H:i') . ' : ' . $request->rejection_reason;
        
        // Ajouter la note de rejet aux notes existantes
        $existingNotes = $payment->notes ? $payment->notes . "\n\n" : '';
        
        // Marquer le paiement comme rejeté
        $payment->update([
            'validation_status' => 'rejected',
            'notes' => $existingNotes . $rejectionNote,
        ]);

        return redirect()->route('payments.validation.index')
            ->with('success', 'Paiement rejeté avec succès.');
    }

    /**
     * Historique des paiements validés
     */
    public function history()
    {
        $query = Payment::with(['client', 'site', 'lot', 'caissierValidatedBy', 'managerValidatedBy'])
            ->where('validation_status', 'completed')
            ->orderBy('admin_validated_at', 'desc');

        // Filtres par date
        if (request('date_from')) {
            $query->whereDate('manager_validated_at', '>=', request('date_from'));
        }
        if (request('date_to')) {
            $query->whereDate('manager_validated_at', '<=', request('date_to'));
        }

        $validatedPayments = $query->paginate(20);

        return view('payments.validation.history', compact('validatedPayments'));
    }

    /**
     * Vérifier si l'utilisateur peut valider un paiement
     */
    private function canValidatePayment(Payment $payment)
    {
        $user = Auth::user();
        $status = $payment->validation_status;
        
        // L'admin peut valider les paiements validés par le responsable
        if ($user->role === 'administrateur' && $status === 'responsable_validated') {
            return true;
        }
        
        // Le responsable peut valider les paiements validés par le caissier
        if ($user->role === 'responsable_commercial' && $status === 'caissier_validated') {
            return true;
        }
        
        // Le caissier peut valider les paiements en attente
        if ($user->role === 'caissier' && $status === 'pending') {
            return true;
        }
        
        // L'admin peut aussi valider directement les paiements en attente
        if ($user->role === 'administrateur' && $status === 'pending') {
            return true;
        }
        
        // L'admin peut aussi valider directement les paiements validés par le caissier
        if ($user->role === 'administrateur' && $status === 'caissier_validated') {
            return true;
        }
        
        return false;
    }

    /**
     * Finalise la validation complète d'un paiement
     */
    private function completeValidation(Payment $payment)
    {
        // Mettre à jour le statut à 'fully_validated' (au lieu de 'completed') pour correspondre au scope
        if ($payment->caissier_validated && $payment->responsable_validated && $payment->admin_validated) {
            $now = now();
            
            // Enregistrer l'activité
            activity()
                ->performedOn($payment)
                ->causedBy(auth()->user())
                ->withProperties([
                    'validated_by' => [
                        'caissier' => $payment->caissierValidatedBy ? $payment->caissierValidatedBy->name : null,
                        'responsable' => $payment->responsableValidatedBy ? $payment->responsableValidatedBy->name : null,
                        'admin' => $payment->adminValidatedBy ? $payment->adminValidatedBy->name : null,
                    ]
                ])
                ->log('Paiement validé avec succès');
                
            // Mettre à jour le statut du paiement
            $payment->update([
                'validation_status' => 'fully_validated', // Changé de 'completed' à 'fully_validated'
                'completed_at' => $now,
                'is_confirmed' => true,
                'confirmed_by' => auth()->id(),
                'confirmed_at' => $now
            ]);
            
            // **NOUVEAU**: Mettre à jour l'échéance si ce paiement est lié à une échéance
            if ($payment->payment_schedule_id) {
                $this->updatePaymentScheduleOnValidation($payment);
            }
        }
    }
    
    /**
     * Met à jour l'échéance quand son paiement est complètement validé
     */
    private function updatePaymentScheduleOnValidation(Payment $payment)
    {
        try {
            $schedule = \App\Models\PaymentSchedule::find($payment->payment_schedule_id);
            
            if ($schedule && !$schedule->is_paid) {
                // Marquer l'échéance comme payée
                $schedule->update([
                    'is_paid' => true,
                    'paid_date' => $payment->payment_date ?? now(),
                    'payment_method' => $payment->payment_method,
                    'notes' => 'Paiement validé après processus de validation 4 étapes',
                    'amount' => $payment->amount, // Utiliser le montant validé
                ]);
                
                // Mettre à jour le contrat lié
                $contract = $schedule->contract;
                if ($contract) {
                    $contract->paid_amount += $payment->amount;
                    $contract->remaining_amount = max(0, $contract->remaining_amount - $payment->amount);
                    $contract->save();
                }
                
                // Notifier le client (si la notification existe)
                try {
                    if ($contract && $contract->client && class_exists('\\App\\Notifications\\PaymentReceiptNotification')) {
                        $contract->client->notify(new \App\Notifications\PaymentReceiptNotification($schedule));
                    }
                } catch (\Exception $e) {
                    // Ignorer l'erreur de notification - ce n'est pas critique
                    \Log::warning('Erreur lors de l\'envoi de la notification de paiement: ' . $e->getMessage());
                }
                
                // Enregistrer l'activité
                activity()
                    ->performedOn($schedule)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'payment_id' => $payment->id,
                        'amount' => $payment->amount,
                        'validation_completed' => true
                    ])
                    ->log('\u00c9chéance marquée comme payée après validation complète du paiement');
            }
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour de l\'\u00e9chéance après validation: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'payment_schedule_id' => $payment->payment_schedule_id
            ]);
        }
    }
    
    /**
     * Générer une facture PDF pour un paiement
     */
    private function generateInvoice(Payment $payment)
    {
        try {
            // Charger les relations nécessaires
            $payment->load(['client', 'lot.site']);
            
            // Vérifier si le dossier de stockage existe, sinon le créer
            $directory = 'invoices/' . now()->format('Y/m');
            $storagePath = storage_path('app/public/' . $directory);
            
            if (!file_exists($storagePath)) {
                if (!mkdir($storagePath, 0755, true)) {
                    throw new \Exception("Impossible de créer le répertoire de stockage: " . $storagePath);
                }
            }
            
            // Vérifier les permissions du répertoire
            if (!is_writable($storagePath)) {
                throw new \Exception("Le répertoire de stockage n'est pas accessible en écriture: " . $storagePath);
            }
            
            // Générer un nom de fichier unique
            $filename = 'FACTURE-' . $payment->reference_number . '-' . now()->format('YmdHis') . '.pdf';
            $path = $directory . '/' . $filename;
            $fullPath = storage_path('app/public/' . $path);
            
            // Générer le PDF
            $pdf = \PDF::loadView('payments.invoice', [
                'payment' => $payment,
                'date' => now(),
            ]);
            
            // Sauvegarder le fichier
            file_put_contents($fullPath, $pdf->output());
            
            // Vérifier que le fichier a été créé
            if (!file_exists($fullPath)) {
                throw new \Exception("Échec de la création du fichier PDF: " . $fullPath);
            }
            
            return $path;
            
        } catch (\Exception $e) {
            // Log l'erreur
            \Log::error('Erreur lors de la génération de la facture: ' . $e->getMessage());
            
            // Créer un message d'erreur plus convivial
            throw new \Exception('Erreur lors de la génération de la facture. Veuillez vérifier les logs pour plus de détails.');
        }
    }
    
    /**
     * Statistiques des paiements
     */
    public function statistics()
    {
        $user = Auth::user();
        
        $stats = [
            'pending_count' => Payment::where('validation_status', 'pending')->count(),
            'caissier_validated_count' => Payment::where('validation_status', 'caissier_validated')->count(),
            'completed_today' => Payment::where('validation_status', 'completed')
                ->whereDate('admin_validated_at', today())
                ->count(),
            'total_validated_amount' => Payment::where('validation_status', 'completed')
                ->whereDate('admin_validated_at', today())
                ->sum('amount'),
            'recent_validations' => Payment::with(['client', 'caissierValidatedBy', 'managerValidatedBy', 'adminValidatedBy'])
                ->where('validation_status', 'completed')
                ->orderBy('admin_validated_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        // Filtrer selon le rôle de l'utilisateur
        if ($user->isManager()) {
            $stats['pending_count'] = Payment::where('validation_status', 'caissier_validated')
                ->whereHas('client', function ($q) {
                    $q->whereHas('assignedTo', function ($subQ) {
                        $subQ->where('role', 'commercial');
                    });
                })->count();
        }

        return view('payments.validation.statistics', compact('stats'));
    }
}
