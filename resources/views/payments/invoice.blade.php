<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Facture - {{ $payment->reference_number }}</title>
    <style>
        body { 
            font-family: DejaVu Sans, sans-serif; 
            margin: 0; 
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        .document-container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #000;
            padding: 0;
        }
        .header-section {
            background-color: #f8f8f8;
            padding: 15px;
            border-bottom: 2px solid #000;
            text-align: center;
        }
        .company-logo {
            font-size: 18px;
            font-weight: bold;
            color: #d2691e;
            margin-bottom: 5px;
        }
        .company-services {
            font-size: 10px;
            color: #666;
            font-weight: normal;
        }
        .client-info {
            padding: 15px;
            border-bottom: 1px solid #000;
        }
        .client-info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .client-info-table td {
            padding: 5px 0;
            border: none;
            vertical-align: top;
        }
        .client-info-left {
            width: 50%;
            text-align: left;
        }
        .client-info-right {
            width: 50%;
            text-align: right;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .main-table th,
        .main-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .main-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .amount-cell {
            text-align: right;
            font-weight: bold;
        }
        .conditions-section {
            padding: 15px;
            border-top: 1px solid #000;
            font-size: 10px;
        }
        .signature-section {
            padding: 15px;
            text-align: right;
            border-top: 1px solid #000;
        }
        .number-column {
            width: 30px;
            text-align: center;
        }
        .description-column {
            width: 60%;
        }
        .price-column {
            width: 15%;
            text-align: right;
        }
        .signature-line {
            height: 60px; 
            width: 150px; 
            border-bottom: 1px solid #000; 
            margin: 20px 0 0 auto;
        }
    </style>
</head>
<body>
    <div class="document-container">
        <!-- En-tête avec logo et informations société -->
        <div class="header-section">
            <div class="company-logo">
                GROUPE YAYEDIA INTERNATIONAL BTP SARL
            </div>
            <div class="company-services">
                TERRASSEMENT - VENTE DE TERRAIN - CONSTRUCTION - COOPÉRATIVE
            </div>
        </div>

        <!-- Informations client et facture -->
        <div class="client-info">
            <table class="client-info-table">
                <tr>
                    <td class="client-info-left">
                        <strong>{{ $payment->client->first_name ?? '' }} {{ $payment->client->last_name ?? $payment->client->full_name }}</strong>
                    </td>
                    <td class="client-info-right">
                        <strong>N° {{ $payment->reference_number }}</strong>
                    </td>
                </tr>
                <tr>
                    <td class="client-info-left">
                        @if(isset($payment->lot))
                            LOT {{ $payment->lot->number ?? 'N/A' }}
                        @else
                            {{ $payment->site->name ?? 'Site non spécifié' }}
                        @endif
                    </td>
                    <td class="client-info-right">
                        {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : now()->format('d/m/Y') }}
                    </td>
                </tr>
            </table>
        </div>

        <!-- Tableau principal -->
        <table class="main-table">
            <!-- En-tête du tableau -->
            <thead>
                <tr>
                    <th class="number-column">N°</th>
                    <th class="description-column">DÉSIGNATION</th>
                    <th class="price-column">P. Unitaire</th>
                    <th class="price-column">P. Total</th>
                </tr>
            </thead>
            <tbody>
                <!-- Ligne principale -->
                <tr>
                    <td class="number-column">1</td>
                    <td class="description-column">
                        @switch($payment->type)
                            @case('adhesion')
                                ADHÉSION / FRAIS D'OUVERTURE DE DOSSIER
                                @break
                            @case('reservation')
                                RÉSERVATION 
                                @if(isset($payment->lot))
                                    LOT {{ $payment->lot->number }}
                                @endif
                                @break
                            @case('mensualite')
                                MENSUALITÉ 
                                @if(isset($payment->lot))
                                    LOT {{ $payment->lot->number }}
                                @endif
                                @break
                            @default
                                {{ strtoupper($payment->description ?? ucfirst($payment->type)) }}
                        @endswitch
                        @if(isset($payment->site))
                            - {{ $payment->site->name }}
                        @endif
                    </td>
                    <td class="amount-cell">{{ number_format($payment->amount, 0, ',', ' ') }}</td>
                    <td class="amount-cell">{{ number_format($payment->amount, 0, ',', ' ') }}</td>
                </tr>
                
                <!-- Lignes vides pour l'espace -->
                @for($i = 2; $i <= 8; $i++)
                <tr>
                    <td class="number-column">{{ $i }}</td>
                    <td class="description-column">&nbsp;</td>
                    <td class="price-column">&nbsp;</td>
                    <td class="price-column">&nbsp;</td>
                </tr>
                @endfor
                
                <!-- Ligne total -->
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="3" style="text-align: center;">MONTANT TOTAL HT</td>
                    <td class="amount-cell">{{ number_format($payment->amount, 0, ',', ' ') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Section des conditions -->
        <div class="conditions-section">
            <p><strong>LA FACTURE EST ARRÊTÉE À LA SOMME DE :</strong> {{ number_format($payment->amount, 0, ',', ' ') }} FRANCS CFA</p>
            <br>
            <p><strong>PAIEMENT EN TROIS MODES POSSIBLES :</strong></p>
        </div>
        
        <!-- Section signature -->
        <div class="signature-section">
            <p><strong>COMPTABILITÉ</strong></p>
            <div class="signature-line"></div>
        </div>
    </div>
</body>
</html>