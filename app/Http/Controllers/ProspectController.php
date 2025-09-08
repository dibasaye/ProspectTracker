<?php

namespace App\Http\Controllers;

use App\Models\Prospect;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ProspectAssigned;
use Illuminate\Support\Facades\Notification;
use App\Models\FollowUpAction;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;


class ProspectController extends Controller
{
    public function index(Request $request)
    {
        $query = Prospect::with(['assignedTo', 'interestedSite']);
        
        // Filter by assigned user for agents
        if (Auth::user()->isAgent()) {
            $query->where('assigned_to_id', Auth::id());
        }
        
        // Apply filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('assigned_to') && $request->assigned_to) {
            $query->where('assigned_to_id', $request->assigned_to);
        }
        
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        $prospects = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Get filter options
        $agents = User::byRole('commercial')->active()->get();
        $sites = Site::active()->get();
        
        return view('prospects.index', compact('prospects', 'agents', 'sites'));
    }
    
    public function create()
    {
        $sites = Site::active()->get();
        $agents = User::byRole('commercial')->active()->get();
        
        return view('prospects.create', compact('sites', 'agents'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'phone_secondary' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'representative_name' => 'nullable|string|max:255',
            'representative_phone' => 'nullable|string|max:255',
            'representative_address' => 'nullable|string',
            'assigned_to_id' => 'nullable|exists:users,id',
            'interested_site_id' => 'nullable|exists:sites,id',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'id_document' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048', // ← le bon nom
        ]);

        // Gérer le fichier de pièce d'identité
         if ($request->hasFile('id_document')) {
        $validated['id_document'] = $request->file('id_document')->store('prospects/documents', 'public');
    }
        

        
        $validated['status'] = 'nouveau';
        $validated['contact_date'] = now();

        // Si l'utilisateur connecté est admin ou responsable et n'a pas choisi un assigné, on s'auto-assigne
    if (in_array(auth()->user()->role, ['administrateur', 'responsable_commercial']) && empty($validated['assigned_to_id'])) {
        $validated['assigned_to_id'] = auth()->id();
    }
        
        Prospect::create($validated);
        
        return redirect()->route('prospects.index')->with('success', 'Prospect créé avec succès.');
    }

    public function storeMultiple(Request $request)
{
    $request->validate([
        'phones.*' => 'required|string|min:8'
    ]);

    foreach ($request->phones as $phone) {
        Prospect::create([
            'phone' => $phone,
            'status' => 'nouveau',
             'contact_date' => now(),
        ]);
    }

    return redirect()->route('prospects.index')
        ->with('success', count($request->phones) . ' prospects ont été créés avec succès');
}
    
    public function show(Prospect $prospect)
    {
        $prospect->load([
            'assignedTo', 
            'interestedSite', 
            'payments', 
            'contract', 
            'reservations' => function($query) {
                $query->where('expires_at', '>', now())
                      ->with(['lot.site']);
            }
        ]);
        
        return view('prospects.show', compact('prospect'));
    }
    
    public function edit(Prospect $prospect)
    {
        $sites = Site::active()->get();
        $agents = User::byRole('commercial')->active()->get();
        
        return view('prospects.edit', compact('prospect', 'sites', 'agents'));
    }
    
    public function update(Request $request, Prospect $prospect)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'phone_secondary' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'representative_name' => 'nullable|string|max:255',
            'representative_phone' => 'nullable|string|max:255',
            'representative_address' => 'nullable|string',
            'status' => 'required|in:nouveau,en_relance,interesse,converti,abandonne',
            'assigned_to_id' => 'nullable|exists:users,id',
            'interested_site_id' => 'nullable|exists:sites,id',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'next_follow_up' => 'nullable|date',
        ]);
        
        $prospect->update($validated);
        
        return redirect()->route('prospects.show', $prospect)->with('success', 'Prospect mis à jour avec succès.');
    }
    
    public function destroy(Prospect $prospect)
    {
        $prospect->delete();
        
        return redirect()->route('prospects.index')->with('success', 'Prospect supprimé avec succès.');
    }

    public function showAssignForm(Prospect $prospect)
{
    // Récupérer tous les commerciaux uniquement
    $commerciaux = User::where('role', 'commercial')->get();

    return view('prospects.assign', compact('prospect', 'commerciaux'));
}

