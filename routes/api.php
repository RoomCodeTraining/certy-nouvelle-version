<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
|
| Authentification via ASACI : le frontend envoie email/password à Laravel,
| Laravel appelle l'API ASACI (auth/tokens puis auth/user), sync le user
| local et renvoie un token Sanctum au frontend.
|
*/

Route::post('/v1/login', LoginController::class)->name('api.v1.login');
