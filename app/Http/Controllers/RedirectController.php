<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectController extends Controller
{
    public function home(): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return redirect()->route('login');
    }

    public function registerRedirect(): RedirectResponse
    {
        return redirect()->route('login');
    }

    public function vehiclesCreateForClient(Request $request, Client $client): RedirectResponse
    {
        if (! Client::accessibleBy($request->user())->where('id', $client->id)->exists()) {
            abort(403);
        }
        return redirect()->route('vehicles.create', ['client_id' => $client->id]);
    }
}
