<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Mail\OrganizationInvitationMail;
use App\Models\OrganizationInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $organization = $request->user()->currentOrganization();

        if (! $organization || ! $request->user()->isAdminOf($organization)) {
            abort(403);
        }

        $members = $organization->users()
            ->withPivot('role')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->pivot->role,
            ]);

        $invitations = $organization->pendingInvitations()
            ->with('inviter:id,name')
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'email' => $i->email,
                'role' => $i->role,
                'token' => $i->token,
                'expires_at' => $i->expires_at->toISOString(),
                'inviter_name' => $i->inviter?->name,
            ]);

        return Inertia::render('Settings/Team', [
            'organization' => $organization->only(['id', 'name']),
            'members' => $members,
            'invitations' => $invitations,
            'roles' => OrganizationInvitation::roles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = $request->user()->currentOrganization();

        if (! $organization || ! $request->user()->isAdminOf($organization)) {
            abort(403);
        }

        $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'string', 'in:admin,lecteur'],
        ]);

        $email = strtolower($request->email);

        if ($organization->users()->where('email', $email)->exists()) {
            return back()->withErrors(['email' => 'Cette personne fait déjà partie de l\'équipe.']);
        }

        $existing = $organization->pendingInvitations()->where('email', $email)->first();
        if ($existing) {
            return back()->withErrors(['email' => 'Une invitation est déjà en cours pour cet email.']);
        }

        $invitation = OrganizationInvitation::create([
            'organization_id' => $organization->id,
            'email' => $email,
            'role' => $request->role,
            'token' => OrganizationInvitation::createToken(),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($email)->send(new OrganizationInvitationMail($invitation->load(['inviter', 'organization'])));

        return back()->with('success', 'Invitation envoyée à ' . $email);
    }

    public function destroy(Request $request, OrganizationInvitation $invitation): RedirectResponse
    {
        $organization = $request->user()->currentOrganization();

        if (! $organization || ! $request->user()->isAdminOf($organization)) {
            abort(403);
        }

        if ($invitation->organization_id !== $organization->id) {
            abort(404);
        }

        $invitation->delete();

        return back()->with('success', 'Invitation annulée.');
    }
}
