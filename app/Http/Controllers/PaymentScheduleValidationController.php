<?php

namespace App\Http\Controllers;

use App\Models\PaymentSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PaymentScheduleValidationController extends Controller
{
    /**
     * Constructeur du contrôleur<?php

namespace App\Http\Controllers;

use App\Models\PaymentSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PaymentScheduleValidationController extends Controller
{
    /**
     * Constructeur du contrôleur
     */
    public function __construct()
    {
        $this->middleware('auth');
        
        // Vérifier si l'utilisateur est connecté et a le bon rôle
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user) {
                return redirect()->route('login');
            }
            
            // Vérifier le rôle de l'utilisateur
            if (!in_array($user->role, ['caissier', 'responsable_commercial', 'administrateur'])) {
                abort(403, 'Accès non autorisé. Seuls les caissiers, responsables commerciaux et administrateurs peuvent valider les échéances.');
            }
            
            return $next($request);
        });
        
        // Autoriser la ressource avec la politique personnalisée
        $this->authorizeResource(PaymentSchedule::class, 'schedule', [
            'except' => ['validatePayment', 'reject']
        ]);
    }

    /**
     * Valider un paiement d'échéance
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function validatePayment(Request $request, $id)
    {
        try {
            $schedule = PaymentSchedule::findOrFail($id);
            $user = auth()->user();
            
            // Vérifier les autorisations
            if (!$this->canValidatePayment($schedule, $user)) {
                abort(403, 'Action non autorisée.');
            }
            
            // Valider la requête
            $validated = $request->validate([
                'amount_received' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
                'payment_proof' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            ]);
            
            // Traiter le fichier de preuve de paiement s'il est fourni
            $paymentProofPath = null;
            if ($request->hasFile('payment_proof')) {
                $paymentProofPath = $request->file('payment_proof')->store('payment-proofs', 'public');
            }
            
            // Mettre à jour l'échéance selon le rôle de l'utilisateur
            $now = now();
            
            if ($user->isCaissier()) {
                $schedule->update([
                    'caissier_validated' => true,
                    'caissier_validated_by' => $user->id,
                    'caissier_validated_at' => $now,
                    'caissier_notes' => $validated['notes'] ?? null,
                    'caissier_amount_received' => $validated['amount_received'],
                    'payment_proof_path' => $paymentProofPath ?? $schedule->payment_proof_path,
                    'validation_status' => 'caissier_validated',
                ]);
                
                $message = 'Paiement validé avec succès par le caissier.';
            } 
            elseif ($user->isResponsableCommercial()) {
                $schedule->update([
                    'responsable_validated' => true,
                    'responsable_validated_by' => $user->id,
                    'responsable_validated_at' => $now,
                    'responsable_notes' => $validated['notes'] ?? null,
                    'validation_status' => 'responsable_validated',
                ]);
                
                $message = 'Paiement validé avec succès par le responsable commercial.';
            } 
            elseif ($user->isAdmin()) {
                $schedule->update([
                    'admin_validated' => true,
                    'admin_validated_by' => $user->id,
                    'admin_validated_at' => $now,
                    'admin_notes' => $validated['notes'] ?? null,
                    'validation_status' => 'completed',
                    'is_paid' => true,
                    'paid_date' => $now,
                ]);
                
                $message = 'Paiement validé avec succès par l\'administrateur.';
                
                // Ici, vous pouvez ajouter des actions supplémentaires comme la génération d'une facture
                // ou la mise à jour du statut du contrat
            }
            
            // Enregistrer l'activité
            activity()
                ->causedBy($user)
                ->performedOn($schedule)
                ->withProperties([
                    'action' => 'payment_validation',
                    'status' => $schedule->validation_status,
                    'amount_received' => $validated['amount_received'] ?? null,
                ])
                ->log('Validation de paiement d\'échéance');
            
            return redirect()
                ->route('payment-schedules.validation.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la validation du paiement : ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la validation du paiement.');
        }
    }
    
    /**
     * Vérifier si l'utilisateur peut valider ce paiement
     */
    protected function canValidatePayment(PaymentSchedule $schedule, User $user): bool
    {
        if ($user->isCaissier()) {
            return !$schedule->caissier_validated && 
                   in_array($schedule->validation_status, [null, 'pending', 'caissier_pending']);
        }
        
        if ($user->isResponsableCommercial()) {
            return $schedule->caissier_validated && 
                   !$schedule->responsable_validated && 
                   in_array($schedule->validation_status, ['caissier_validated', 'responsable_pending']);
        }
        
        if ($user->isAdmin()) {
            return $schedule->caissier_validated && 
                   $schedule->responsable_validated && 
                   !$schedule->admin_validated && 
                   in_array($schedule->validation_status, ['responsable_validated', 'admin_pending']);
        }
        
        return false;
    }
    
    /**
     * Rejeter un paiement d'échéance
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PaymentSchedule  $schedule
     * @return \Illuminate\Http\Response
     */
    public function reject(Request $request, PaymentSchedule $schedule)
    {
        $this->authorize('reject', $schedule);
        
        // Validation des données
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);
        
        try {
            // Mettre à jour le statut de validation selon le rôle
            $now = now();
            $updateData = [
                'validation_status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'rejected_by' => auth()->id(),
                'rejected_at' => $now,
            ];
            
            // Réinitialiser les validations selon le rôle
            if (auth()->user()->isAdmin()) {
                $updateData['admin_validated'] = false;
                $updateData['admin_validated_by'] = null;
                $updateData['admin_validated_at'] = null;
                $updateData['admin_notes'] = $validated['rejection_reason'];
            } 
            
            if (auth()->user()->isResponsableCommercial() || auth()->user()->isAdmin()) {
                $updateData['responsable_validated'] = false;
                $updateData['responsable_validated_by'] = null;
                $updateData['responsable_validated_at'] = null;
                $updateData['responsable_notes'] = $validated['rejection_reason'];
            } 
            
            $updateData['caissier_validated'] = false;
            $updateData['caissier_validated_by'] = null;
            $updateData['caissier_validated_at'] = null;
            $updateData['caissier_notes'] = $validated['rejection_reason'];
            
            // Mettre à jour l'échéance
            $schedule->update($updateData);
            
            // Enregistrer l'activité
            activity()
                ->causedBy(auth()->user())
                ->performedOn($schedule)
                ->withProperties([
                    'action' => 'payment_rejection',
                    'reason' => $validated['rejection_reason'],
                    'rejected_by_role' => auth()->user()->role,
                ])
                ->log('Rejet du paiement d\'échéance');
            
            return redirect()
                ->route('payment-schedules.validation.index')
                ->with('success', 'Le paiement a été rejeté avec succès.');
                
        } catch (\Exception $e) {
            \Log::error('Erreur lors du rejet du paiement : ' . $e->getMessage());
            return back()
        $user = auth()->user();
        $reason = $request->validate(['rejection_reason' => 'required|string|max:1000'])['rejection_reason'];
        
        // Réinitialiser les validations selon l'étape actuelle
        $updateData = [
            'validation_status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
        ];

        if ($user->isAdmin()) {
            // Si c'est un admin qui rejette, tout est réinitialisé
            $updateData = array_merge($updateData, [
                'caissier_validated' => false,
                'responsable_validated' => false,
                'admin_validated' => false,
                'caissier_notes' => null,
                'responsable_notes' => null,
                'admin_notes' => null,
                'payment_proof_path' => null,
            ]);
        } elseif ($user->isResponsableCommercial()) {
            // Si c'est le responsable qui rejette, on revient à l'étape caissier
            $updateData = array_merge($updateData, [
                'caissier_validated' => false,
                'responsable_validated' => false,
                'caissier_notes' => null,
            ]);
        } elseif ($user->isCaissier()) {
            // Le caissier ne peut pas rejeter, il doit soumettre d'abord
            return back()->with('error', 'Vous n\'êtes pas autorisé à effectuer cette action.');
        }

        $schedule->update($updateData);

        return redirect()->route('payment-schedules.validation.index')
            ->with('success', 'L\'échéance a été rejetée et est revenue à l\'étape précédente.');
    }

    /**
     * Afficher la liste des échéances en attente de validation
     */
    public function index()
    {
        $user = auth()->user();
        
        // Requête de base avec les relations nécessaires
        $query = PaymentSchedule::with([
            'contract', 
            'contract.client', 
            'contract.site',
            'caissierValidatedBy',
            'responsableValidatedBy',
            'adminValidatedBy'
        ]);
        
        // Filtrer selon le rôle de l'utilisateur
        if ($user->isCaissier()) {
            // Pour le caissier : échéances payées mais non encore validées
            $query->where('is_paid', true)
                ->where('caissier_validated', false)
                ->where(function($q) {
                    $q->whereNull('validation_status')
                      ->orWhere('validation_status', 'pending')
                      ->orWhere('validation_status', 'caissier_pending');
                });
                
        } elseif ($user->isResponsableCommercial()) {
            // Pour le responsable : échéances validées par le caissier mais pas encore par le responsable
            $query->where('is_paid', true)
                ->where('caissier_validated', true)
                ->where('responsable_validated', false)
                ->where(function($q) {
                    $q->where('validation_status', 'caissier_validated')
                      ->orWhere('validation_status', 'responsable_pending');
                });
                
        } elseif ($user->isAdmin()) {
            // Pour l'admin : échéances validées par le caissier et le responsable mais pas encore par l'admin
            $query->where('is_paid', true)
                ->where('caissier_validated', true)
                ->where('responsable_validated', true)
                ->where('admin_validated', false)
                ->where(function($q) {
                    $q->where('validation_status', 'responsable_validated')
                      ->orWhere('validation_status', 'admin_pending');
                });
        }
        
        // Trier par date d'échéance croissante et ID
        $schedules = $query->orderBy('due_date')
                          ->orderBy('id')
                          ->get();
        
        // Statistiques pour le tableau de bord
        $stats = [
            'total_pending' => $schedules->count(),
            'total_amount' => $schedules->sum('amount'),
            'by_contract' => $schedules->groupBy('contract_id')->count(),
            'by_client' => $schedules->groupBy('contract.client_id')->count()
        ];

        return view('payment-schedules.validation.index', compact('schedules', 'stats'));
    }

    /**
     * Afficher les détails d'une échéance pour validation
     */
    /**
     * Afficher les détails d'une échéance pour validation
     */
    public function show($id)
    {
        try {
            // Récupérer l'échéance avec ses relations
            $schedule = PaymentSchedule::with([
                'contract', 
                'contract.client', 
                'contract.site',
                'caissierValidatedBy',
                'responsableValidatedBy',
                'adminValidatedBy',
                'contract.paymentSchedules' => function($query) {
                    $query->orderBy('due_date');
                }
            ])->findOrFail($id);
            
            // Vérifier les autorisations
            $this->authorize('view', $schedule);
            
            // Journalisation pour le débogage
            \Log::info('Affichage de l\'échéance', [
                'schedule_id' => $schedule->id,
                'is_paid' => $schedule->is_paid,
                'caissier_validated' => $schedule->caissier_validated,
                'responsable_validated' => $schedule->responsable_validated,
                'admin_validated' => $schedule->admin_validated,
                'user_role' => auth()->user()->roles->pluck('name')
            ]);
            
            // Vérifier les autorisations
            $this->authorize('view', $schedule);
            
            return view('payment-schedules.validation.show', compact('schedule'));
            
        } catch (\Exception $e) {
            // Journaliser l'erreur
            \Log::error('Erreur lors de l\'affichage de l\'échéance: ' . $e->getMessage(), [
                'schedule_id' => $id ?? 'inconnu',
                'user_id' => auth()->id(),
                'exception' => $e
            ]);
            
            // Rediriger avec un message d'erreur
            return redirect()->route('payment-schedules.validation.index')
                ->with('error', 'Impossible d\'afficher l\'échéance demandée.');
        }
    }

    /**
     * Valider une échéance (double validation)
     */
    public function validateSchedule(Request $request, PaymentSchedule $schedule)
    {
        $user = auth()->user();
        
        // Vérifier que l'utilisateur peut valider cette échéance
        if ($user->isCaissier() && $schedule->canBeValidatedByCaissier()) {
            return $this->validateByCaissier($request, $schedule);
        } elseif ($user->isResponsableCommercial() && $schedule->canBeValidatedByResponsable()) {
            return $this->validateByResponsable($request, $schedule);
        } elseif ($user->isAdmin() && $schedule->canBeValidatedByAdmin()) {
            return $this->validateByAdmin($request, $schedule);
        }
        
        return back()->with('error', 'Action non autorisée ou étape de validation incorrecte.');
    }

    /**
     * Validation par le caissier
     */
    private function validateByCaissier(Request $request, PaymentSchedule $schedule)
    {
        $validated = $request->validate([
            'caissier_notes' => 'nullable|string|max:1000',
            'caissier_amount_received' => 'required|numeric|min:0',
            'payment_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ], [
            'payment_proof.required' => 'Le justificatif de paiement est obligatoire',
            'payment_proof.mimes' => 'Le fichier doit être au format PDF, JPG ou PNG',
            'payment_proof.max' => 'Le fichier ne doit pas dépasser 2 Mo',
            'caissier_amount_received.required' => 'Le montant reçu est obligatoire',
            'caissier_amount_received.numeric' => 'Le montant doit être un nombre',
            'caissier_amount_received.min' => 'Le montant ne peut pas être négatif',
        ]);

        // Gérer le téléchargement du justificatif
        $file = $request->file('payment_proof');
        $paymentProofPath = $file->store('payment_schedules/proofs', 'public');
        
        // Mettre à jour l'échéance avec la validation caissier
        $updateData = [
            'caissier_validated' => true,
            'caissier_validated_by' => Auth::id(),
            'caissier_validated_at' => now(),
            'caissier_notes' => $request->caissier_notes,
            'caissier_amount_received' => $request->caissier_amount_received,
            'payment_proof_path' => $paymentProofPath,
            'validation_status' => 'caissier_validated',
        ];

        // Si le montant reçu est suffisant, marquer comme payé
        if ($request->caissier_amount_received >= $schedule->amount) {
            $updateData['is_paid'] = true;
            $updateData['paid_date'] = now();
        }

        $schedule->update($updateData);

        // Envoyer une notification au responsable pour validation
        // Notification::send(...);

        return redirect()->route('payment-schedules.validation.index')
            ->with('success', 'Échéance validée avec succès. En attente de validation par le responsable commercial.');
    }
    
    /**
     * Validation par le responsable commercial
     */
    private function validateByResponsable(Request $request, PaymentSchedule $schedule)
    {
        $validated = $request->validate([
            'responsable_notes' => 'nullable|string|max:1000',
        ]);

        $schedule->update([
            'responsable_validated' => true,
            'responsable_validated_by' => Auth::id(),
            'responsable_validated_at' => now(),
            'responsable_notes' => $request->responsable_notes,
            'validation_status' => 'responsable_validated',
        ]);

        // Envoyer une notification à l'administrateur pour validation finale
        // Notification::send(...);

        return redirect()->route('payment-schedules.validation.index')
            ->with('success', 'Validation du responsable enregistrée. En attente de validation par l\'administrateur.');
    }

    /**
     * Validation par l'administrateur
     */
    private function validateByAdmin(Request $request, PaymentSchedule $schedule)
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $schedule->update([
            'admin_validated' => true,
            'admin_validated_by' => Auth::id(),
            'admin_validated_at' => now(),
            'admin_notes' => $request->admin_notes,
            'validation_status' => 'validated',
            'is_paid' => true,
            'paid_date' => now(),
        ]);

        // Mettre à jour le statut du contrat si toutes les échéances sont payées
        $this->checkContractCompletion($schedule->contract);

        return redirect()->route('payment-schedules.validation.index')
            ->with('success', 'Échéance validée avec succès. Le paiement a été enregistré.');
    }

    /**
     * Vérifier si toutes les échéances du contrat sont payées et mettre à jour le statut
     */
    private function checkContractCompletion($contract)
    {
        $unpaidSchedules = $contract->paymentSchedules()->where('is_paid', false)->count();
        
        if ($unpaidSchedules === 0) {
            $contract->update(['status' => 'completed']);
        }
    }

    /**
     * Rejeter une échéance à n'importe quelle étape de la validation
     */
    public function reject(Request $request, PaymentSchedule $schedule)
    {
        $user = auth()->user();
        $reason = $request->validate(['rejection_reason' => 'required|string|max:1000'])['rejection_reason'];
        
        // Réinitialiser les validations selon l'étape actuelle
        $updateData = [
            'validation_status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
        ];

        if ($user->isAdmin()) {
            // Si c'est un admin qui rejette, tout est réinitialisé
            $updateData = array_merge($updateData, [
                'caissier_validated' => false,
                'responsable_validated' => false,
                'admin_validated' => false,
                'caissier_notes' => null,
                'responsable_notes' => null,
                'admin_notes' => null,
                'payment_proof_path' => null,
            ]);
        } elseif ($user->isResponsableCommercial()) {
            // Si c'est le responsable qui rejette, on revient à l'étape caissier
            $updateData = array_merge($updateData, [
                'caissier_validated' => false,
                'responsable_validated' => false,
                'caissier_notes' => null,
            ]);
        } elseif ($user->isCaissier()) {
            // Le caissier ne peut pas rejeter, il doit soumettre d'abord
            return back()->with('error', 'Vous n\'êtes pas autorisé à effectuer cette action.');
        }

        $schedule->update($updateData);

        return redirect()->route('payment-schedules.validation.index')
            ->with('success', 'L\'échéance a été rejetée et est revenue à l\'étape précédente.');
    }

    /**
     * Afficher l'historique des validations
     */
    public function history()
    {
        $schedules = PaymentSchedule::where('validation_status', 'validated')
            ->orWhere('validation_status', 'rejected')
            ->with(['contract', 'contract.client', 'caissierValidatedBy', 'responsableValidatedBy', 'adminValidatedBy'])
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('payment-schedules.validation.history', compact('schedules'));
    }

    /**
     * Télécharger le justificatif de paiement
     */
    public function downloadProof(PaymentSchedule $schedule)
    {
        if (!$schedule->payment_proof_path || !Storage::disk('public')->exists($schedule->payment_proof_path)) {
            abort(404, 'Fichier non trouvé');
        }

        return Storage::disk('public')->download($schedule->payment_proof_path, 'justificatif-paiement-' . $schedule->id . '.' . pathinfo($schedule->payment_proof_path, PATHINFO_EXTENSION));
    }
}

     */
    public function __construct()
    {
        $this->middleware('auth');
        
        // Vérifier si l'utilisateur est connecté et a le bon rôle
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user) {
                return redirect()->route('login');
            }
            
            // Vérifier le rôle de l'utilisateur
            if (!in_array($user->role, ['caissier', 'responsable_commercial', 'administrateur'])) {
                abort(403, 'Accès non autorisé. Seuls les caissiers, responsables commerciaux et administrateurs peuvent valider les échéances.');
            }
            
            return $next($request);
        });
        
        // Autoriser la ressource avec la politique personnalisée
        $this->authorizeResource(PaymentSchedule::class, 'schedule', [
            'except' => ['validatePayment', 'reject']
        ]);
    }

    /**
     * Valider un paiement d'échéance
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function validatePayment(Request $request, $id)
    {
        try {
            $schedule = PaymentSchedule::findOrFail($id);
            $user = auth()->user();
            
            // Vérifier les autorisations
            if (!$this->canValidatePayment($schedule, $user)) {
                abort(403, 'Action non autorisée.');
            }
            
            // Valider la requête
            $validated = $request->validate([
                'amount_received' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
                'payment_proof' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            ]);
            
            // Traiter le fichier de preuve de paiement s'il est fourni
            $paymentProofPath = null;
            if ($request->hasFile('payment_proof')) {
                $paymentProofPath = $request->file('payment_proof')->store('payment-proofs', 'public');
            }
            
            // Mettre à jour l'échéance selon le rôle de l'utilisateur
            $now = now();
            
            if ($user->isCaissier()) {
                $schedule->update([
                    'caissier_validated' => true,
                    'caissier_validated_by' => $user->id,
                    'caissier_validated_at' => $now,
                    'caissier_notes' => $validated['notes'] ?? null,
                    'caissier_amount_received' => $validated['amount_received'],
                    'payment_proof_path' => $paymentProofPath ?? $schedule->payment_proof_path,
                    'validation_status' => 'caissier_validated',
                ]);
                
                $message = 'Paiement validé avec succès par le caissier.';
            } 
            elseif ($user->isResponsableCommercial()) {
                $schedule->update([
                    'responsable_validated' => true,
                    'responsable_validated_by' => $user->id,
                    'responsable_validated_at' => $now,
                    'responsable_notes' => $validated['notes'] ?? null,
                    'validation_status' => 'responsable_validated',
                ]);
                
                $message = 'Paiement validé avec succès par le responsable commercial.';
            } 
            elseif ($user->isAdmin()) {
                $schedule->update([
                    'admin_validated' => true,
                    'admin_validated_by' => $user->id,
                    'admin_validated_at' => $now,
                    'admin_notes' => $validated['notes'] ?? null,
                    'validation_status' => 'completed',
                    'is_paid' => true,
                    'paid_date' => $now,
                ]);
                
                $message = 'Paiement validé avec succès par l\'administrateur.';
                
                // Ici, vous pouvez ajouter des actions supplémentaires comme la génération d'une facture
                // ou la mise à jour du statut du contrat
            }
            
            // Enregistrer l'activité
            activity()
                ->causedBy($user)
                ->performedOn($schedule)
                ->withProperties([
                    'action' => 'payment_validation',
                    'status' => $schedule->validation_status,
                    'amount_received' => $validated['amount_received'] ?? null,
                ])
                ->log('Validation de paiement d\'échéance');
            
            return redirect()
                ->route('payment-schedules.validation.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la validation du paiement : ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la validation du paiement.');
        }
    }
    
    /**
     * Vérifier si l'utilisateur peut valider ce paiement
     */
    protected function canValidatePayment(PaymentSchedule $schedule, User $user): bool
    {
        if ($user->isCaissier()) {
            return !$schedule->caissier_validated && 
                   in_array($schedule->validation_status, [null, 'pending', 'caissier_pending']);
        }
        
        if ($user->isResponsableCommercial()) {
            return $schedule->caissier_validated && 
                   !$schedule->responsable_validated && 
                   in_array($schedule->validation_status, ['caissier_validated', 'responsable_pending']);
        }
        
        if ($user->isAdmin()) {
            return $schedule->caissier_validated && 
                   $schedule->responsable_validated && 
                   !$schedule->admin_validated && 
                   in_array($schedule->validation_status, ['responsable_validated', 'admin_pending']);
        }
        
        return false;
    }
    
    /**
     * Rejeter un paiement d'échéance
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PaymentSchedule  $schedule
     * @return \Illuminate\Http\Response
     */
    // public function reject(Request $request, PaymentSchedule $schedule)
    // {
    //     $this->authorize('reject', $schedule);
        
    //     // Validation des données
    //     $validated = $request->validate([
    //         'rejection_reason' => 'required|string|max:1000',
    //     ]);
        
    //     try {
    //         // Mettre à jour le statut de validation selon le rôle
    //         $now = now();
    //         $updateData = [
    //             'validation_status' => 'rejected',
    //             'rejection_reason' => $validated['rejection_reason'],
    //             'rejected_by' => auth()->id(),
    //             'rejected_at' => $now,
    //         ];
            
    //         // Réinitialiser les validations selon le rôle
    //         if (auth()->user()->isAdmin()) {
    //             $updateData['admin_validated'] = false;
    //             $updateData['admin_validated_by'] = null;
    //             $updateData['admin_validated_at'] = null;
    //             $updateData['admin_notes'] = $validated['rejection_reason'];
    //         } 
            
    //         if (auth()->user()->isResponsableCommercial() || auth()->user()->isAdmin()) {
    //             $updateData['responsable_validated'] = false;
    //             $updateData['responsable_validated_by'] = null;
    //             $updateData['responsable_validated_at'] = null;
    //             $updateData['responsable_notes'] = $validated['rejection_reason'];
    //         } 
            
    //         $updateData['caissier_validated'] = false;
    //         $updateData['caissier_validated_by'] = null;
    //         $updateData['caissier_validated_at'] = null;
    //         $updateData['caissier_notes'] = $validated['rejection_reason'];
            
    //         // Mettre à jour l'échéance
    //         $schedule->update($updateData);
            
    //         // Enregistrer l'activité
    //         activity()
    //             ->causedBy(auth()->user())
    //             ->performedOn($schedule)
    //             ->withProperties([
    //                 'action' => 'payment_rejection',
    //                 'reason' => $validated['rejection_reason'],
    //                 'rejected_by_role' => auth()->user()->role,
    //             ])
    //             ->log('Rejet du paiement d\'échéance');
            
    //         return redirect()
    //             ->route('payment-schedules.validation.index')
    //             ->with('success', 'Le paiement a été rejeté avec succès.');
                
    //     } catch (\Exception $e) {
    //         \Log::error('Erreur lors du rejet du paiement : ' . $e->getMessage());
    //         return back()
    //     $user = auth()->user();
    //     $reason = $request->validate(['rejection_reason' => 'required|string|max:1000'])['rejection_reason'];
        
    //     // Réinitialiser les validations selon l'étape actuelle
    //     $updateData = [
    //         'validation_status' => 'rejected',
    //         'rejection_reason' => $reason,
    //         'rejected_by' => $user->id,
    //         'rejected_at' => now(),
    //     ];

    //     if ($user->isAdmin()) {
    //         // Si c'est un admin qui rejette, tout est réinitialisé
    //         $updateData = array_merge($updateData, [
    //             'caissier_validated' => false,
    //             'responsable_validated' => false,
    //             'admin_validated' => false,
    //             'caissier_notes' => null,
    //             'responsable_notes' => null,
    //             'admin_notes' => null,
    //             'payment_proof_path' => null,
    //         ]);
    //     } elseif ($user->isResponsableCommercial()) {
    //         // Si c'est le responsable qui rejette, on revient à l'étape caissier
    //         $updateData = array_merge($updateData, [
    //             'caissier_validated' => false,
    //             'responsable_validated' => false,
    //             'caissier_notes' => null,
    //         ]);
    //     } elseif ($user->isCaissier()) {
    //         // Le caissier ne peut pas rejeter, il doit soumettre d'abord
    //         return back()->with('error', 'Vous n\'êtes pas autorisé à effectuer cette action.');
    //     }

    //     $schedule->update($updateData);

    //     return redirect()->route('payment-schedules.validation.index')
    //         ->with('success', 'L\'échéance a été rejetée et est revenue à l\'étape précédente.');
    // }

    // /**
    //  * Afficher la liste des échéances en attente de validation
    //  */
    // public function index()
    // {
    //     $user = auth()->user();
        
    //     // Requête de base avec les relations nécessaires
    //     $query = PaymentSchedule::with([
    //         'contract', 
    //         'contract.client', 
    //         'contract.site',
    //         'caissierValidatedBy',
    //         'responsableValidatedBy',
    //         'adminValidatedBy'
    //     ]);
        
    //     // Filtrer selon le rôle de l'utilisateur
    //     if ($user->isCaissier()) {
    //         // Pour le caissier : échéances payées mais non encore validées
    //         $query->where('is_paid', true)
    //             ->where('caissier_validated', false)
    //             ->where(function($q) {
    //                 $q->whereNull('validation_status')
    //                   ->orWhere('validation_status', 'pending')
    //                   ->orWhere('validation_status', 'caissier_pending');
    //             });
                
    //     } elseif ($user->isResponsableCommercial()) {
    //         // Pour le responsable : échéances validées par le caissier mais pas encore par le responsable
    //         $query->where('is_paid', true)
    //             ->where('caissier_validated', true)
    //             ->where('responsable_validated', false)
    //             ->where(function($q) {
    //                 $q->where('validation_status', 'caissier_validated')
    //                   ->orWhere('validation_status', 'responsable_pending');
    //             });
                
    //     } elseif ($user->isAdmin()) {
    //         // Pour l'admin : échéances validées par le caissier et le responsable mais pas encore par l'admin
    //         $query->where('is_paid', true)
    //             ->where('caissier_validated', true)
    //             ->where('responsable_validated', true)
    //             ->where('admin_validated', false)
    //             ->where(function($q) {
    //                 $q->where('validation_status', 'responsable_validated')
    //                   ->orWhere('validation_status', 'admin_pending');
    //             });
    //     }
        
    //     // Trier par date d'échéance croissante et ID
    //     $schedules = $query->orderBy('due_date')
    //                       ->orderBy('id')
    //                       ->get();
        
    //     // Statistiques pour le tableau de bord
    //     $stats = [
    //         'total_pending' => $schedules->count(),
    //         'total_amount' => $schedules->sum('amount'),
    //         'by_contract' => $schedules->groupBy('contract_id')->count(),
    //         'by_client' => $schedules->groupBy('contract.client_id')->count()
    //     ];

    //     return view('payment-schedules.validation.index', compact('schedules', 'stats'));
    // }

    /**
     * Afficher les détails d'une échéance pour validation
     */
    /**
     * Afficher les détails d'une échéance pour validation
     */
    public function show($id)
    {
        try {
            // Récupérer l'échéance avec ses relations
            $schedule = PaymentSchedule::with([
                'contract', 
                'contract.client', 
                'contract.site',
                'caissierValidatedBy',
                'responsableValidatedBy',
                'adminValidatedBy',
                'contract.paymentSchedules' => function($query) {
                    $query->orderBy('due_date');
                }
            ])->findOrFail($id);
            
            // Vérifier les autorisations
            $this->authorize('view', $schedule);
            
            // Journalisation pour le débogage
            \Log::info('Affichage de l\'échéance', [
                'schedule_id' => $schedule->id,
                'is_paid' => $schedule->is_paid,
                'caissier_validated' => $schedule->caissier_validated,
                'responsable_validated' => $schedule->responsable_validated,
                'admin_validated' => $schedule->admin_validated,
                'user_role' => auth()->user()->roles->pluck('name')
            ]);
            
            // Vérifier les autorisations
            $this->authorize('view', $schedule);
            
            return view('payment-schedules.validation.show', compact('schedule'));
            
        } catch (\Exception $e) {
            // Journaliser l'erreur
            \Log::error('Erreur lors de l\'affichage de l\'échéance: ' . $e->getMessage(), [
                'schedule_id' => $id ?? 'inconnu',
                'user_id' => auth()->id(),
                'exception' => $e
            ]);
            
            // Rediriger avec un message d'erreur
            return redirect()->route('payment-schedules.validation.index')
                ->with('error', 'Impossible d\'afficher l\'échéance demandée.');
        }
    }

    /**
     * Valider une échéance (double validation)
     */
    public function validateSchedule(Request $request, PaymentSchedule $schedule)
    {
        $user = auth()->user();
        
        // Vérifier que l'utilisateur peut valider cette échéance
        if ($user->isCaissier() && $schedule->canBeValidatedByCaissier()) {
            return $this->validateByCaissier($request, $schedule);
        } elseif ($user->isResponsableCommercial() && $schedule->canBeValidatedByResponsable()) {
            return $this->validateByResponsable($request, $schedule);
        } elseif ($user->isAdmin() && $schedule->canBeValidatedByAdmin()) {
            return $this->validateByAdmin($request, $schedule);
        }
        
        return back()->with('error', 'Action non autorisée ou étape de validation incorrecte.');
    }

    /**
     * Validation par le caissier
     */
    private function validateByCaissier(Request $request, PaymentSchedule $schedule)
    {
        $validated = $request->validate([
            'caissier_notes' => 'nullable|string|max:1000',
            'caissier_amount_received' => 'required|numeric|min:0',
            'payment_proof' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ], [
            'payment_proof.required' => 'Le justificatif de paiement est obligatoire',
            'payment_proof.mimes' => 'Le fichier doit être au format PDF, JPG ou PNG',
            'payment_proof.max' => 'Le fichier ne doit pas dépasser 2 Mo',
            'caissier_amount_received.required' => 'Le montant reçu est obligatoire',
            'caissier_amount_received.numeric' => 'Le montant doit être un nombre',
            'caissier_amount_received.min' => 'Le montant ne peut pas être négatif',
        ]);

        // Gérer le téléchargement du justificatif
        $file = $request->file('payment_proof');
        $paymentProofPath = $file->store('payment_schedules/proofs', 'public');
        
        // Mettre à jour l'échéance avec la validation caissier
        $updateData = [
            'caissier_validated' => true,
            'caissier_validated_by' => Auth::id(),
            'caissier_validated_at' => now(),
            'caissier_notes' => $request->caissier_notes,
            'caissier_amount_received' => $request->caissier_amount_received,
            'payment_proof_path' => $paymentProofPath,
            'validation_status' => 'caissier_validated',
        ];

        // Si le montant reçu est suffisant, marquer comme payé
        if ($request->caissier_amount_received >= $schedule->amount) {
            $updateData['is_paid'] = true;
            $updateData['paid_date'] = now();
        }

        $schedule->update($updateData);

        // Envoyer une notification au responsable pour validation
        // Notification::send(...);

        return redirect()->route('payment-schedules.validation.index')
            ->with('success', 'Échéance validée avec succès. En attente de validation par le responsable commercial.');
    }
    
    /**
     * Validation par le responsable commercial
     */
    private function validateByResponsable(Request $request, PaymentSchedule $schedule)
    {
        $validated = $request->validate([
            'responsable_notes' => 'nullable|string|max:1000',
        ]);

        $schedule->update([
            'responsable_validated' => true,
            'responsable_validated_by' => Auth::id(),
            'responsable_validated_at' => now(),
            'responsable_notes' => $request->responsable_notes,
            'validation_status' => 'responsable_validated',
        ]);

        // Envoyer une notification à l'administrateur pour validation finale
        // Notification::send(...);

        return redirect()->route('payment-schedules.validation.index')
            ->with('success', 'Validation du responsable enregistrée. En attente de validation par l\'administrateur.');
    }

    /**
     * Validation par l'administrateur
     */
    private function validateByAdmin(Request $request, PaymentSchedule $schedule)
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $schedule->update([
            'admin_validated' => true,
            'admin_validated_by' => Auth::id(),
            'admin_validated_at' => now(),
            'admin_notes' => $request->admin_notes,
            'validation_status' => 'validated',
            'is_paid' => true,
            'paid_date' => now(),
        ]);

        // Mettre à jour le statut du contrat si toutes les échéances sont payées
        $this->checkContractCompletion($schedule->contract);

        return redirect()->route('payment-schedules.validation.index')
            ->with('success', 'Échéance validée avec succès. Le paiement a été enregistré.');
    }

    /**
     * Vérifier si toutes les échéances du contrat sont payées et mettre à jour le statut
     */
    private function checkContractCompletion($contract)
    {
        $unpaidSchedules = $contract->paymentSchedules()->where('is_paid', false)->count();
        
        if ($unpaidSchedules === 0) {
            $contract->update(['status' => 'completed']);
        }
    }

    /**
     * Rejeter une échéance à n'importe quelle étape de la validation
     */
    public function reject(Request $request, PaymentSchedule $schedule)
    {
        $user = auth()->user();
        $reason = $request->validate(['rejection_reason' => 'required|string|max:1000'])['rejection_reason'];
        
        // Réinitialiser les validations selon l'étape actuelle
        $updateData = [
            'validation_status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
        ];

        if ($user->isAdmin()) {
            // Si c'est un admin qui rejette, tout est réinitialisé
            $updateData = array_merge($updateData, [
                'caissier_validated' => false,
                'responsable_validated' => false,
                'admin_validated' => false,
                'caissier_notes' => null,
                'responsable_notes' => null,
                'admin_notes' => null,
                'payment_proof_path' => null,
            ]);
        } elseif ($user->isResponsableCommercial()) {
            // Si c'est le responsable qui rejette, on revient à l'étape caissier
            $updateData = array_merge($updateData, [
                'caissier_validated' => false,
                'responsable_validated' => false,
                'caissier_notes' => null,
            ]);
        } elseif ($user->isCaissier()) {
            // Le caissier ne peut pas rejeter, il doit soumettre d'abord
            return back()->with('error', 'Vous n\'êtes pas autorisé à effectuer cette action.');
        }

        $schedule->update($updateData);

        return redirect()->route('payment-schedules.validation.index')
            ->with('success', 'L\'échéance a été rejetée et est revenue à l\'étape précédente.');
    }

    /**
     * Afficher l'historique des validations
     */
    public function history()
    {
        $schedules = PaymentSchedule::where('validation_status', 'validated')
            ->orWhere('validation_status', 'rejected')
            ->with(['contract', 'contract.client', 'caissierValidatedBy', 'responsableValidatedBy', 'adminValidatedBy'])
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('payment-schedules.validation.history', compact('schedules'));
    }

    /**
     * Télécharger le justificatif de paiement
     */
    public function downloadProof(PaymentSchedule $schedule)
    {
        if (!$schedule->payment_proof_path || !Storage::disk('public')->exists($schedule->payment_proof_path)) {
            abort(404, 'Fichier non trouvé');
        }

        return Storage::disk('public')->download($schedule->payment_proof_path, 'justificatif-paiement-' . $schedule->id . '.' . pathinfo($schedule->payment_proof_path, PATHINFO_EXTENSION));
    }
}