public function assign(Request $request, Prospect $prospect)
{
    $request->validate([
        'commercial_id' => 'required|exists:users,id',
    ]);

    // Mettre à jour la colonne assigned_to avec l'ID commercial choisi
     $prospect->assigned_to_id = $request->commercial_id;
    $prospect->save();

    // Envoyer la notification au commercial assigné
    $commercial = User::find($request->commercial_id);
    $commercial->notify(new ProspectAssigned($prospect));

    return redirect()->route('prospects.index')->with('success', 'Prospect assigné avec succès.');
}

public function assignBulk(Request $request)
{
    $request->validate([
        'prospect_ids' => 'required|string',
        'commercial_id' => 'required|exists:users,id'
    ]);

    $prospectIds = explode(',', $request->prospect_ids);
    
    $prospects = Prospect::whereIn('id', $prospectIds)->get();
    $commercial = User::find($request->commercial_id);

    foreach ($prospects as $prospect) {
        $prospect->update(['assigned_to_id' => $request->commercial_id]);
        $commercial->notify(new ProspectAssigned($prospect));
    }

    return redirect()->route('prospects.index')
        ->with('success', count($prospectIds) . ' prospects ont été assignés avec succès.');
}

public function followupForm(Prospect $prospect)
{
    return view('prospects.followup', compact('prospect'));
}

public function storeFollowup(Request $request, Prospect $prospect)
{
    $validated = $request->validate([
        'type' => 'required|in:appel,whatsapp,rdv,email',
        'notes' => 'nullable|string',
        'next_follow_up' => 'nullable|date',
        'status' => 'required|in:nouveau,en_relance,interesse,converti,abandonne',
    ]);

    FollowUpAction::create([
        'prospect_id' => $prospect->id,
        'user_id' => auth()->id(),
        'type' => $validated['type'],
        'notes' => $validated['notes'],
    ]);

    // Mettre à jour le statut du prospect
    $oldStatus = $prospect->status;
    $newStatus = $validated['status'];
    
    $prospect->update([
        'status' => $newStatus,
        'next_follow_up' => $validated['next_follow_up'],
    ]);

    // Messages spécifiques selon le changement de statut
    $message = 'Relance enregistrée.';
    
    if ($oldStatus !== $newStatus) {
        switch ($newStatus) {
            case 'interesse':
                $message = 'Prospect marqué comme intéressé.';
                break;
            case 'converti':
                $message = 'Prospect marqué comme converti !';
                break;
            case 'abandonne':
                $message = 'Prospect marqué comme abandonné.';
                break;
            case 'en_relance':
                $message = 'Prospect mis en relance.';
                break;
        }
    }

    return redirect()->route('prospects.show', $prospect)->with('success', $message);
}

public function import(Request $request)
{
    $request->validate([
        'phone_file' => 'required|file|mimes:csv,xlsx,xls'
    ]);

    try {
        Excel::import(new ProspectsImport, $request->file('phone_file'));
        return redirect()->back()->with('success', 'Import réussi !');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
    }
}

