<?php

// use App\Http\Controllers\HubspotContactController;

use App\Http\Controllers\HubspotContactController as ControllersHubspotContactController;
use App\Http\Controllers\HubSpotController;
use App\Http\Controllers\ProfileController;
use App\Modules\HubSpot\Controllers\HubspotAccountController;
use App\Modules\HubSpot\Controllers\HubspotContactController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Route::get('/hubspot/authenticate', [HubSpotController::class, 'authenticate'])->name('hubspot.authenticate');
    // Route::get('/hubspot/callback', [HubSpotController::class, 'callback'])->name('hubspot.callback');
    // Route::get('/hubspot/accounts', [HubSpotController::class, 'listAccounts'])->name('hubspot.accounts');
    // Route::get('/hubspot/account/{id}', [HubSpotController::class, 'viewAccount'])->name('hubspot.account.view');

    // Route::get('/hubspot/contacts', [HubspotContactController::class, 'index'])->name('hubspot.contacts.index');
    // Route::post('/hubspot/contacts/import', [ControllersHubspotContactController::class, 'import'])->name('hubspot.contacts.import');
    // Route::post('/hubspot/contacts', [HubspotContactController::class, 'store'])->name('hubspot.contacts.store');
    // Route::put('/hubspot/contacts/{id}', [HubspotContactController::class, 'update'])->name('hubspot.contacts.update');
    // Route::delete('/hubspot/contacts/{id}', [HubspotContactController::class, 'destroy'])->name('hubspot.contacts.destroy');

    Route::prefix('hubspot')->group(function () {
        Route::get('/', [HubspotAccountController::class, 'index'])->name('hubspot.index');
        Route::get('/authenticate', [HubspotAccountController::class, 'authenticate'])->name('hubspot.authenticate');
        Route::get('/callback', [HubspotAccountController::class, 'callback'])->name('hubspot.callback');
        Route::get('/accounts', [HubspotAccountController::class, 'getAccounts'])->name('hubspot.accounts');
        
        Route::get('/{id}/contacts', [HubspotContactController::class, 'index'])->name('hubspot.contacts.index');
        Route::post('/contacts/import', [HubspotContactController::class, 'import'])->name('hubspot.contacts.import');
        Route::post('/contacts', [HubspotContactController::class, 'store'])->name('hubspot.contacts.store');
        Route::put('/contacts/{id}', [HubspotContactController::class, 'update'])->name('hubspot.contacts.update');
        Route::delete('/contacts/{id}', [HubspotContactController::class, 'destroy'])->name('hubspot.contacts.destroy');
    });

   

});

require __DIR__.'/auth.php';
