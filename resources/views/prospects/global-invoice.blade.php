<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture Globale - {{ $prospect->full_name }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .header {
            border-bottom: 3px solid #e67e22;
            margin-bottom: 20px;
            padding-bottom: 15px;
        }
        
        .company-header {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .logo-section {
            display: table-cell;
            width: 80px;
            vertical-align: top;
        }
        
        .company-info {
            display: table-cell;
            vertical-align: top;
            padding-left: 15px;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #e67e22;
            margin: 0;
            line-height: 1.2;
        }
        
        .company-subtitle {
            font-size: 11px;
            color: #666;
            margin: 2px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .logo {
            width: 70px;
            height: auto;
        }
        
        .invoice-meta {
            text-align: right;
            margin-top: 20px;
        }
        
        .invoice-number {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .invoice-date {
            font-size: 12px;
            color: #666;
        }
        
        .client-section {
            margin: 30px 0;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        
        .client-info {
            margin: 0;
        }
        
        .lots-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .lots-table th,
        .lots-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        
        .lots-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        
        .lots-table .text-right {
            text-align: right;
        }
        
        .lots-table .text-center {
            text-align: center;
        }
        
        .total-section {
            margin-top: 20px;
            text-align: right;
        }
        
        .total-row {
            font-size: 14px;
            font-weight: bold;
            background-color: #f5f5f5;
        }
        
        .terms {
            margin-top: 30px;
            font-size: 11px;
            line-height: 1.6;
        }
        
        .signature-section {
            margin-top: 50px;
            text-align: right;
        }
        
        .signature-label {
            font-weight: bold;
            margin-bottom: 40px;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-header">
            <div class="logo-section">
                @if(file_exists(public_path('images/yayedia.png')))
                    <img src="{{ public_path('images/yayedia.png') }}" alt="Logo" class="logo">
                @else
                    <div style="width: 70px; height: 50px; background-color: #e67e22; display: flex; align-items: center; justify-content: center; color: white; font-size: 10px; text-align: center;">
                        LOGO
                    </div>
                @endif
            </div>
            <div class="company-info">
                <h1 class="company-name">GROUPE YAYEDIA INTERNATIONAL BTP SARL</h1>
                <div class="company-subtitle">
                    TERRASSEMENT - VENTE DE TERRAIN - CONSTRUCTION - COOPERATIVE
                </div>
            </div>
        </div>
        
        <div class="invoice-meta">
            <div class="invoice-number">N° {{ $invoiceNumber }}</div>
            <div class="invoice-date">{{ $invoiceDate }}</div>
        </div>
    </div>

    <div class="client-section">
        <strong>CLIENT:</strong><br>
        <div class="client-info">
            <strong>{{ $prospect->full_name }}</strong><br>
            @if($prospect->phone)
                Téléphone: {{ $prospect->phone }}<br>
            @endif
            @if($prospect->email)
                Email: {{ $prospect->email }}<br>
            @endif
            @if($prospect->address)
                Adresse: {{ $prospect->address }}
            @endif
        </div>
    </div>

    <table class="lots-table">
        <thead>
            <tr>
                <th style="width: 10%;">N°</th>
                <th style="width: 50%;">DÉSIGNATION</th>
                <th style="width: 20%;">P. Unitaire</th>
                <th style="width: 20%;">P. Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lots as $index => $lot)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>ACQUISITION DE 01 PARCELLES {{ strtoupper($lot->site->name) }}</strong><br>
                    <small>LOT: {{ $lot->lot_number }} / {{ $lot->area }}m²</small><br>
                    <small>Position: {{ ucfirst($lot->position) }}</small>
                    @if($lot->description)
                        <br><small>{{ $lot->description }}</small>
                    @endif
                </td>
                <td class="text-right">{{ number_format($lot->final_price, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($lot->final_price, 0, ',', ' ') }}</td>
            </tr>
            @endforeach
            
            <!-- Ligne vide pour l'espacement -->
            @for($i = 0; $i < (8 - count($lots)); $i++)
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
            @endfor
            
            <!-- Total -->
            <tr class="total-row">
                <td colspan="3" class="text-right"><strong>MONTANT TOTAL HT</strong></td>
                <td class="text-right"><strong>{{ number_format($totalAmount, 0, ',', ' ') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="terms">
        <p><strong>CONDITIONS:</strong></p>
        <p>• LA FACTURE EST EXIGÉE À LA SIGNATURE DU CETTE ÉTAT QUEL FOIS</p>
        <p>• LE PAIEMENT EST DÛE IMMÉDIATEMENT</p>
    </div>

    <div class="signature-section">
        <div class="signature-label">COMPTABILITÉ</div>
        <div style="border-bottom: 2px solid #333; width: 200px; margin-left: auto; margin-top: 30px;"></div>
    </div>
</body>
</html>