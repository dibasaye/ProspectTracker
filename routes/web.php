<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProspectController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CashTransactionController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\PaymentScheduleController;
use App\Http\Controllers\PaymentValidationController;
use App\Http\Controllers\CommercialPerformanceController;
use App\Http\Controllers\LotManagementController;
use App\Http\Controllers\PaymentReceiptController;
use App\Http\Controllers\ReportController;


Route::get('/', function () {
    return view('welcome');
});

// Route temporaire pour déboguer les justificatifs de paiement
Route::get('/debug/payment-proofs', function () {
    $payments = \App\Models\Payment::whereNotNull('payment_proof_path')->get();
    
    if ($payments->isEmpty()) {
        return "Aucun paiement avec justificatif trouvé dans la base de données.";
    }
    
    $output = "<h1>Paiements avec justificatifs</h1><ul>";
    
    foreach ($payments as $payment) {
        $fullPath = storage_path('app/public/' . $payment->payment_proof_path);
        $fileExists = file_exists($fullPath) ? "Oui" : "Non";
        $url = asset('storage/' . $payment->payment_proof_path);
        
        $output .= sprintf(
            "<li>ID: %d - Chemin: %s - Existe: %s - <a href='%s' target='_blank'>Voir</a></li>",
            $payment->id,
            $payment->payment_proof_path,
            $fileExists,
            $url
        );
    }
    
    $output .= "</ul>";
    
    // Vérifier les fichiers dans le dossier de stockage
    $files = \Illuminate\Support\Facades\Storage::disk('public')->files('payment_proofs');
    $output .= "<h2>Fichiers dans le dossier de stockage</h2><ul>";
    
    foreach ($files as $file) {
        $output .= "<li>" . $file . "</li>";
    }
    
    $output .= "</ul>";
    
    return $output;
});

