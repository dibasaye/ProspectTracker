<?php

namespace App\Policies;

use App\Models\PaymentSchedule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PaymentSchedulePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isCaissier() || $user->isResponsableCommercial() || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PaymentSchedule $paymentSchedule): bool
    {
        // L'utilisateur peut voir l'échéance s'il est impliqué dans le processus de validation
        return $user->isCaissier() || $user->isResponsableCommercial() || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Seul un admin peut créer manuellement des échéances
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PaymentSchedule $paymentSchedule): bool
    {
        // Seul un admin peut modifier une échéance
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can validate the payment schedule.
     */
    public function validate(User $user, PaymentSchedule $paymentSchedule): bool
    {
        if ($user->isCaissier()) {
            return $paymentSchedule->canBeValidatedByCaissier();
        }

        if ($user->isResponsableCommercial()) {
            return $paymentSchedule->canBeValidatedByResponsable();
        }

        if ($user->isAdmin()) {
            return $paymentSchedule->canBeValidatedByAdmin();
        }

        return false;
    }

    /**
     * Determine whether the user can reject the payment schedule.
     */
    public function reject(User $user, PaymentSchedule $paymentSchedule): bool
    {
        // L'admin peut rejeter à n'importe quelle étape
        if ($user->isAdmin()) {
            return true;
        }

        // Le responsable peut rejeter si c'est à son tour de valider
        if ($user->isResponsableCommercial() && $paymentSchedule->canBeValidatedByResponsable()) {
            return true;
        }

        // Le caissier peut rejeter si c'est à son tour de valider
        if ($user->isCaissier() && $paymentSchedule->canBeValidatedByCaissier()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the history of validations.
     */
    public function viewHistory(User $user): bool
    {
        return $user->isCaissier() || $user->isResponsableCommercial() || $user->isAdmin();
    }

    /**
     * Determine whether the user can download the payment proof.
     */
    public function downloadProof(User $user, PaymentSchedule $paymentSchedule): bool
    {
        // L'admin peut tout voir
        if ($user->isAdmin()) {
            return true;
        }

        // Le responsable peut voir les justificatifs des échéances qu'il doit valider ou a validées
        if ($user->isResponsableCommercial()) {
            return $paymentSchedule->responsable_validated || $paymentSchedule->canBeValidatedByResponsable();
        }

        // Le caissier peut voir ses propres justificatifs
        if ($user->isCaissier() && $paymentSchedule->caissier_validated_by === $user->id) {
            return true;
        }

        return false;
    }
}
