<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Reporting prêt</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #334155;">
    <p>L'export des attestations externes est prêt.</p>
    @if($dateFrom && $dateTo)
        <p>Période : du {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</p>
    @endif
    <p><strong>{{ $rowCount }}</strong> attestation(s) exportée(s).</p>
    <p>Le fichier Excel est joint à cet email.</p>
</body>
</html>
