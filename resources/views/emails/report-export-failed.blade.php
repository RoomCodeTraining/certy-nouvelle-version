<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Reporting — échec</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #334155;">
    <p>L'export automatique des attestations externes (Reporting) n'a pas pu être effectué.</p>
    <p><strong>Raison :</strong> {{ $reason }}</p>
    @if($period)
        <p><strong>Période concernée :</strong> {{ $period }}</p>
    @endif
    <p class="mt-4">
        <strong>Action requise :</strong> Connectez-vous à l'application avec un compte root pour renouveler la session externe (ASACI).
        Tant que la session n'est pas renouvelée, les exports automatiques resteront bloqués.
    </p>
</body>
</html>
