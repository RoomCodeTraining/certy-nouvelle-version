<?php

namespace App\Http\Controllers;

use App\Models\OrganizationInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(Request $request, string $token): Response|RedirectResponse
    {
        $invitation = OrganizationInvitation::where('token', $token)->with('organization', 'inviter')->firstOrFail();

        if ($invitation->accepted_at) {
            return redirect()->route('dashboard')->with('info', 'Cette invitation a déjà été acceptée.');
        }

        if ($invitation->isExpired()) {
            return redirect()->route('home')->with('error', 'Cette invitation a expiré.');
        }

        $user = $request->user();

        if ($user) {
            if (strtolower($user->email) !== strtolower($invitation->email)) {
                return Inertia::render('Invitation/Show', [
                    'invitation' => [
                        'token' => $token,
                        'email' => $invitation->email,
                        'organization_name' => $invitation->organization->name,
                        'inviter_name' => $invitation->inviter?->name,
                    ],
                    'authenticated' => true,
                    'error' => 'Cette invitation a été envoyée à ' . $invitation->email . '. Connectez-vous avec ce compte pour accepter.',
                ]);
            }
        }

        return Inertia::render('Invitation/Show', [
            'invitation' => [
                'token' => $token,
                'email' => $invitation->email,
                'organization_name' => $invitation->organization->name,
                'inviter_name' => $invitation->inviter?->name,
            ],
            'authenticated' => (bool) $user,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = OrganizationInvitation::where('token', $token)->with('organization')->firstOrFail();

        if ($invitation->accepted_at) {
            return redirect()->route('dashboard')->with('info', 'Cette invitation a déjà été acceptée.');
        }

        if ($invitation->isExpired()) {
            return redirect()->route('home')->with('error', 'Cette invitation a expiré.');
        }

        $user = $request->user();

        if (! $user) {
            return redirect()->route('register', ['invitation' => $token])
                ->with('info', 'Créez un compte pour rejoindre ' . $invitation->organization->name . '.');
        }

        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return back()->withErrors(['email' => 'Cette invitation a été envoyée à un autre email.']);
        }

        $user->organizations()->attach($invitation->organization_id, [
            'role' => $invitation->role,
        ]);

        $invitation->update(['accepted_at' => now()]);

        return redirect()->route('dashboard')->with('success', 'Vous avez rejoint ' . $invitation->organization->name . '.');
    }
}
