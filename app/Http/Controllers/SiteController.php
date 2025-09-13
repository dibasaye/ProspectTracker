<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Lot;
use App\Models\Prospect;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SiteController extends Controller
{
    public function index()
    {
        $sites = Site::with(['lots'])->orderBy('created_at', 'desc')->paginate(12);
        
        return view('sites.index', compact('sites'));
    }
    
    public function create()
    {
        return view('sites.create');
    }
    
   public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'location' => 'required|string|max:255',
        'description' => 'nullable|string',
        'total_area' => 'required|numeric|min:0',
        'area_unit' => 'required|in:m2,hectare,are,centiare',
        'total_lots' => 'required|integer|min:0',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
        'image_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        'launch_date' => 'nullable|date',
        
        // Prix du formulaire
        'price_angle' => 'required|numeric|min:0',
        'price_facade' => 'required|numeric|min:0',
        'price_interieur' => 'required|numeric|min:0',
        
        // Frais
        'reservation_fee' => 'required|numeric|min:0',
        'membership_fee' => 'required|numeric|min:0',
        
        // Options de paiement avec pourcentages
        'enable_payment_cash' => 'nullable|boolean',
        'enable_payment_1_year' => 'nullable|boolean',
        'enable_payment_2_years' => 'nullable|boolean',
        'enable_payment_3_years' => 'nullable|boolean',
        'percentage_1_year' => 'nullable|numeric|min:0|max:100',
        'percentage_2_years' => 'nullable|numeric|min:0|max:100',
        'percentage_3_years' => 'nullable|numeric|min:0|max:100',
    ]);

    // Convertir la superficie en m² pour stockage uniforme
    $areaInM2 = $validated['total_area'];
    switch($validated['area_unit']) {
        case 'hectare':
            $areaInM2 = $validated['total_area'] * 10000;
            break;
        case 'are':
            $areaInM2 = $validated['total_area'] * 100;
            break;
        case 'centiare':
            $areaInM2 = $validated['total_area']; // centiare = m²
            break;
        case 'm2':
        default:
            $areaInM2 = $validated['total_area'];
            break;
    }

    // Mapper les données vers les noms des colonnes de la base de données
    $siteData = [
        'name' => $validated['name'],
        'location' => $validated['location'],
        'description' => $validated['description'] ?? null,
        'total_area' => $areaInM2, // Toujours stocké en m²
        'area_unit' => $validated['area_unit'], // Unité originale
        'total_lots' => $validated['total_lots'],
        'latitude' => $validated['latitude'] ?? null,
        'longitude' => $validated['longitude'] ?? null,
        'launch_date' => $validated['launch_date'] ?? null,
        
        // CORRECTION : Utiliser les vrais noms des colonnes DB
        'angle_price' => $validated['price_angle'],
        'facade_price' => $validated['price_facade'],      
        'interior_price' => $validated['price_interieur'], 
        
        'reservation_fee' => $validated['reservation_fee'],
        'membership_fee' => $validated['membership_fee'],
        
        // Options de paiement avec pourcentages personnalisés
        'enable_payment_cash' => $request->has('enable_payment_cash') ? 1 : 0,
        'enable_payment_1_year' => $request->has('enable_payment_1_year') ? 1 : 0,
        'enable_payment_2_years' => $request->has('enable_payment_2_years') ? 1 : 0,
        'enable_payment_3_years' => $request->has('enable_payment_3_years') ? 1 : 0,
        
        // Pourcentages personnalisés
        'percentage_1_year' => $request->has('enable_payment_1_year') ? ($validated['percentage_1_year'] ?? 5) : null,
        'percentage_2_years' => $request->has('enable_payment_2_years') ? ($validated['percentage_2_years'] ?? 10) : null,
        'percentage_3_years' => $request->has('enable_payment_3_years') ? ($validated['percentage_3_years'] ?? 15) : null,
        
        // Colonnes avec valeurs par défaut
        'status' => 'active',
        'is_active' => 1,
        'payment_plan' => '24_months',
    ];

    // Gestion du fichier image
    if ($request->hasFile('image_file')) {
        $path = $request->file('image_file')->store('sites', 'public');
        $siteData['image_url'] = $path;
    }

    try {
        $site = Site::create($siteData);

        return redirect()->route('sites.show', $site)
            ->with('success', 'Site créé avec succès.');

    } catch (\Exception $e) {
        \Log::error('Erreur création site : ' . $e->getMessage());
        \Log::error('Data envoyée : ' . json_encode($siteData));
        
        return redirect()->back()
            ->with('error', 'Erreur lors de la création : ' . $e->getMessage())
            ->withInput();
    }
}
    
    public function show(Site $site)
    {
        $site->load(['lots', 'prospects', 'contracts']);

        $stats = [
            'total_lots' => $site->lots()->count(),
            'available_lots' => $site->availableLots()->count(),
            'reserved_lots' => $site->reservedLots()->count(),
            'sold_lots' => $site->soldLots()->count(),
            'total_prospects' => $site->prospects()->count(),
            'total_revenue' => $site->payments()->confirmed()->sum('amount'),
        ];

        // Déterminer si le fichier est un PDF
        $isPdf = $site->image_url ? Str::endsWith($site->image_url, '.pdf') : false;

        return view('sites.show', compact('site', 'stats', 'isPdf'));
    }
    
    public function edit(Site $site)
    {
        return view('sites.edit', compact('site'));
    }
    
    public function update(Request $request, Site $site)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'location' => 'required|string|max:255',
        'description' => 'nullable|string',
        'total_area' => 'nullable|numeric|min:0',
        'total_lots' => 'required|integer|min:0',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
        'image_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        // Correction: utiliser les noms des champs du formulaire
        'price_angle' => 'required|numeric|min:0',
        'price_facade' => 'required|numeric|min:0',
        'price_interieur' => 'required|numeric|min:0',
        'reservation_fee' => 'required|numeric|min:0',
        'membership_fee' => 'required|numeric|min:0',
    ]);

    $updateData = [
        'name' => $validated['name'],
        'location' => $validated['location'],
        'description' => $validated['description'] ?? null,
        'total_area' => $validated['total_area'] ?? null,
        'total_lots' => $validated['total_lots'],
        'latitude' => $validated['latitude'] ?? null,
        'longitude' => $validated['longitude'] ?? null,
        // Correction: mapper vers les noms de colonnes de la base de données
        'angle_price' => $validated['price_angle'],
        'facade_price' => $validated['price_facade'],
        'interior_price' => $validated['price_interieur'],
        'reservation_fee' => $validated['reservation_fee'],
        'membership_fee' => $validated['membership_fee'],
        'enable_payment_cash' => $request->has('enable_payment_cash') ? 1 : 0,
        'enable_payment_1_year' => $request->has('enable_payment_1_year') ? 1 : 0,
        'enable_payment_2_years' => $request->has('enable_payment_2_years') ? 1 : 0,
        'enable_payment_3_years' => $request->has('enable_payment_3_years') ? 1 : 0,
    ];

    if ($request->hasFile('image_file')) {
        if ($site->image_url && \Storage::disk('public')->exists($site->image_url)) {
            \Storage::disk('public')->delete($site->image_url);
        }
        $path = $request->file('image_file')->store('sites', 'public');
        $updateData['image_url'] = $path;
    }

    try {
        $site->update($updateData);
        return redirect()->route('sites.show', $site->id)
            ->with('success', 'Site modifié avec succès.');
    } catch (\Exception $e) {
        \Log::error('Erreur mise à jour site : ' . $e->getMessage());
        return redirect()->back()->with('error', 'Erreur lors de la modification : ' . $e->getMessage())->withInput();
    }
}

}