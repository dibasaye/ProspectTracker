{{-- resources/views/contracts/pdf_export.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrat {{ $contract->contract_number ?? 'N/A' }}</title>
    <style>
        @page {
            size: A4;
            margin: 18mm 16mm 18mm 16mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #000;
            background: #fff;
            margin: 0;
        }

        .page {
            page-break-after: always;
            padding: 20mm 16mm;
            position: relative;
            box-sizing: border-box;
        }

        .page::before {
            content: "";
            position: absolute;
            top: 50%; left: 50%;
            width: 180mm; height: 180mm;
            background: url('{{ public_path("images/image.png") }}') no-repeat center center;
            background-size: contain;
            opacity: 0.05;
            transform: translate(-50%, -50%);
            z-index: 0;
        }

        .header img {
            width: 100%;
            max-height: 40mm;
            margin-bottom: 10mm;
        }

        .title {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10mm 0;
        }

        .article { font-weight: bold; margin: 5mm 0 2mm; }
        p { text-align: justify; margin: 0 0 4mm; }
        .indent { text-indent: 8mm; }
        .center { text-align: center; }
        .small { font-size: 11pt; }
        .tiny { font-size: 10pt; }

        .kv {
            display: grid;
            grid-template-columns: 55mm 1fr;
            gap: 2mm 4mm;
            margin: 5mm 0;
        }

        .footer {
            text-align: center;
            font-size: 10pt;
            margin-top: 10mm;
        }
        .footer img {
            width: 100%;
            max-height: 12mm;
            margin-bottom: 3mm;
        }

        .pageno {
            text-align: center;
            font-size: 10pt;
            margin-top: 5mm;
        }
    </style>
</head>
<body>
<div class="container">
    {{-- HEADER --}}
    @if(!empty($images['header_image']))
        <div class="header">
            <img src="{{ $images['header_image'] }}" alt="Logo YAYE DIA BTP">
        </div>
    @endif

    {{-- CONTENT --}}
    @if(($content_source ?? 'default') === 'default')
        <div class="default-content">
            {{-- =================== PAGE 1 =================== --}}
            <section class="page">
                <h1 class="title">Contrat de réservation</h1>

                <p class="center"><b>ENTRE LES SOUSSIGNÉS :</b></p>
                <p class="indent">
                    La société <b>YAYE DIA BTP</b>, représentée par Madame <b>Fatou Faye</b>...
                </p>

                <p class="center"><i>Ci-après dénommée "Promoteur"</i></p>
                <p class="center"><b>Et</b></p>

                <div class="kv">
                    <div><b>Nom :</b></div> <div>{{ $contract->client->full_name ?? 'N/A' }}</div>
                    <div><b>Adresse :</b></div> <div>{{ $contract->client->address ?? 'N/A' }}</div>
                    <div><b>Téléphone :</b></div> <div>{{ $contract->client->phone ?? 'N/A' }}</div>
                </div>
                <p class="center"><i>Ci-après dénommé(e) "le Client"</i></p>

                <p class="article">IL A ÉTÉ EXPOSÉ CE QUI SUIT :</p>
                <p>La société YAYE DIA BTP est spécialisée dans ...</p>

                <div class="footer">
                    @if(!empty($images['footer_image']))
                        <img src="{{ $images['footer_image'] }}" alt="Footer">
                    @endif
                    <p>RCCM: SN DKR ... – NINEA : 011440188<br>
                       www.groupeyaye.com</p>
                </div>
                <div class="pageno">- 1 -</div>
            </section>

            {{-- =================== PAGE 2 =================== --}}
            <section class="page">
                <h3 class="article">Article 1 : Objet du contrat</h3>
                <p>La société YAYE DIA BTP est une société spécialisée dans l'intermédiation et les prestations de
                 services immobiliers. YAYE DIA BTP propose des produits de qualité garantissant la conformité
                 aux standards les plus élevés de sa profession afin de répondre ainsi aux exigences de sa clientèle.</p>

                <p>
              Dans le cadre de ses activités et fort de son expérience, YAYE DIA BTP apporte son expertise et son
              savoir-faire dans le domaine de la promotion immobilière et foncière. C'est ainsi qu'elle offre plusieurs+    services, notamment la viabilisation, l'aménagement, la réalisation de projets immobiliers ou encore la+    commercialisation des biens ou de terrains
           </p>
           <p>
      L'extrait de Délibération N° 002 du 26-01-2019 du Conseil Municipal de Keur Moussa relative à
      l'affectation de terre du domaine national sise à LELO SERERE d'une superficie de 50 ha 00 ca,
      extrait des 324 HA 69 a 80 ca et établis par l'arrêté N° 046/AKM/SP portant le projet de
      lotissement dudit village pour sa restructuration.
      Un protocole a été signé avec la promotrice Madame Fatou Faye gérante de la société YAYE DIA BTP+   pour la réalisation du protocole d'Accord du 27 Novembre 2020 prévoyant la restructuration dudit
      village. En compensation de la réalisation de ce projet la marie de la commune de Keur Moussa cède
     à la société YAYE DIA BTP 30% des parcelles loties.
 </p>


                <h3 class="article">Article 2 : Désignation du terrain</h3>
                <p>...</p>

                <h3 class="article">Article 3 : Réservation</h3>
                <p>...</p>

                <h3 class="article">Article 4 : Conditions financières</h3>
                <p>Prix : {{ number_format($contract->total_amount ?? 0, 0, ',', ' ') }} FCFA</p>

                <div class="footer">
                    @if(!empty($images['footer_image']))
                        <img src="{{ $images['footer_image'] }}" alt="Footer">
                    @endif
                    <p>yayediasarl@gmail.com</p>
                </div>
                <div class="pageno">- 2 -</div>
            </section>

            {{-- =================== PAGE 3 =================== --}}
            <section class="page">
                <h3 class="article">Article 8 : Résolution du contrat</h3>
                <p>...</p>

                <h3 class="article">Article 9 : Intérêts de retard</h3>
                <p>...</p>

                <h3 class="article">Article 10 : Réalisation de la vente</h3>
                <p>...</p>

                <h3 class="article">Article 11 : Données personnelles</h3>
                <p>...</p>

                <div class="footer">
                    @if(!empty($images['footer_image']))
                        <img src="{{ $images['footer_image'] }}" alt="Footer">
                    @endif
                </div>
                <div class="pageno">- 3 -</div>
            </section>
        </div>
    @else
        {{-- Contenu personnalisé --}}
        <div class="custom-content">
            {!! $custom_content ?? '<p>Aucun contenu disponible.</p>' !!}
        </div>
    @endif
</div>
</body>
</html>
