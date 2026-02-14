<?php

use App\Http\Controllers\AssistantController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\Settings\OrganizationController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SubscriptionController;
use App\Http\Controllers\Settings\TeamController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])
    ->middleware('auth')
    ->name('invitations.accept');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::middleware('onboarding.completed')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::get('/assistant', AssistantController::class)->name('assistant');
        Route::post('/assistant/ask', [AssistantController::class, 'ask'])->name('assistant.ask');
        Route::get('/settings/organization', [OrganizationController::class, 'edit'])->name('settings.organization');
        Route::put('/settings/organization', [OrganizationController::class, 'update']);
        Route::get('/settings/team', [TeamController::class, 'index'])->name('settings.team');
        Route::post('/settings/team', [TeamController::class, 'store']);
        Route::delete('/settings/team/{invitation}', [TeamController::class, 'destroy']);
        Route::get('/settings/subscription', [SubscriptionController::class, 'edit'])->name('settings.subscription');
        Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('settings.profile');
        Route::put('/settings/profile', [ProfileController::class, 'update']);
    });

    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});
