<?php

use App\Http\Controllers\GatewayController;
use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [WebController::class, 'loginForm'])->name('login');
Route::post('/login', [WebController::class, 'login']);
Route::post('/logout', [WebController::class, 'logout'])->name('logout');

Route::any('gateway/{service}/{path}', GatewayController::class)->where('path', '.*');

Route::get('/', [WebController::class, 'publicIndex'])->name('public.home');
Route::get('/public/biens/{id}', [WebController::class, 'publicShow'])->name('public.biens.show');

Route::middleware('session.auth')->group(function () {
    Route::get('/dashboard', [WebController::class, 'dashboard'])->name('dashboard');
    Route::get('/biens', [WebController::class, 'index'])->name('biens.index');
    Route::get('/biens/export', [WebController::class, 'export'])->name('biens.export');
    Route::get('/biens/create', [WebController::class, 'create'])->name('biens.create');
    Route::post('/biens', [WebController::class, 'store'])->name('biens.store');
    Route::get('/biens/{id}', [WebController::class, 'show'])->name('biens.show');
    Route::get('/biens/{id}/edit', [WebController::class, 'edit'])->name('biens.edit');
    Route::put('/biens/{id}', [WebController::class, 'update'])->name('biens.update');
    Route::delete('/biens/{id}', [WebController::class, 'destroy'])->name('biens.destroy');
    Route::post('/biens/{id}/mouvements', [WebController::class, 'mouvement'])->name('biens.mouvement');

    Route::get('/finances', [WebController::class, 'finances'])->name('finances.index');
    Route::get('/finances/create', [WebController::class, 'createFinance'])->name('finances.create');
    Route::post('/finances', [WebController::class, 'storeFinance'])->name('finances.store');
    Route::get('/finances/{id}/edit', [WebController::class, 'editFinance'])->name('finances.edit');
    Route::put('/finances/{id}', [WebController::class, 'updateFinance'])->name('finances.update');
    Route::delete('/finances/{id}', [WebController::class, 'destroyFinance'])->name('finances.destroy');

    Route::get('/organisations', [WebController::class, 'organisations'])->name('organisations.index');
    Route::post('/organisations', [WebController::class, 'storeOrganisation'])->name('organisations.store');
    Route::get('/utilisateurs', [WebController::class, 'users'])->name('users.index');
    Route::post('/utilisateurs', [WebController::class, 'storeUser'])->name('users.store');
});