Route::middleware(['auth'])->group(function () {
    Route::resource('reports', ReportController::class)->only(['index']);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Route pour les statistiques de performance des commerciaux
    Route::get('/commercial-performance', [DashboardController::class, 'commercialPerformance'])->name('commercial.performance');
    Route::get('/commercial-performance/export', [DashboardController::class, 'exportPerformance'])->name('commercial.performance.export');
    
    // Prospect management routes
    Route::resource('prospects', ProspectController::class);

     Route::get('/prospects/{prospect}/assign', [ProspectController::class, 'showAssignForm'])->name('prospects.assign.form');

    // Route pour enregistrer l'assignation (POST)
    Route::post('/prospects/{prospect}/assign', [ProspectController::class, 'assign'])->name('prospects.assign');
Route::get('/prospects/{prospect}/followup', [ProspectController::class, 'followupForm'])->name('prospects.followup.form');
Route::post('/prospects/{prospect}/followup', [ProspectController::class, 'storeFollowup'])->name('prospects.followup.store');
Route::post('/prospects/assign-bulk', [ProspectController::class, 'assignBulk'])
    ->name('prospects.assign.bulk');
Route::post('/prospects/import', [ProspectController::class, 'import'])->name('prospects.import');
Route::get('/prospects/template/download', [ProspectController::class, 'downloadTemplate'])->name('prospects.template.download');
Route::post('/prospects/store-bulk', [ProspectController::class, 'storeBulk'])->name('prospects.store-bulk');

    // Reservation management routes
     Route::get('/create/{prospect}', [ReservationController::class, 'create'])->name('reservations.create');
    Route::post('/store/{prospect}', [ReservationController::class, 'store'])->name('reservations.store');
    Route::post('/reserve-by-number/{prospect}', [ReservationController::class, 'reserveByNumber'])->name('reservations.reserve-by-number');

    
    // Site management routes
    Route::resource('sites', SiteController::class);
    Route::get('/sites/{site}/lots', [SiteController::class, 'lots'])->name('sites.lots');
    
    // Routes pour gestion en masse des lots
    Route::get('/sites/{site}/lots/bulk-create', [LotManagementController::class, 'bulkCreate'])->name('sites.lots.bulk-create');
    Route::post('/sites/{site}/lots/bulk-store', [LotManagementController::class, 'bulkStore'])->name('sites.lots.bulk-store');
    Route::post('/sites/{site}/lots/bulk-update-position', [LotManagementController::class, 'bulkUpdatePosition'])->name('sites.lots.bulk-update-position');

    // Payment management routes

    Route::get('prospects/{prospect}/payments/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('prospects/{prospect}/payments', [PaymentController::class, 'store'])->name('payments.store');
    

    Route::get('payments/{payment}/invoice', [PaymentController::class, 'invoice'])->name('payments.invoice');
    
    // Route pour que les commerciaux voient leurs paiements
    Route::get('my-payments', [PaymentController::class, 'myPayments'])->name('payments.my');
    
    // Route directe pour valider un paiement
    Route::post('payments/{payment}/validate', [PaymentValidationController::class, 'validatePayment'])->name('payments.validate');

    // Routes de validation des paiements
    Route::prefix('payments/validation')->name('payments.validation.')->group(function() {
        Route::get('/', [PaymentValidationController::class, 'index'])->name('index');
        Route::get('/history', [PaymentValidationController::class, 'history'])->name('history');
        Route::get('/statistics', [PaymentValidationController::class, 'statistics'])->name('statistics');
        Route::get('/{payment}', [PaymentValidationController::class, 'show'])->name('show');
        Route::post('/{payment}/validate', [PaymentValidationController::class, 'validatePayment'])->name('validate');
        Route::post('/{payment}/reject', [PaymentValidationController::class, 'reject'])->name('reject');
        // Route pour la validation par l'administrateur
        Route::post('/{payment}/admin', [PaymentValidationController::class, 'validateByAdmin'])->name('admin.validate');
    });

    Route::get('prospects/{prospect}/reservation-payment', [PaymentController::class, 'createReservationPayment'])->name('payments.reservation.create');
    Route::post('prospects/{prospect}/reservation-payment', [PaymentController::class, 'storeReservationPayment'])->name('payments.reservation.store');
    Route::post('/prospects/store-multiple', [ProspectController::class, 'storeMultiple'])->name('prospects.store-multiple');
    Route::post('/prospects/{prospect}/change-status', [ProspectController::class, 'changeStatus'])->name('prospects.change-status');
    
    Route::get('/prospects/conversion-stats', [ProspectController::class, 'conversionStats'])
    ->name('prospects.conversion-stats');
    
    // Bordereau management routes
    Route::prefix('receipts')->name('receipts.')->group(function() {
        Route::get('/', [PaymentReceiptController::class, 'index'])->name('index');
        Route::get('/create-daily', [PaymentReceiptController::class, 'createDaily'])->name('create-daily');
        Route::post('/store-daily', [PaymentReceiptController::class, 'storeDaily'])->name('store-daily');
        Route::get('/create-period', [PaymentReceiptController::class, 'createPeriod'])->name('create-period');
        Route::post('/store-period', [PaymentReceiptController::class, 'storePeriod'])->name('store-period');
        Route::get('/{receipt}', [PaymentReceiptController::class, 'show'])->name('show');
        Route::post('/{receipt}/finalize', [PaymentReceiptController::class, 'finalize'])->name('finalize');
        Route::get('/{receipt}/pdf', [PaymentReceiptController::class, 'generatePdf'])->name('pdf');
        Route::delete('/{receipt}', [PaymentReceiptController::class, 'destroy'])->name('destroy');
        Route::get('/api/payments-by-period', [PaymentReceiptController::class, 'getPaymentsByPeriod'])->name('api.payments-by-period');
    });
    
    // Contract management routes

    Route::get('prospects/{prospect}/generate-contract', [ContractController::class, 'generateFromReservation'])->name('contracts.generate');
    Route::get('contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::get('contracts/{contract}/export-pdf', [ContractController::class, 'exportPdf'])->name('contracts.pdf');
    Route::post('contracts/{contract}/upload-signed', [ContractController::class, 'uploadSignedCopy'])->name('contracts.uploadSigned');
    Route::post('contracts/{contract}/sign', [ContractController::class, 'signContract'])->name('contracts.sign');
    Route::get('contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/export', [ContractController::class, 'export'])->name('contracts.export');
    Route::get('/contracts/create/{prospect}', [ContractController::class, 'create'])->name('contracts.create');
    Route::post('/contracts', [ContractController::class, 'store'])->name('contracts.store');

    // Payment Schedule management routes
    Route::get('/payment-schedules', [PaymentScheduleController::class, 'index'])->name('payment-schedules.index');
    Route::put('/payment-schedules/{schedule}/pay', [PaymentScheduleController::class, 'pay'])->name('schedules.pay');
    Route::get('/payment-schedules/{schedule}/receipt', [PaymentScheduleController::class, 'downloadReceipt'])->name('schedules.receipt');
    Route::get('/payment-schedules/export', [PaymentScheduleController::class, 'export'])->name('payment-schedules.export');
    Route::get('/clients/{client}/payment-schedules', [PaymentScheduleController::class, 'clientSchedules'])->name('clients.payment-schedules');
    
    // Nouvelles routes pour les versements et l'historique
    Route::post('/clients/{client}/payment', [PaymentScheduleController::class, 'makeClientPayment'])->name('clients.payment');
    Route::get('/clients/{client}/payment-history', [PaymentScheduleController::class, 'getClientPaymentHistory'])->name('clients.payment-history');






    Route::prefix('sites/{site}')->group(function() {
    Route::get('/lots/create', [LotController::class, 'create'])->name('sites.lots.create');
    Route::post('/lots', [LotController::class, 'store'])->name('sites.lots.store');
    // Route::get('/lots', [LotController::class, 'index'])->name('sites.lots.index');
    Route::post('/lots/{lot}/release', [LotController::class, 'release'])->name('lots.release');
    Route::post('/lots/{lot}/reserve', [LotController::class, 'reserve'])->name('sites.lots.reserve');  // CORRECT
    Route::post('/lots/reserve-by-number', [LotController::class, 'reserveByNumber'])->name('sites.lots.reserve-by-number');

});



Route::get('/notifications/read/{id}', function ($id) {
    $notification = auth()->user()->notifications()->findOrFail($id);
    $notification->markAsRead();
    
    // Rediriger vers le prospect
    return redirect()->route('prospects.show', $notification->data['prospect_id']);
})->name('notifications.read');




// Supprime ou commente la ligne suivante :a
// Route::post('/sites/{site}/lots', [LotController::class, 'store'])->name('lots.store');


Route::get('/commercial/performance', [CommercialPerformanceController::class, 'performance']);
Route::get('/commercial/performance/export', [CommercialPerformanceController::class, 'export'])->name('commercial.performance.export');



Route::post('/sites/{site}/lots', [LotController::class, 'store'])->name('lots.store');






    
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin routes

    // Liste des utilisateurs
    Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');

    // Modifier le rôle
    Route::post('/admin/users/{user}/role', [UserController::class, 'updateRole'])->name('admin.users.updateRole');

    // Activer/Désactiver un utilisateur
    Route::post('/admin/users/{user}/toggle', [UserController::class, 'toggleActive'])->name('admin.users.toggle');

    // Supprimer un utilisateur (optionnel)
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

    // Créer un nouvel utilisateur
    Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');

    // Routes pour les contrats
    Route::resource('contracts', ContractController::class)->except(['edit', 'update']);
    Route::get('contracts/{contract}/preview', [ContractController::class, 'preview'])->name('contracts.preview');
    Route::get('contracts/{contract}/edit-content', [ContractController::class, 'editContent'])->name('contracts.edit-content');
    Route::put('contracts/{contract}/update-content', [ContractController::class, 'updateContent'])->name('contracts.update-content');
    Route::get('contracts/{contract}/pdf', [ContractController::class, 'exportPdf'])->name('contracts.export.pdf');
    Route::get('contracts/{contract}/word', [ContractController::class, 'exportWord'])->name('contracts.export.word');
    Route::post('contracts/{contract}/upload-signed', [ContractController::class, 'uploadSignedCopy'])->name('contracts.upload-signed');
    Route::post('contracts/{contract}/sign', [ContractController::class, 'signContract'])->name('contracts.sign');
    
    // Contract editing routes (super admin only)
    Route::post('contracts/{contract}/update-content', [ContractController::class, 'updateContent'])->name('contracts.update-content');
    Route::post('contracts/{contract}/validate', [ContractController::class, 'validateContract'])->name('contracts.validate');

    // Routes pour la gestion de la caisse (Cashier management)
    Route::prefix('cash')->name('cash.')->group(function() {
        Route::get('/', [App\Http\Controllers\CashTransactionController::class, 'index'])->name('index');
        Route::get('/show/{transaction}', [App\Http\Controllers\CashTransactionController::class, 'show'])->name('show');
        
        // Encaissements (Income)
        Route::get('/encaissement/create', [App\Http\Controllers\CashTransactionController::class, 'createEncaissement'])->name('encaissement.create');
        Route::post('/encaissement', [App\Http\Controllers\CashTransactionController::class, 'storeEncaissement'])->name('encaissement.store');
        
        // Décaissements (Expenses)
        Route::get('/decaissement/create', [App\Http\Controllers\CashTransactionController::class, 'createDecaissement'])->name('decaissement.create');
        Route::post('/decaissement', [App\Http\Controllers\CashTransactionController::class, 'storeDecaissement'])->name('decaissement.store');
        
        // Validation/Actions
        Route::post('/{transaction}/validate', [App\Http\Controllers\CashTransactionController::class, 'validateTransaction'])->name('validate');
        Route::post('/{transaction}/cancel', [App\Http\Controllers\CashTransactionController::class, 'cancel'])->name('cancel');
        
        // Rapports
        Route::get('/rapport', [App\Http\Controllers\CashTransactionController::class, 'rapport'])->name('rapport');
        Route::get('/export', [App\Http\Controllers\CashTransactionController::class, 'export'])->name('export');
    });
});





require __DIR__.'/auth.php';
