    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #000;
            background: #fff;
            line-height: 1.25;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        @page { size: A4; margin: 12mm; }
        @media print {
            body { margin: 0; padding: 0; }
            .page-container { page-break-inside: avoid; }
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 4rem;
            font-weight: bold;
            color: {{ $greenColor }};
            opacity: 0.06;
            z-index: 0;
            pointer-events: none;
            white-space: nowrap;
            letter-spacing: 0.1em;
        }
        .container { padding: 6px 12px 8px; max-width: 100%; position: relative; z-index: 1; }
        .header {
            border-bottom: 1px solid {{ $greenColor }};
            margin-bottom: 4px;
            padding-bottom: 4px;
        }
        .header-top { display: table; width: 100%; }
        .header-top > div { display: table-cell; vertical-align: middle; }
        .header-logo { width: 15%; text-align: center; }
        .header-logo img { height: 52px; max-width: 90px; object-fit: contain; }
        .header-title { width: 70%; text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; color: {{ $greenColor }}; }
        .header-compagnie { width: 15%; text-align: right; }
        .header-compagnie img { height: 40px; max-width: 80px; object-fit: contain; }
        .header-meta { display: table; width: 100%; margin-top: 2px; font-size: 9px; }
        .header-meta > div { display: table-cell; vertical-align: top; }
        .header-meta .left { text-align: left; width: 50%; }
        .header-meta .right { text-align: right; width: 50%; }
        .header-meta span.fw { font-weight: 600; }
        .main-title { text-align: center; margin-bottom: 4px; }
        .main-title h2 { font-size: 11px; font-weight: bold; text-transform: uppercase; color: {{ $greenColor }}; }
        .main-title .sub { font-size: 9px; color: #666; margin-top: 1px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th, td { border: 1px solid {{ $greenColor }}; padding: 3px 4px; text-align: left; }
        th { font-weight: 600; background: #f9fafb; color: {{ $greenColor }}; }
        .table-info { margin-bottom: 4px; }
        .section-title {
            background: {{ $greenColor }};
            color: #fff;
            padding: 3px 6px;
            font-size: 9px;
            font-weight: 600;
            border-radius: 2px 2px 0 0;
        }
        .section-table { border-top: none; }
        .two-cols { display: table; width: 100%; margin-bottom: 4px; }
        .col-left { display: table-cell; width: 60%; padding-right: 6px; vertical-align: top; }
        .col-right { display: table-cell; width: 40%; vertical-align: top; }
        .block { margin-bottom: 3px; }
        .signatures { display: table; width: 100%; margin-top: 6px; font-size: 9px; }
        .signatures > div { display: table-cell; width: 50%; }
        .signatures .left { text-align: left; }
        .signatures .right { text-align: right; }
        .signatures .fw { font-weight: bold; }
        .signatures .mt { margin-top: 2px; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid {{ $greenColor }};
            text-align: center;
            font-size: 8px;
            padding: 4px 8px;
            background: #fff;
        }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .row-highlight { font-weight: bold; background: #f1f5f9 !important; }
        .row-total { font-weight: bold; background: #e2e8f0 !important; font-size: 10px; }
    </style>
