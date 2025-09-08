<x-app-layout>
    <x-slot name="header">
        <h2 class="h4 fw-bold">
            <i class="fas fa-user me-2"></i>Détails du Prospect : {{ $prospect->full_name }}
        </h2>
    </x-slot>

    @inject('str', 'Illuminate\Support\Str')

    <div class="container py-4">

        <!-- Informations personnelles -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Informations personnelles</h5>
                <p><strong>Nom :</strong> {{ $prospect->full_name }}</p>
                <p><strong>Téléphone :</strong> {{ $prospect->phone }}</p>
                @if($prospect->phone_secondary)
                    <p><strong>Téléphone secondaire :</strong> {{ $prospect->phone_secondary }}</p>
                @endif
                @if($prospect->email)
                    <p><strong>Email :</strong> {{ $prospect->email }}</p>
                @endif
                @if($prospect->address)
                    <p><strong>Adresse :</strong> {{ $prospect->address }}</p>
                @endif
                @if($prospect->id_document)
                    <p><strong>Pièce d’identité :</strong></p>
                    @if($str::endsWith($prospect->id_document, '.pdf'))
                        <a href="{{ asset('storage/' . $prospect->id_document) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                            📄 Voir le document PDF
                        </a>
                    @else
                        <img src="{{ asset('storage/' . $prospect->id_document) }}" alt="Pièce d’identité" class="img-fluid rounded shadow-sm mb-2" style="max-width: 300px;">
                    @endif
                @endif
            </div>
        </div>

        <!-- Détails commerciaux -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Détails commerciaux</h5>
                <p><strong>Statut :</strong> 
                    <span class="badge bg-info text-dark">{{ ucfirst(str_replace('_', ' ', $prospect->status)) }}</span>
                </p>
                <p><strong>Assigné à :</strong> {{ $prospect->assignedTo->full_name ?? 'Non assigné' }}</p>
                <p><strong>Site d’intérêt :</strong> {{ $prospect->interestedSite->name ?? '-' }}</p>
                @if($prospect->budget_min || $prospect->budget_max)
                    <p><strong>Budget :</strong>
                        {{ number_format($prospect->budget_min ?? 0, 0, ',', ' ') }} - 
                        {{ number_format($prospect->budget_max ?? 0, 0, ',', ' ') }} FCFA
                    </p>
                @endif
                @if($prospect->contact_date)
                    <p><strong>Date de contact :</strong> {{ $prospect->contact_date->format('d/m/Y') }}</p>
                @endif
                @if($prospect->notes)
                    <p><strong>Notes :</strong> {{ $prospect->notes }}</p>
                @endif
            </div>
        </div>

        <!-- Paiement d’adhésion -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Paiement d’adhésion</h5>
                @if($adhesionPayment = $prospect->payments()->byType('adhesion')->first())
                    <p><strong>Montant :</strong> {{ number_format($adhesionPayment->amount, 0, ',', ' ') }} FCFA</p>
                    <p><strong>Date :</strong> {{ $adhesionPayment->payment_date->format('d/m/Y') }}</p>
                    <p><strong>Mode :</strong> {{ ucfirst($adhesionPayment->payment_method) }}</p>
                    <p><strong>Référence :</strong> {{ $adhesionPayment->reference_number }}</p>
                    <p><strong>Motif :</strong> Adhésion / Frais d’ouverture de dossier</p>
                    <a href="{{ route('payments.invoice', $adhesionPayment) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                        📄 Télécharger la facture
                    </a>
                @else
                    <p class="text-muted">Aucun paiement d’adhésion enregistré.</p>
                @endif
            </div>
        </div>

        <!-- Paiement de réservation -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Paiement de réservation</h5>
                @if($reservationPayment = $prospect->payments()->byType('reservation')->first())
                    <p><strong>Montant :</strong> {{ number_format($reservationPayment->amount, 0, ',', ' ') }} FCFA</p>
                    <p><strong>Date :</strong> {{ $reservationPayment->payment_date->format('d/m/Y') }}</p>
                    <p><strong>Mode :</strong> {{ ucfirst($reservationPayment->payment_method) }}</p>
                    <p><strong>Référence :</strong> {{ $reservationPayment->reference_number }}</p>
                    <p><strong>Lot :</strong> {{ $reservationPayment->lot->lot_number ?? '-' }}</p>
                    <a href="{{ route('payments.invoice', $reservationPayment) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                        📄 Télécharger la facture
                    </a>
                @else
                    <p class="text-muted">Aucun paiement de réservation enregistré.</p>
                @endif
            </div>
        </div>

        <!-- Lots réservés -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fas fa-map-marked-alt me-2"></i>Lots réservés
                    @php
                        $reservationsCount = $prospect->reservations ? $prospect->reservations->count() : 0;
                    @endphp
                    @if($reservationsCount > 0)
                        <span class="badge bg-primary">{{ $reservationsCount }}</span>
                    @endif
                </h5>

                @php
                    $hasReservations = $prospect->reservations && $prospect->reservations->count() > 0;
                @endphp

                @if($hasReservations)
                    <div class="row">
                        @foreach($prospect->reservations as $reservation)
                            <div class="col-md-6 mb-3">
                                <div class="card border-primary">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-map-marker-alt me-2"></i>
                                            Lot {{ $reservation->lot->lot_number }}
                                            @if($reservation->lot->status === 'reserve')
                                                <span class="badge bg-warning">Réservé</span>
                                            @elseif($reservation->lot->status === 'vendu') 
                                                <span class="badge bg-success">Vendu</span>
                                            @endif
                                        </h6>
                                        
                                        <p class="card-text">
                                            <i class="fas fa-building me-2"></i><strong>Site :</strong> {{ $reservation->lot->site->name }}<br>
                                            <i class="fas fa-ruler-combined me-2"></i><strong>Superficie :</strong> {{ number_format($reservation->lot->area, 0) }} m²<br>
                                            @if($reservation->lot->price)
                                                <i class="fas fa-tag me-2"></i><strong>Prix :</strong> {{ number_format($reservation->lot->price, 0, ',', ' ') }} FCFA
                                            @endif
                                        </p>

                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                Réservé jusqu'au {{ $reservation->expires_at->format('d/m/Y H:i') }}
                                            </small>
                                        </div>

                                        <div class="mt-3">
                                            <a href="{{ route('sites.lots', $reservation->lot->site) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i>Voir sur le plan du site
                                            </a>
                                            @if($reservation->lot->contract)
                                                <a href="{{ route('contracts.show', $reservation->lot->contract) }}" class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-file-contract me-1"></i>Contrat
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Résumé rapide -->
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Résumé :</strong> Ce client a réservé {{ $prospect->reservations->count() }} lot(s) 
                        @if($prospect->reservations->pluck('lot.site_id')->unique()->count() > 1)
                            dans {{ $prospect->reservations->pluck('lot.site_id')->unique()->count() }} site(s) différent(s)
                        @else
                            dans le site "{{ $prospect->reservations->first()->lot->site->name }}"
                        @endif
                        pour une superficie totale de {{ number_format($prospect->reservations->sum('lot.area'), 0) }} m².
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-map-marker-alt text-muted fa-3x mb-3"></i>
                        <p class="text-muted">Ce client n'a encore réservé aucun lot.</p>
                        @if(auth()->user()->isAdmin() || auth()->user()->isAgent())
                            <a href="{{ route('reservations.create', $prospect) }}" class="btn btn-outline-primary">
                                <i class="fas fa-plus me-1"></i>Réserver un lot
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Contrat -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Contrat</h5>
                @if($prospect->contract)
                    <p><strong>Numéro :</strong> {{ $prospect->contract->contract_number }}</p>
                    <p><strong>Montant total :</strong> {{ number_format($prospect->contract->total_amount, 0, ',', ' ') }} FCFA</p>
                    <p><strong>Durée (mois) :</strong> {{ $prospect->contract->payment_duration_months }}</p>
                    <p><strong>Statut :</strong> {{ ucfirst($prospect->contract->status) }}</p>
                    <a href="{{ route('contracts.show', $prospect->contract) }}" class="btn btn-sm btn-outline-primary">Voir le contrat</a>
                @else
                    <p class="text-muted">Aucun contrat généré pour ce prospect.</p>
                @endif
            </div>
        </div>

        <!-- Historique des relances -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Historique des relances</h5>
                    <a href="{{ route('prospects.followup.form', $prospect) }}" class="btn btn-outline-primary btn-sm">
                        + Ajouter une relance
                    </a>
                </div>

                @if($prospect->followUps->count())
                    <ul class="list-group">
                        @foreach($prospect->followUps as $followup)
                            <li class="list-group-item">
                                <strong>{{ ucfirst($followup->type) }}</strong>
                                <small class="text-muted">par {{ $followup->user->full_name }} le {{ $followup->created_at->format('d/m/Y H:i') }}</small>
                                <div class="mt-1">{{ $followup->notes }}</div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted">Aucune relance enregistrée.</p>
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="d-flex flex-wrap gap-2 mb-4">
            @if(auth()->user()->isAdmin() || auth()->user()->isAgent())
                <a href="{{ route('reservations.create', $prospect) }}" class="btn btn-outline-success"> Réserver un lot</a>
            @endif

            <a href="{{ route('payments.create', ['prospect' => $prospect->id]) }}" class="btn btn-success"> Paiement d’adhésion</a>

            <a href="{{ route('payments.reservation.create', $prospect) }}" class="btn btn-outline-primary mt-2">
                 Paiement de Réservation
            </a>

            <a href="{{ route('contracts.generate', $prospect) }}" class="btn btn-primary">Générer contrat automatiquement</a>

            <a href="{{ route('prospects.edit', $prospect) }}" class="btn btn-warning"> Modifier</a>

            <form action="{{ route('prospects.destroy', $prospect) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce prospect ?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger">🗑 Supprimer</button>
            </form>
        </div>
    </div>
</x-app-layout>
