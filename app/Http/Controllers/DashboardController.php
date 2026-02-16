<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();
        $organization = $user->currentOrganization();

        return Inertia::render('Dashboard', [
            'organization' => $organization,
        ]);
    }
}
