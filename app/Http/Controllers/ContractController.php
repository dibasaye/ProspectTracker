<?php

namespace App\Http\Controllers;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;
use Barryvdh\DomPDF\Facade\Pdf;
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
            return redirect()->back()->with('error', "Ce prospect n'a pas de lot réservé.");
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

        // Vérifier que le site existe
        if (!$site) {
            return back()->with('error', 'Le lot n\'a pas de site associé.');
        }

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
                'amount' => 0,
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
     * Nettoie et valide le contenu texte pour éviter les erreurs d'encodage
     */
    private function cleanTextContent($text)
    {
        if (empty($text)) {
            return '';
        }
        
        // Supprimer les caractères de contrôle
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // S'assurer que le texte est en UTF-8
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        
        return trim($text);
    }

    /**
     * Nettoie le contenu HTML pour corriger les problèmes de balises
     */
    private function cleanHtmlContent($html)
    {
        if (empty($html)) {
            return '';
        }
        
        // Corriger les balises auto-fermantes mal formées
        $html = preg_replace('/<br>/', '<br/>', $html);
        $html = preg_replace('/<hr>/', '<hr/>', $html);
        $html = preg_replace('/<img([^>]*)>/', '<img$1/>', $html);
        
        // Supprimer les balises HTML et body si présentes
        $html = preg_replace('/<\!?DOCTYPE html>/', '', $html);
        $html = preg_replace('/<html[^>]*>/', '', $html);
        $html = preg_replace('/<\/html>/', '', $html);
        $html = preg_replace('/<body[^>]*>/', '', $html);
        $html = preg_replace('/<\/body>/', '', $html);
        
        try {
            // S'assurer que toutes les balises sont correctement fermées
            $doc = new \DOMDocument();
            @$doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            
            return $doc->saveHTML();
        } catch (\Exception $e) {
            \Log::error('Erreur lors du nettoyage HTML', [
                'error' => $e->getMessage()
            ]);
            
            // En cas d'erreur, retourner le HTML d'origine
            return $html;
        }
    }

    /**
     * Sécurise les données du contrat avant export
     */
    private function sanitizeContractData(Contract $contract)
    {
        $data = [
            'client_name' => $this->cleanTextContent($contract->client->full_name ?? 'Client non défini'),
            'lot_info' => 'N/A',
            'site_info' => 'N/A'
        ];
        
        if ($contract->lot) {
            $data['lot_info'] = $this->cleanTextContent($contract->lot->lot_number ?? $contract->lot->reference ?? 'N/A');
        }
        
        if ($contract->site) {
            $data['site_info'] = $this->cleanTextContent($contract->site->name ?? 'N/A');
        }
        
        return $data;
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
            'content_length' => $request->has('content') ? strlen($request->content) : 0
        ]);
        
        // Vérifier si l'utilisateur est authentifié
        if (!auth()->check()) {
            \Log::warning('Tentative non autorisée de mise à jour du contenu');
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
            
            // Nettoyer le contenu
            $cleanedContent = $this->cleanTextContent($validated['content']);
            
            // Mise à jour du contenu
            $contract->content = $cleanedContent;
            $contract->updated_at = now();
            $saved = $contract->save();
            
            // Vérification après la sauvegarde
            $contract->refresh();
            \Log::info('Contrat mis à jour avec succès', [
                'saved' => $saved,
                'content_length' => $contract->content ? strlen($contract->content) : 0
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Le contenu du contrat a été mis à jour avec succès.',
                'content' => $contract->content,
                'updated_at' => $contract->updated_at->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $ve) {
            $errors = $ve->validator->errors()->all();
            \Log::error('Erreur de validation lors de la mise à jour du contenu', [
                'errors' => $errors
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $errors
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la mise à jour du contenu du contrat', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la mise à jour du contenu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export PDF corrigé pour éviter la duplication de contenu
     */
    public function exportPdf(Contract $contract)
{
    // Configuration des limites d'exécution
    set_time_limit(300);
    ini_set('memory_limit', '512M');
    
    \Log::info('=== DÉBUT GÉNÉRATION PDF ===', [
        'contract_id' => $contract->id,
        'request_has_content' => request()->has('content'),
        'request_content_length' => request()->has('content') ? strlen(request('content')) : 0,
        'db_content_length' => $contract->content ? strlen($contract->content) : 0,
    ]);
    
    try {
        // Précharger les images
        $images = [
            'header_image' => '',
            'footer_image' => '',
            'watermark_image' => ''
        ];
        
        $imageFiles = [
            'header_image' => public_path('images/yayedia.png'),
            'footer_image' => public_path('images/footer-image.png'),
            'watermark_image' => public_path('images/image.png')
        ];
        
        foreach ($imageFiles as $key => $path) {
            if (file_exists($path)) {
                $images[$key] = 'data:image/png;base64,' . base64_encode(file_get_contents($path));
            }
        }

        // Variables de contrôle - UNE SEULE SERA VRAIE
        $contentSource = null;
        $finalContent = null;
        
        // ÉTAPE 1: Déterminer la source du contenu
        if (request()->has('content') && !empty(trim(request('content')))) {
            $contentSource = 'request';
            $finalContent = $this->cleanTextContent(request('content'));
            
            // Sauvegarder immédiatement
            $contract->update(['content' => $finalContent]);
            $contract->refresh();
            
        } elseif (!empty($contract->content)) {
            $contentSource = 'database';
            $finalContent = $contract->content;
            
        } else {
            $contentSource = 'default';
            $finalContent = null; // Pas de contenu personnalisé
        }
        
        \Log::info('=== SOURCE DE CONTENU DÉTERMINÉE ===', [
            'source' => $contentSource,
            'content_length' => $finalContent ? strlen($finalContent) : 0
        ]);

        // ÉTAPE 2: Préparer les données pour la vue
        $viewData = [
            'contract' => $contract,
            'client' => $contract->client,
            'client_name' => request('client_name', $contract->client->full_name ?? 'Client non défini'),
            'contract_date' => request('contract_date', $contract->created_at->format('d/m/Y')),
            'images' => $images,
            
            // Variables de contrôle du contenu - EXCLUSIVES
            'content_source' => $contentSource,
            'custom_content' => $contentSource !== 'default' ? $finalContent : null,
        ];
        
        \Log::info('=== DONNÉES PRÉPARÉES POUR LA VUE ===', [
            'content_source' => $viewData['content_source'],
            'has_custom_content' => !empty($viewData['custom_content']),
            'custom_content_length' => $viewData['custom_content'] ? strlen($viewData['custom_content']) : 0
        ]);

        // ÉTAPE 3: Générer le PDF
        $pdf = \PDF::setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'isFontSubsettingEnabled' => true,
            'dpi' => 96,
            'defaultFont' => 'dejavu sans',
            'debug' => false
        ]);
        
        // Charger la vue avec un nom spécifique pour éviter les conflits
        $pdf->loadView('contracts.pdf', $viewData);
        
        $output = $pdf->output();
        
        \Log::info('=== PDF GÉNÉRÉ AVEC SUCCÈS ===', [
            'pdf_size' => strlen($output),
            'final_source' => $contentSource
        ]);
        
        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="contrat_' . $contract->contract_number . '.pdf"',
            'Content-Transfer-Encoding' => 'binary',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Content-Length' => strlen($output)
        ]);
        
    } catch (\Exception $e) {
        \Log::error('=== ERREUR GÉNÉRATION PDF ===', [
            'contract_id' => $contract->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return back()->with('error', 'Erreur PDF : ' . $e->getMessage());
    }
}
    
    /**
     * Validate contract and convert to locked PDF
     */
    public function validateContract(Contract $contract)
    {
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

    /**
     * Export Word avec logique similaire au PDF
     */
    public function exportWord(Contract $contract)
    {
        if (!$contract->is_editable && !auth()->user()->isAdmin()) {
            return back()->with('error', 'Ce contrat est verrouillé. Seul le PDF est disponible.');
        }
        
        // Charger les relations nécessaires
        $contract->load(['client', 'site', 'lot']);
        
        if (!$contract->client) {
            return back()->with('error', 'Ce contrat n\'a pas de client associé.');
        }
        
        // Sécuriser les données
        $contractData = $this->sanitizeContractData($contract);
        
        try {
            // Créer le document Word
            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Times New Roman');
            $phpWord->setDefaultFontSize(12);
            
            // Configuration de la section
            $section = $phpWord->addSection([
                'marginLeft' => 1134,
                'marginRight' => 1134,
                'marginTop' => 1134,
                'marginBottom' => 1134,
                'pageSizeW' => 11906,
                'pageSizeH' => 16838
            ]);
            
            // Déterminer quel contenu utiliser (même logique que le PDF)
            $useCustomContent = false;
            $customContent = null;
            
            if (request()->has('content') && !empty(trim(request()->input('content')))) {
                // Contenu personnalisé depuis la requête
                $customContent = $this->cleanTextContent(request()->input('content'));
                $useCustomContent = true;
                
                // Sauvegarder le contenu
                $contract->update([
                    'content' => $customContent,
                    'updated_at' => now()
                ]);
                
            } else if (!empty($contract->content)) {
                // Contenu personnalisé existant
                $customContent = $contract->content;
                $useCustomContent = true;
            }
            
            if ($useCustomContent && $customContent) {
                // Utiliser le contenu personnalisé
                $content = $this->cleanHtmlContent($customContent);
                
                try {
                    // Ajouter le contenu HTML au document
                    Html::addHtml($section, $content, false, true);
                } catch (\Exception $e) {
                    \Log::warning('Erreur lors de l\'ajout du HTML, utilisation de texte brut', [
                        'error' => $e->getMessage(),
                        'contract_id' => $contract->id
                    ]);
                    
                    // Fallback: utiliser du texte simple si le HTML échoue
                    $section->addText(strip_tags($content));
                }
            } else {
                // Utiliser le contenu par défaut
                
                // Logo
                $logoPath = public_path('images/yayedia.png');
                if (file_exists($logoPath)) {
                    $header = $section->addHeader();
                    $header->addImage($logoPath, [
                        'width' => 200,
                        'alignment' => 'center'
                    ]);
                }
                
                // Titre
                $section->addText(
                    'CONTRAT DE RESERVATION',
                    ['bold' => true, 'size' => 16],
                    ['alignment' => 'center', 'spaceAfter' => 500]
                );
                
                // Parties
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
                    'Et M./Mme ' . $contractData['client_name'],
                    [],
                    ['spaceAfter' => 400]
                );
                
                // Article 1
                $section->addText(
                    'Article 1 - OBJET',
                    ['bold' => true],
                    ['spaceAfter' => 200]
                );
                
                $articleContent = sprintf(
                    'La société YAYE DIA BTP consent à M./Mme %s une option de réservation sur le lot numéro %s situé à %s.',
                    $contractData['client_name'],
                    $contractData['lot_info'],
                    $contractData['site_info']
                );
                
                $section->addText(
                    $articleContent,
                    [],
                    ['spaceAfter' => 400]
                );
            }
            
            // Générer le fichier
            $filename = 'contrat_' . $contract->contract_number . '.docx';
            $tempFile = tempnam(sys_get_temp_dir(), 'contract_');
            
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempFile);
            
            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la génération du document Word', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Erreur lors de la génération du document Word : ' . $e->getMessage());
        }
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

        if ($contract->client && !$contract->client->isConverti()) {
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

        if ($contract->client) {
            $contract->client->markAsConverti();
        }

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Contrat signé avec succès. Le prospect a été marqué comme converti.');
    }

    public function export()
    {
        $contracts = Contract::with('client')->get();

        $csvData = $contracts->map(function ($c) {
            return [
                'Numéro' => $c->contract_number,
                'Client' => $c->client ? $c->client->full_name : 'Client non défini',
                'Lot' => optional($c->lot)->reference ?? 'N/A',
                'Montant' => number_format($c->total_amount ?? 0, 0, ',', ' '),
                'Durée' => ($c->payment_duration_months ?? 0) . ' mois',
                'Statut' => $c->status,
            ];
        });

        $filename = 'contracts_' . now()->format('Ymd_His') . '.csv';

        $handle = fopen('php://output', 'w');
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=$filename");

        if ($csvData->isNotEmpty()) {
            fputcsv($handle, array_keys($csvData->first()));
            foreach ($csvData as $line) {
                fputcsv($handle, $line);
            }
        }
        fclose($handle);
        exit;
    }

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

        $contract = $schedule->contract;
        $contract->paid_amount += $request->amount;
        $contract->remaining_amount = $contract->total_amount - $contract->paid_amount;
        $contract->save();

        return response()->json(['success' => true]);
    }
}