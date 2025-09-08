<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illwarem\Bootstrap\Console\Kernel::class);

$app->boot();

use App\Models\PaymentSchedule;

$schedule = PaymentSchedule::where('is_paid', true)->first();

if ($schedule) {
    echo "ID: " . $schedule->id . "\n";
    echo "Montant: " . $schedule->amount . "\n";
    echo "Date d'échéance: " . $schedule->due_date . "\n";
    echo "Caissier validé: " . ($schedule->caissier_validated ? 'Oui' : 'Non') . "\n";
    echo "Responsable validé: " . ($schedule->responsable_validated ? 'Oui' : 'Non') . "\n";
    echo "Admin validé: " . ($schedule->admin_validated ? 'Oui' : 'Non') . "\n";
    echo "Statut de validation: " . ($schedule->validation_status ?? 'Non défini') . "\n";
} else {
    echo "Aucune échéance payée trouvée.\n";
}
