<?php

/**
 * Exemple de routes web pour la migration fullstack Inertia.
 * À adapter selon vos contrôleurs et middlewares (check.inactivity, external.sso).
 * Utiliser HashId pour les modèles si vous utilisez deligoez/laravel-model-hashid.
 */

use App\Http\Controllers\Web\BrokerSettingsController;
use App\Http\Controllers\Web\ClientController;
use App\Http\Controllers\Web\ContractController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\VehicleController;
use App\Http\Controllers\Web\BordereauController;
use App\Http\Controllers\Web\ProductionController;
use App\Http\Controllers\Web\ReportingController;
use App\Http\Controllers\Web\AccountingEntryController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

// Auth (invités)
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store']);
});

// Auth (connectés) + inactivité
Route::middleware(['auth', 'check.inactivity'])->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Clients
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

    // Véhicules
    Route::get('/clients/{client}/vehicles', [VehicleController::class, 'getClientVehicles'])->name('clients.vehicles');
    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
    Route::get('/vehicles/{vehicle}/edit', [VehicleController::class, 'edit'])->name('vehicles.edit');
    Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');

    // Contrats
    Route::get('/contrats', [ContractController::class, 'index'])->name('contrats.index');
    Route::get('/contrats/create', [ContractController::class, 'create'])->name('contrats.create');
    Route::post('/contrats', [ContractController::class, 'store'])->name('contrats.store');
    Route::get('/contrats/{contract}', [ContractController::class, 'show'])->name('contrats.show');
    Route::get('/contrats/{contract}/edit', [ContractController::class, 'edit'])->name('contrats.edit');
    Route::put('/contrats/{contract}', [ContractController::class, 'update'])->name('contrats.update');
    Route::delete('/contrats/{contract}', [ContractController::class, 'destroy'])->name('contrats.destroy');
    Route::post('/contrats/{contract}/validate', [ContractController::class, 'validate'])->name('contrats.validate');
    Route::post('/contrats/{contract}/cancel', [ContractController::class, 'cancel'])->name('contrats.cancel');
    Route::post('/contrats/{contract}/renew', [ContractController::class, 'renew'])->name('contrats.renew');
    Route::post('/contrats/{contract}/endorse', [ContractController::class, 'endorse'])->name('contrats.endorse');
    Route::get('/contrats/{contract}/printed-pdf', [ContractController::class, 'printedPdf'])->name('contrats.printedPdf');
    Route::get('/contrats/{contract}/download-pdf', [ContractController::class, 'downloadPdf'])->name('contrats.downloadPdf');

    // Bordereaux
    Route::resource('bordereaux', BordereauController::class);
    Route::post('/bordereaux/{bordereau}/submit', [BordereauController::class, 'submit'])->name('bordereaux.submit');
    Route::post('/bordereaux/{bordereau}/approve', [BordereauController::class, 'approve'])->name('bordereaux.approve');
    Route::post('/bordereaux/{bordereau}/reject', [BordereauController::class, 'reject'])->name('bordereaux.reject');
    Route::post('/bordereaux/{bordereau}/mark-as-paid', [BordereauController::class, 'markAsPaid'])->name('bordereaux.markAsPaid');
    Route::get('/bordereaux/{bordereau}/generate-pdf', [BordereauController::class, 'generatePdf'])->name('bordereaux.generatePdf');

    // Productions
    Route::get('/productions', [ProductionController::class, 'index'])->name('productions.index');
    Route::get('/productions/summary', [ProductionController::class, 'summary'])->name('productions.summary');
    Route::get('/productions/export', [ProductionController::class, 'export'])->name('productions.export');

    // Reporting
    Route::get('/reporting/contracts-overview', [ReportingController::class, 'contractsOverview'])->name('reporting.contracts-overview');
    Route::get('/reporting/statistics-by-period', [ReportingController::class, 'statisticsByPeriod'])->name('reporting.statistics-by-period');
    Route::get('/reporting/expiring-contracts', [ReportingController::class, 'expiringContracts'])->name('reporting.expiring-contracts');

    // Comptabilité
    Route::resource('accounting-entries', AccountingEntryController::class)->names('accounting_entries');

    // Paramètres courtier
    Route::get('/settings/broker', [BrokerSettingsController::class, 'edit'])->name('broker-settings.edit');
    Route::put('/settings/broker', [BrokerSettingsController::class, 'update'])->name('broker-settings.update');
});
