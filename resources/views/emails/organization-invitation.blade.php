<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitation Baaro</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #334155; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 0 auto; padding: 24px; }
        .btn { display: inline-block; padding: 12px 24px; background: #0f172a; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; }
        .muted { color: #64748b; font-size: 13px; margin-top: 24px; }
        .link { color: #0f172a; word-break: break-all; }
    </style>
</head>
<body>
    <div class="container">
        <p>Bonjour,</p>
        <p><strong>{{ $invitation->inviter?->name ?? 'Un membre' }}</strong> vous invite à rejoindre <strong>{{ $invitation->organization->name }}</strong> sur Baaro.</p>
        <p>Baaro est une plateforme d'archivage intelligent pour les PME.</p>
        <p style="margin: 24px 0;">
            <a href="{{ url('/invitations/' . $invitation->token) }}" class="btn">Accepter l'invitation</a>
        </p>
        <p class="muted">
            Cette invitation expire le {{ $invitation->expires_at->format('d/m/Y') }}.
        </p>
        <p class="muted">
            Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
            <a href="{{ url('/invitations/' . $invitation->token) }}" class="link">{{ url('/invitations/' . $invitation->token) }}</a>
        </p>
    </div>
</body>
</html>