public function downloadTemplate()
{
    $headers = [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="modele_import_prospects.csv"',
    ];

    $callback = function() {
        $file = fopen('php://output', 'w');
        fputcsv($file, ['telephone']);
        fputcsv($file, ['774567890']);
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

public function storeBulk(Request $request)
{
    $request->validate([
        'phone_numbers' => 'required|string'
    ]);

    $phones = collect(explode("\n", $request->phone_numbers))
        ->map(fn($phone) => trim($phone))
        ->filter()
        ->unique();

    $count = 0;
    foreach ($phones as $phone) {
        if (preg_match('/^[0-9]{9}$/', $phone)) {
            Prospect::create([
                'phone' => $phone,
                'status' => 'nouveau',
                'contact_date' => now()
            ]);
            $count++;
        }
    }

    return redirect()->back()->with('success', "$count numéros importés avec succès !");
}

    public function changeStatus(Request $request, Prospect $prospect)
    {
        $request->validate([
            'status' => 'required|in:nouveau,en_relance,interesse,converti,abandonne,client_reservataire',
        ]);

        $oldStatus = $prospect->status;
        $newStatus = $request->status;

        $prospect->update(['status' => $newStatus]);

        // Créer une action de suivi automatique
        FollowUpAction::create([
            'prospect_id' => $prospect->id,
            'user_id' => auth()->id(),
            'type' => 'rdv',
            'notes' => "Statut changé de '$oldStatus' à '$newStatus' par " . auth()->user()->full_name,
        ]);

        $statusMessages = [
            'nouveau' => 'Prospect marqué comme nouveau.',
            'en_relance' => 'Prospect mis en relance.',
            'interesse' => 'Prospect marqué comme intéressé.',
            'converti' => 'Prospect marqué comme converti !',
            'abandonne' => 'Prospect marqué comme abandonné.',
            'client_reservataire' => 'Prospect marqué comme client réservataire.',
        ];

        return redirect()->route('prospects.show', $prospect)
            ->with('success', $statusMessages[$newStatus] ?? 'Statut mis à jour.');
    }

    public function conversionStats()
    {
        $user = Auth::user();
        
        if (!$user->isManager() && !$user->isAdmin()) {
            abort(403, 'Accès non autorisé.');
        }
        
        // Statistiques globales
        $totalProspects = Prospect::count();
        $convertedProspects = Prospect::where('status', 'converti')->count();
        $interestedProspects = Prospect::where('status', 'interesse')->count();
        $conversionRate = $totalProspects > 0 ? ($convertedProspects / $totalProspects) * 100 : 0;
        
        // Statistiques par commercial
        $commercialStats = User::where('role', 'commercial')
            ->where('is_active', true)
            ->with(['assignedProspects'])
            ->get()
            ->map(function ($commercial) {
                $prospects = $commercial->assignedProspects;
                $total = $prospects->count();
                $converted = $prospects->where('status', 'converti')->count();
                $interested = $prospects->where('status', 'interesse')->count();
                
                return [
                    'id' => $commercial->id,
                    'name' => $commercial->full_name,
                    'email' => $commercial->email,
                    'total' => $total,
                    'converted' => $converted,
                    'interested' => $interested,
                    'conversion_rate' => $total > 0 ? ($converted / $total) * 100 : 0,
                ];
            })
            ->sortByDesc('conversion_rate');
        
        // Données mensuelles
        $monthlyData = collect();
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyData->push([
                'month' => $month->format('M Y'),
                'new_prospects' => Prospect::whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->count(),
                'converted_prospects' => Prospect::where('status', 'converti')
                    ->whereMonth('updated_at', $month->month)
                    ->whereYear('updated_at', $month->year)
                    ->count(),
            ]);
        }
        
        // Comptage par statut
        $statusCounts = [
            'nouveau' => Prospect::where('status', 'nouveau')->count(),
            'en_relance' => Prospect::where('status', 'en_relance')->count(),
            'interesse' => Prospect::where('status', 'interesse')->count(),
            'converti' => Prospect::where('status', 'converti')->count(),
            'abandonne' => Prospect::where('status', 'abandonne')->count(),
        ];
        
        return view('prospects.conversion_stats', compact(
            'totalProspects',
            'convertedProspects', 
            'interestedProspects',
            'conversionRate',
            'commercialStats',
            'monthlyData',
            'statusCounts'
        ));
    }
}