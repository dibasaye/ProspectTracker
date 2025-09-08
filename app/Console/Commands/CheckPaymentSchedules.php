<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Contract;
use App\Models\PaymentSchedule;

class CheckPaymentSchedules extends Command
{
    protected $signature = 'schedules:check';
    protected $description = 'Vérifier les échéances de paiement et les contrats';

    public function handle()
    {
        // 1. Vérifier le nombre total d'échéances
        $totalSchedules = PaymentSchedule::count();
        $this->info("Nombre total d'échéances : " . $totalSchedules);

        // 2. Vérifier les contrats signés avec échéances
        $signedContracts = Contract::where('status', 'signe')
            ->whereHas('paymentSchedules')
            ->withCount('paymentSchedules')
            ->get();

        $this->info("\nContrats signés avec échéances : " . $signedContracts->count());
        
        foreach ($signedContracts as $contract) {
            $this->line("- Contrat #{$contract->id} : {$contract->payment_schedules_count} échéances");
        }

        // 3. Vérifier les échéances sans contrat signé
        $unsignedSchedules = PaymentSchedule::whereDoesntHave('contract', function($q) {
                $q->where('status', 'signe');
            })
            ->count();

        $this->info("\nÉchéances sans contrat signé : " . $unsignedSchedules);

        // 4. Vérifier les échéances par statut
        $paidSchedules = PaymentSchedule::where('is_paid', true)->count();
        $unpaidSchedules = PaymentSchedule::where('is_paid', false)->count();

        $this->info("\nStatut des échéances :");
        $this->line("- Payées : " . $paidSchedules);
        $this->line("- En attente : " . $unpaidSchedules);

        return 0;
    }
}
