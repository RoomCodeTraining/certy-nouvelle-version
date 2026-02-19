<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DigitalController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\Referential\VehicleBrandController;
use App\Http\Controllers\Referential\VehicleModelController;
use App\Http\Controllers\BordereauController;
use App\Http\Controllers\CertyIaController;
use App\Http\Controllers\Settings\OrganizationCompanyConfigController;
use App\Http\Controllers\Settings\CertyIaSettingsController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

// Point d'entrée : redirection vers login (invités) ou dashboard (connectés)
Route::get('/', [RedirectController::class, 'home'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('register', [RedirectController::class, 'registerRedirect']);
    Route::post('register', [RedirectController::class, 'registerRedirect']);
});

Route::middleware(['auth', 'ensure.organization'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, '__invoke'])->name('dashboard');
    Route::get('/api/statistics', [\App\Http\Controllers\Api\StatisticsController::class, '__invoke'])->name('api.statistics');

    // Certy IA — assistant (question/réponse sur clients, véhicules, contrats, bordereaux)
    Route::get('/certy-ia', [CertyIaController::class, 'index'])->name('certy-ia.index');
    Route::post('/certy-ia/ask', [CertyIaController::class, 'ask'])->name('certy-ia.ask');

    Route::middleware('root')->group(function () {
        Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('settings.profile');
        Route::put('/settings/profile', [ProfileController::class, 'update']);
        Route::get('/settings/config', [OrganizationCompanyConfigController::class, 'index'])->name('settings.config');
        Route::post('/settings/config', [OrganizationCompanyConfigController::class, 'update'])->name('settings.config.update');
        Route::delete('/settings/config/{config}', [OrganizationCompanyConfigController::class, 'destroy'])->name('settings.config.destroy');
        Route::get('/settings/certy-ia', [CertyIaSettingsController::class, 'edit'])->name('settings.certy-ia.edit');
        Route::put('/settings/certy-ia', [CertyIaSettingsController::class, 'update'])->name('settings.certy-ia.update');
    });

    // CRUD Clients
    Route::resource('clients', ClientController::class);

    // CRUD Véhicules : formulaire création avec choix du client (ou pré-rempli depuis fiche client)
    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
    Route::get('/clients/{client}/vehicles/create', [RedirectController::class, 'vehiclesCreateForClient'])->name('vehicles.create.for_client');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::post('/vehicles/quick-store', [VehicleController::class, 'quickStore'])->name('vehicles.quick-store');
    Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
    Route::get('/vehicles/{vehicle}/edit', [VehicleController::class, 'edit'])->name('vehicles.edit');
    Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');

    // CRUD Contrats
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/export', [ContractController::class, 'export'])->name('contracts.export');
    Route::get('/contracts/create', [ContractController::class, 'create'])->name('contracts.create');
    Route::post('/contracts/preview', [ContractController::class, 'preview'])->name('contracts.preview');
    Route::post('/contracts', [ContractController::class, 'store'])->name('contracts.store');
    Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');
    Route::get('/contracts/{contract}/pdf', [ContractController::class, 'pdf'])->name('contracts.pdf');
    Route::get('/contracts/{contract}/renew', [ContractController::class, 'renew'])->name('contracts.renew');
    Route::get('/contracts/{contract}/edit', [ContractController::class, 'edit'])->name('contracts.edit');
    Route::put('/contracts/{contract}', [ContractController::class, 'update'])->name('contracts.update');
    Route::post('/contracts/{contract}/validate', [ContractController::class, 'validate'])->name('contracts.validate');
    Route::post('/contracts/{contract}/cancel', [ContractController::class, 'cancel'])->name('contracts.cancel');
    Route::post('/contracts/{contract}/mark-attestation-issued', [ContractController::class, 'markAttestationIssued'])->name('contracts.mark-attestation-issued');
    Route::post('/contracts/{contract}/generate-attestation', [ContractController::class, 'generateAttestation'])->name('contracts.generate-attestation');

    // Bordereaux (compagnie + période du -> au) — réservé aux utilisateurs root
    Route::middleware('root')->group(function () {
        Route::get('/bordereaux', [BordereauController::class, 'index'])->name('bordereaux.index');
        Route::get('/bordereaux/create', [BordereauController::class, 'create'])->name('bordereaux.create');
        Route::post('/bordereaux', [BordereauController::class, 'store'])->name('bordereaux.store');
        Route::get('/bordereaux/{bordereau}', [BordereauController::class, 'show'])->name('bordereaux.show');
        Route::post('/bordereaux/{bordereau}/validate', [BordereauController::class, 'validate'])->name('bordereaux.validate');
        Route::delete('/bordereaux/{bordereau}', [BordereauController::class, 'destroy'])->name('bordereaux.destroy');
        Route::get('/bordereaux/{bordereau}/pdf', [BordereauController::class, 'pdf'])->name('bordereaux.pdf');
        Route::get('/bordereaux/{bordereau}/excel', [BordereauController::class, 'excel'])->name('bordereaux.excel');
    });

    // Référentiel : Marques et Modèles véhicules
    Route::get('/referential/brands', [VehicleBrandController::class, 'index'])->name('referential.brands.index');
    Route::get('/referential/brands/create', [VehicleBrandController::class, 'create'])->name('referential.brands.create');
    Route::post('/referential/brands', [VehicleBrandController::class, 'store'])->name('referential.brands.store');
    Route::get('/referential/brands/{brand}/edit', [VehicleBrandController::class, 'edit'])->name('referential.brands.edit');
    Route::put('/referential/brands/{brand}', [VehicleBrandController::class, 'update'])->name('referential.brands.update');
    Route::delete('/referential/brands/{brand}', [VehicleBrandController::class, 'destroy'])->name('referential.brands.destroy');

    Route::get('/referential/models', [VehicleModelController::class, 'index'])->name('referential.models.index');
    Route::get('/referential/models/create', [VehicleModelController::class, 'create'])->name('referential.models.create');
    Route::post('/referential/models', [VehicleModelController::class, 'store'])->name('referential.models.store');
    Route::get('/referential/models/{vehicle_model}/edit', [VehicleModelController::class, 'edit'])->name('referential.models.edit');
    Route::put('/referential/models/{vehicle_model}', [VehicleModelController::class, 'update'])->name('referential.models.update');
    Route::delete('/referential/models/{vehicle_model}', [VehicleModelController::class, 'destroy'])->name('referential.models.destroy');

    // Digital (service externe ASACI)
    Route::get('/digital/attestations', [DigitalController::class, 'attestations'])->name('digital.attestations');
    Route::get('/digital/attestations/{reference}/download', [DigitalController::class, 'downloadAttestation'])->name('digital.attestations.download');
    Route::get('/digital/attestations/{reference}/download-url', [DigitalController::class, 'downloadUrlAttestation'])->name('digital.attestations.download_url');
    Route::get('/digital/attestations/{reference}/view', [DigitalController::class, 'viewAttestation'])->name('digital.attestations.view');
    Route::middleware('root')->group(function () {
        Route::get('/digital/rattachements', [DigitalController::class, 'rattachements'])->name('digital.rattachements');
        Route::get('/digital/stock', [DigitalController::class, 'stock'])->name('digital.stock');
        Route::get('/digital/stock/create', [DigitalController::class, 'createTransaction'])->name('digital.stock.create');
        Route::post('/digital/transactions', [DigitalController::class, 'storeTransactionRequest'])->name('digital.transactions.store');
        Route::post('/digital/transactions/{reference}/cancel', [DigitalController::class, 'cancelTransaction'])->name('digital.transactions.cancel');
        Route::get('/digital/bureaux', [DigitalController::class, 'bureaux'])->name('digital.bureaux');
        Route::get('/digital/bureaux/create', [DigitalController::class, 'createBureau'])->name('digital.bureaux.create');
        Route::post('/digital/bureaux', [DigitalController::class, 'storeBureau'])->name('digital.bureaux.store');
        Route::get('/digital/bureaux/{id}/edit', [DigitalController::class, 'editBureau'])->name('digital.bureaux.edit');
        Route::put('/digital/bureaux/{id}', [DigitalController::class, 'updateBureau'])->name('digital.bureaux.update');
        Route::post('/digital/bureaux/{id}/enable', [DigitalController::class, 'enableBureau'])->name('digital.bureaux.enable');
        Route::post('/digital/bureaux/{id}/disable', [DigitalController::class, 'disableBureau'])->name('digital.bureaux.disable');
        Route::get('/digital/utilisateurs', [DigitalController::class, 'utilisateurs'])->name('digital.utilisateurs');
        Route::get('/digital/utilisateurs/create', [DigitalController::class, 'createUtilisateur'])->name('digital.utilisateurs.create');
        Route::post('/digital/utilisateurs', [DigitalController::class, 'storeUtilisateur'])->name('digital.utilisateurs.store');
        Route::get('/digital/utilisateurs/{id}/edit', [DigitalController::class, 'editUtilisateur'])->name('digital.utilisateurs.edit');
        Route::put('/digital/utilisateurs/{id}', [DigitalController::class, 'updateUtilisateur'])->name('digital.utilisateurs.update');
        Route::post('/digital/utilisateurs/{id}/enable', [DigitalController::class, 'enableUtilisateur'])->name('digital.utilisateurs.enable');
        Route::post('/digital/utilisateurs/{id}/disable', [DigitalController::class, 'disableUtilisateur'])->name('digital.utilisateurs.disable');
    });
});
