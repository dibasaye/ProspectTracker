<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reçu de Versement - YAYE DIA BTP</title>
    <style>
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #f39c12;
            padding-bottom: 15px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .document-title {
            font-size: 18px;
            color: #f39c12;
            font-weight: bold;
        }
        .document-subtitle {
            font-size: 12px;
            color: #e67e22;
            font-style: italic;
        }
        .receipt-number {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 10px;
        }
        .content {
            margin: 30px 0;
        }
        .client-info {
            background: #ecf0f1;
            padding: 15px;
            border-left: 4px solid #3498db;
            margin-bottom: 20px;
        }
        .payment-details {
            background: #fff3cd;
            padding: 15px;
            border-left: 4px solid #f39c12;
            margin-bottom: 20px;
            border: 1px solid #fdeaa3;
        }
        .validation-status {
            background: #d1ecf1;
            padding: 15px;
            border-left: 4px solid #17a2b8;
            margin-bottom: 20px;
            border: 1px solid #bee5eb;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
            border-bottom: 1px dotted #bdc3c7;
            padding-bottom: 5px;
        }
        .label {
            font-weight: bold;
            width: 180px;
            color: #2c3e50;
        }
        .value {
            flex: 1;
            color: #34495e;
        }
        .amount {
            font-size: 16px;
            font-weight: bold;
            color: #f39c12;
        }
        .reference {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #2c3e50;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-style: italic;
            color: #7f8c8d;
            border-top: 1px solid #bdc3c7;
            padding-top: 15px;
        }
        .signature-area {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 200px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 12px;
        }
        .next-steps {
            background: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-top: 20px;
        }
        .step {
            margin-bottom: 8px;
        }
        .step-number {
            background: #007bff;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">YAYE DIA BTP</div>
        <div class="document-title">REÇU DE VERSEMENT</div>
        <div class="document-subtitle">Confirmation de demande de paiement d'échéance</div>
        <div class="receipt-number">Référence : {{ $payment->reference_number }}</div>
    </div>

    <div class="content">
        <div class="client-info">
            <h3 style="margin-top: 0; color: #2c3e50;">Informations Client</h3>
            <div class="info-row">
                <span class="label">Nom complet :</span>
                <span class="value">{{ $payment->client->full_name }}</span>
            </div>
            <div class="info-row">
                <span class="label">Téléphone :</span>
                <span class="value">{{ $payment->client->phone ?? 'Non renseigné' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Contrat N° :</span>
                <span class="value">{{ $payment->contract->contract_number ?? 'N/A' }}</span>
            </div>
        </div>

        <div class="payment-details">
            <h3 style="margin-top: 0; color: #f39c12;">Détails du Versement</h3>
            <div class="info-row">
                <span class="label">Échéance N° :</span>
                <span class="value">{{ $schedule->installment_number }}</span>
            </div>
            <div class="info-row">
                <span class="label">Montant versé :</span>
                <span class="value amount">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="info-row">
                <span class="label">Méthode de paiement :</span>
                <span class="value">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
            </div>
            <div class="info-row">
                <span class="label">Date d'échéance :</span>
                <span class="value">{{ $schedule->due_date->format('d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span class="label">Date de versement :</span>
                <span class="value">{{ $payment->payment_date->format('d/m/Y à H:i') }}</span>
            </div>
            @if($payment->site)
            <div class="info-row">
                <span class="label">Site :</span>
                <span class="value">{{ $payment->site->name }}</span>
            </div>
            @endif
            @if($payment->lot)
            <div class="info-row">
                <span class="label">Lot N° :</span>
                <span class="value">{{ $payment->lot->number }}</span>
            </div>
            @endif
            @if($payment->notes)
            <div class="info-row">
                <span class="label">Notes :</span>
                <span class="value">{{ $payment->notes }}</span>
            </div>
            @endif
        </div>

        <div class="validation-status">
            <h3 style="margin-top: 0; color: #17a2b8;">Statut de Validation</h3>
            <div class="info-row">
                <span class="label">Statut actuel :</span>
                <span class="value" style="color: #f39c12; font-weight: bold;">EN ATTENTE DE VALIDATION</span>
            </div>
            <div class="info-row">
                <span class="label">Créé par :</span>
                <span class="value">{{ $payment->createdBy->name ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="label">Date de création :</span>
                <span class="value">{{ $payment->created_at->format('d/m/Y à H:i') }}</span>
            </div>
        </div>

        <div class="next-steps">
            <h3 style="margin-top: 0; color: #495057;">Prochaines Étapes</h3>
            <div class="step">
                <span class="step-number">1</span>
                <strong>Caissier :</strong> Vérification du montant et du justificatif
            </div>
            <div class="step">
                <span class="step-number">2</span>
                <strong>Responsable :</strong> Validation du paiement
            </div>
            <div class="step">
                <span class="step-number">3</span>
                <strong>Administrateur :</strong> Validation finale
            </div>
            <div class="step">
                <span class="step-number">4</span>
                <strong>Finalisé :</strong> Échéance automatiquement marquée comme payée
            </div>
        </div>
    </div>

    <div class="signature-area">
        <div class="signature-box">
            <div class="signature-line">Signature du Client</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Signature YAYE DIA BTP</div>
        </div>
    </div>

    <div class="footer">
        <p><strong>Important :</strong> Ce reçu confirme votre demande de versement.</p>
        <p>Votre paiement sera validé sous 48h ouvrables maximum.</p>
        <p style="font-size: 10px;">Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
</body>
</html>