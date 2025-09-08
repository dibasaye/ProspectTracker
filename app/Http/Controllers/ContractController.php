<?php

namespace App\Http\Controllers;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;

use Illuminate\Http\Request;
use App\Models\Prospect;
use App\Models\Contract;
use App\Models\Reservation;
use App\Models\Lot;
use App\Models\PaymentSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $contracts = Contract::with(['client', 'lot'])
            ->when($request->client, function ($query, $client) {
                $query->whereHas('client', fn($q) => $q->where('full_name', 'like', "%$client%"));
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('contracts.index', compact('contracts'));
    }

    public function create(Prospect $prospect)
    {
        $lot = optional($prospect->reservation)->lot;

        if (!$lot) {
            return redirect()->back()->with('error', 'Ce prospect n’a pas de lot réservé.');
        }

        return view('contracts.create', compact('prospect', 'lot'));
    }

    public function show(Contract $contract)
    {
        $contract->load(['client', 'lot', 'site', 'paymentSchedules', 'validatedBy']);
        
        $canEdit = auth()->user()->isAdmin() && $contract->is_editable;
        $canView = true; // All authenticated users can view contracts
        
        return view('contracts.show', compact('contract', 'canEdit', 'canView'));
    }

    public function generateFromReservation(Prospect $prospect)
    {
        if (Contract::where('client_id', $prospect->id)->exists()) {
            return back()->with('error', 'Un contrat a déjà été généré pour ce client.');
        }

        $reservation = $prospect->reservation;
        if (!$reservation || !$reservation->lot) {
            return back()->with('error', 'Aucune réservation active avec un lot associé.');
        }

        $lot = $reservation->lot;
        $site = $lot->site;

        $total = $lot->price ?? 5000000;
        $duration = 12;
        $monthly = $total / $duration;

        $contract = Contract::create([
            'contract_number' => 'CTR-' . strtoupper(uniqid()),
            'client_id' => $prospect->id,
            'site_id' => $site->id,
            'lot_id' => $lot->id,
            'total_amount' => $total,
            'paid_amount' => $prospect->payments()->sum('amount'),
            'remaining_amount' => $total - $prospect->payments()->sum('amount'),
            'payment_duration_months' => $duration,
            'monthly_payment' => $monthly,
            'start_date' => now(),
            'end_date' => now()->addMonths($duration),
            'status' => 'brouillon',
            'generated_by' => auth()->id(),
        ]);

        // Marquer le prospect comme converti
        $prospect->markAsConverti();

        // Créer les échéances de paiement
        for ($i = 1; $i <= $duration; $i++) {
            $contract->paymentSchedules()->create([
                'installment_number' => $i,
                'amount' => 0, // ← on met 0 pour afficher '-' au début
                'due_date' => now()->addMonths($i),
                'is_paid' => false,
            ]);
        }

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Contrat généré avec succès. Le prospect a été marqué comme converti.');
    }

    public function preview(Contract $contract)
    {
        $contract->load([
            'client',
            'site',
            'lot',
            'payments' => function($query) {
                $query->orderBy('created_at', 'desc');
            },
            'paymentSchedules' => function($query) {
                $query->orderBy('due_date', 'asc');
            }
        ]);
        
        // Calculer les totaux
        $totalPaid = $contract->payments->sum('amount');
        $totalDue = $contract->total_amount - $totalPaid;
        
        return view('contracts.preview', [
            'contract' => $contract,
            'totalPaid' => $totalPaid,
            'totalDue' => $totalDue
        ]);
    }
    
    /**
     * Afficher le formulaire d'édition du contenu du contrat
     */
    public function editContent(Contract $contract)
    {
        $this->authorize('update', $contract);
        
        return view('contracts.edit_content', [
            'contract' => $contract
        ]);
    }
    
    /**
     * Mettre à jour le contenu du contrat
     */
    public function updateContent(Request $request, Contract $contract)
    {
        // Log de débogage
        \Log::info('Tentative de mise à jour du contrat', [
            'user_id' => auth()->id(),
            'contract_id' => $contract->id,
            'has_content' => $request->has('content'),
            'content_length' => $request->has('content') ? strlen($request->content) : 0,
            'request_headers' => $request->headers->all(),
            'request_method' => $request->method(),
            'is_ajax' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
            'content_type' => $request->header('Content-Type'),
            'accept_header' => $request->header('Accept')
        ]);
        
        // Vérifier si l'utilisateur est authentifié
        if (!auth()->check()) {
            \Log::warning('Tentative non autorisée de mise à jour du contenu', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé. Veuillez vous connecter.'
            ], 403);
        }
        
        try {
            // Valider la requête
            $validated = $request->validate([
                'content' => 'required|string',
            ]);
            
            // Nettoyer le contenu HTML pour éviter les problèmes de sécurité
            $cleanedContent = htmlspecialchars($validated['content'], 
                ENT_QUOTES | ENT_HTML5, 
                'UTF-8', 
                false // Ne pas double encoder
            );
            
            // Log avant la mise à jour
            \Log::debug('Contenu validé et nettoyé', [
                'original_length' => strlen($validated['content']),
                'cleaned_length' => strlen($cleanedContent),
                'content_preview' => substr($cleanedContent, 0, 100) . '...',
                'content_has_html' => $cleanedContent !== strip_tags($cleanedContent)
            ]);
            
            // Mise à jour du contenu
            $contract->content = $cleanedContent;
            $contract->updated_at = now();
            $saved = $contract->save();
            
            // Vérification après la sauvegarde
            $contract->refresh();
            \Log::info('Contrat mis à jour avec succès', [
                'saved' => $saved,
                'content_length' => $contract->content ? strlen($contract->content) : 0,
                'updated_at' => $contract->updated_at,
                'content_in_db' => $contract->content ? 'oui' : 'non'
            ]);
            
            // Réponse JSON avec le contenu mis à jour
            return response()->json([
                'success' => true,
                'message' => 'Le contenu du contrat a été mis à jour avec succès.',
                'content' => $contract->content,
                'content_length' => $contract->content ? strlen($contract->content) : 0,
                'updated_at' => $contract->updated_at->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $ve) {
            // Gestion spécifique des erreurs de validation
            $errors = $ve->validator->errors()->all();
            \Log::error('Erreur de validation lors de la mise à jour du contenu', [
                'errors' => $errors,
                'request_data' => $request->except(['content']),
                'content_length' => $request->has('content') ? strlen($request->content) : 0
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $errors,
                'content_length' => $request->has('content') ? strlen($request->content) : 0
            ], 422);
            
        } catch (\Exception $e) {
            // Gestion des autres erreurs
            \Log::error('Erreur lors de la mise à jour du contenu du contrat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour du contenu: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'exception' => get_class($e)
            ], 500);
        }
    }

    public function exportPdf(Contract $contract)
    {
        // Configuration des limites d'exécution
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '512M'); // Augmentation de la mémoire
        
        // Désactiver le débogage pour améliorer les performances
        if (function_exists('xdebug_disable')) {
            xdebug_disable();
        }
        
        // Journalisation pour le débogage
        \Log::info('Début de la génération du PDF pour le contrat', [
            'contract_id' => $contract->id,
            'has_content_param' => request()->has('content'),
            'has_stored_content' => !empty($contract->content),
            'client_id' => $contract->client_id
        ]);
        
        try {
            // Précharger les images en base64 avec gestion d'erreur
            $images = [];
            $imageFiles = [
                'header_image' => public_path('images/yayedia.png'),
                'footer_image' => public_path('images/footer-image.png'),
                'watermark_image' => public_path('images/image.png')
            ];
            
            foreach ($imageFiles as $key => $path) {
                if (file_exists($path)) {
                    $images[$key] = 'data:image/png;base64,' . base64_encode(file_get_contents($path));
                } else {
                    \Log::warning("Fichier image manquant : " . $path);
                    $images[$key] = ''; // Valeur par défaut si l'image est manquante
                }
            }
    
            // Préparer les données de base
            $data = [
                'contract' => $contract,
                'client' => $contract->client, // Ajout du client complet pour la vue
                'client_name' => request('client_name', $contract->client->full_name),
                'contract_date' => request('contract_date', $contract->created_at->format('d/m/Y')),
                'header_image' => $images['header_image'],
                'footer_image' => $images['footer_image'],
                'watermark_image' => $images['watermark_image']
            ];
    
            // Journalisation des données de base
            \Log::debug('Données de base préparées', [
                'client_name' => $data['client_name'],
                'contract_date' => $data['contract_date']
            ]);
    
            // Gestion du contenu personnalisé
            if (request()->has('content') && !empty(trim(request('content')))) {
                // Nettoyer le contenu HTML
                $content = trim(request('content'));
                
                // Journalisation avant sauvegarde
                \Log::debug('Contenu personnalisé reçu', [
                    'content_length' => strlen($content),
                    'content_preview' => substr(strip_tags($content), 0, 100) . '...'
                ]);
                
                // Sauvegarder le contenu modifié dans la base de données
                $contract->update([
                    'content' => $content,
                    'updated_at' => now()
                ]);
                
                $data['custom_content'] = $content;
                \Log::info('Contenu personnalisé sauvegardé dans la base de données');
            } 
            // Utiliser le contenu sauvegardé s'il existe
            else if (!empty($contract->content)) {
                $data['custom_content'] = $contract->content;
                \Log::info('Utilisation du contenu sauvegardé existant', [
                    'content_length' => strlen($contract->content)
                ]);
            }
            
            // Si aucun contenu personnalisé n'est fourni, utiliser le contenu par défaut
            if (empty($data['custom_content'])) {
                \Log::info('Aucun contenu personnalisé trouvé, utilisation du contenu par défaut');
                $defaultContent = view('contracts.default_content', [
                    'contract' => $contract,
                    'client' => $contract->client
                ])->render();
                
                // Sauvegarder le contenu par défaut pour une utilisation future
                $contract->update([
                    'content' => $defaultContent,
                    'updated_at' => now()
                ]);
                
                $data['custom_content'] = $defaultContent;
                \Log::info('Contenu par défaut généré et sauvegardé', [
                    'content_length' => strlen($defaultContent)
                ]);
            }
            
            // Journalisation finale des données
            \Log::debug('Données finales pour la génération du PDF', [
                'has_custom_content' => !empty($data['custom_content']),
                'content_length' => !empty($data['custom_content']) ? strlen($data['custom_content']) : 0,
                'client_name' => $data['client_name'],
                'contract_date' => $data['contract_date']
            ]);
    
            // Configuration optimisée de DomPDF
            $pdf = \PDF::setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,  // Désactivé car on utilise base64 pour les images
                'isPhpEnabled' => false,     // Désactivé si non nécessaire
                'isFontSubsettingEnabled' => true,
                'dpi' => 96,
                'defaultFont' => 'dejavu sans',
                // Désactivation complète du débogage
                'debug' => false,
                'debugKeepTemp' => false,
                'debugCss' => false,
                'debugLayout' => false,
                'debugLayoutLines' => false,
                'debugLayoutBlocks' => false,
                'debugLayoutInline' => false,
                'debugLayoutPaddingBox' => false,
                // Optimisations de performance
                'enableCssFloat' => true,
                'isJavascriptEnabled' => false,
                'isHtml5Parser' => true,
                'isPhpEnabled' => false,
                'isRemoteEnabled' => false,
            ])->loadView('contracts.pdf', $data);
            
            // Compression et options de sortie
            $output = $pdf->output();
            \Log::info('PDF généré avec succès', [
                'pdf_size' => strlen($output),
                'contract_id' => $contract->id
            ]);
            
            return response($output, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="contrat_' . $contract->contract_number . '.pdf"',
                'Content-Transfer-Encoding' => 'binary',
                'Expires' => '0',
                'Cache-Control' => 'private, max-age=0, must-revalidate',
                'Pragma' => 'public',
                'Content-Length' => strlen($output)
            ]);
            
        } catch (\Exception $e) {
            // Journalisation de l'erreur
            \Log::error('Erreur lors de la génération du PDF', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Rediriger avec un message d'erreur en cas d'échec
            return back()->with('error', 'Une erreur est survenue lors de la génération du PDF : ' . $e->getMessage());
        }
    }

    
    /**
     * Validate contract and convert to locked PDF
     */
    public function validateContract(Contract $contract)
    {
        // Only super admin can validate contracts
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Seul le super administrateur peut valider les contrats.');
        }
        
        $contract->update([
            'is_editable' => false,
            'validated_by' => auth()->id(),
            'validated_at' => now(),
            'status' => 'valide'
        ]);
        
        return back()->with('success', 'Contrat validé avec succès. Il est maintenant verrouillé en PDF.');
    }

    public function exportWord(Contract $contract)
    {
        // Only allow Word export if contract is editable and user is admin
        if (!$contract->is_editable && !auth()->user()->isAdmin()) {
            return back()->with('error', 'Ce contrat est verrouillé. Seul le PDF est disponible.');
        }
        
        // Récupérer le contenu modifié s'il existe
        if (request()->has('content')) {
            $content = request()->input('content');
            $clientName = request()->input('client_name', $contract->client->full_name);
            $contractDate = request()->input('contract_date', now()->format('d/m/Y'));
            
            // Sauvegarder le contenu modifié dans la base de données
            $contract->update([
                'content' => $content,
                'updated_at' => now()
            ]);
            
            // Mettre à jour les données du contrat avec le contenu modifié
            $contract->custom_content = $content;
            $contract->client_name = $clientName;
            $contract->contract_date = $contractDate;
        }
        
        // Charger les données nécessaires pour la vue
        $contract->load(['client', 'site', 'lot']);
        
        // Créer un nouveau document Word
        $phpWord = new PhpWord();
        
        // Définir les styles de base
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);
        
        // Configuration de la mise en page
        $section = $phpWord->addSection([
            'marginLeft' => 1134,   // 2 cm en twips (1 cm = 567 twips)
            'marginRight' => 1134,
            'marginTop' => 1134,
            'marginBottom' => 1134,
            'pageSizeW' => 11906,  // Largeur A4 en twips (21cm)
            'pageSizeH' => 16838   // Hauteur A4 en twips (29.7cm)
        ]);
        
        // Vérifier si un contenu personnalisé est fourni
        if (request()->has('content')) {
            // Utiliser le contenu personnalisé
            $content = request()->input('content');
            $clientName = request()->input('client_name', $contract->client->full_name);
            $contractDate = request()->input('contract_date', now()->format('d/m/Y'));
            
            // Ajouter le contenu HTML au document Word
            \PhpOffice\PhpWord\Shared\Html::addHtml($section, $content, false, true);
        } else {
            // Utiliser le contenu par défaut
            
            // Ajouter le logo
            $header = $section->addHeader();
            $header->addImage(
                public_path('images/yayedia.png'),
                [
                    'width' => 200,
                    'alignment' => 'center'
                ]
            );
            
            // Ajouter le titre
            $section->addText(
                'CONTRAT DE RÉSERVATION',
                ['bold' => true, 'size' => 16],
                ['alignment' => 'center', 'spaceAfter' => 500]
            );
            
            // Ajouter les informations du contrat
            $section->addText(
                'Entre les soussignés :',
                ['bold' => true],
                ['spaceAfter' => 200]
            );
            
            $section->addText(
                'La Société YAYE DIA BTP',
                [],
                ['spaceAfter' => 200]
            );
            
            $section->addText(
                'Et M./Mme ' . $contract->client->full_name,
                [],
                ['spaceAfter' => 400]
            );
            
            // Ajouter le contenu du contrat
            $section->addText(
                'Article 1 - OBJET\n' .
                'La société YAYE DIA BTP consent à M./Mme ' . $contract->client->full_name . 
                ' une option de réservation sur le lot n°' . $contract->lot-number>reference . 
                ' situé à ' . $contract->site->name . '.',
                [],
                ['spaceAfter' => 400]
            );
            
            // Ajouter la signature
            $section->addText(
                'Fait à Dakar, le ' . now()->format('d/m/Y') . '\n\n' .
                'Le Client\n\n\n' .
                'M./Mme ' . $contract->client->full_name . '\n' .
                'Pièce d\'identité : ' . $contract->client->id_number . '\n' .
                'Délivrée le : ' . ($contract->client->id_issue_date ? $contract->client->id_issue_date->format('d/m/Y') : '') . '\n' .
                'À : ' . $contract->client->id_issue_place . '\n\n' .
                'Pour YAYE DIA BTP\n\n\n' .
                'Le Gérant',
                [],
                ['spaceAfter' => 0]
            );
        }

        // Enregistrer le document
        $filename = 'contrat_' . $contract->contract_number . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'contract_');
        
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);
        
        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    public function uploadSignedCopy(Request $request, Contract $contract)
    {
        $request->validate([
            'signed_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $file = $request->file('signed_file');
        $filename = 'contrat_signe_' . $contract->contract_number . '.' . $file->getClientOriginalExtension();
        
        $path = $file->storeAs('contracts/signed', $filename, 'public');

        $contract->update([
            'contract_file_url' => $path,
            'status' => 'signe',
            'signature_date' => now(),
            'signed_by_agent' => auth()->id(),
        ]);

        // Marquer le prospect comme converti si ce n'est pas déjà fait
        if (!$contract->client->isConverti()) {
            $contract->client->markAsConverti();
        }

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Contrat signé uploadé avec succès. Le prospect a été marqué comme converti.');
    }

    public function signContract(Request $request, Contract $contract)
    {
        $request->validate([
            'signature_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $contract->update([
            'status' => 'signe',
            'signature_date' => $request->signature_date,
            'signed_by_agent' => auth()->id(),
            'notes' => $request->notes,
        ]);

        // Marquer le prospect comme converti
        $contract->client->markAsConverti();

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Contrat signé avec succès. Le prospect a été marqué comme converti.');
    }

    public function export()
    {
        $contracts = Contract::with('client')->get();

        $csvData = $contracts->map(function ($c) {
            return [
                'Numéro' => $c->contract_number,
                'Client' => $c->client->full_name,
                'Lot' => optional($c->lot)->reference,
                'Montant' => $c->total_amount,
                'Durée' => $c->payment_duration_months . ' mois',
                'Statut' => $c->status,
            ];
        });

        $filename = 'contracts_' . now()->format('Ymd_His') . '.csv';

        $handle = fopen('php://output', 'w');
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=$filename");

        fputcsv($handle, array_keys($csvData->first()));
        foreach ($csvData as $line) {
            fputcsv($handle, $line);
        }
        fclose($handle);
        exit;
    }

    // ✅ Méthode pour AJAX de paiement
    public function pay(Request $request, $scheduleId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $schedule = PaymentSchedule::findOrFail($scheduleId);
        $schedule->update([
            'amount' => $request->amount,
            'is_paid' => true,
            'paid_date' => now(),
        ]);

        // Met à jour le contrat aussi
        $contract = $schedule->contract;
        $contract->paid_amount += $request->amount;
        $contract->remaining_amount = $contract->total_amount - $contract->paid_amount;
        $contract->save();

        return response()->json(['success' => true]);
    }
}
