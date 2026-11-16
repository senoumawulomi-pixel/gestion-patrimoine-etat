<?php

use App\Http\Controllers\MouvementController;
use Illuminate\Support\Facades\Route;

Route::get('mouvements', [MouvementController::class, 'index'])->middleware('token');
Route::post('mouvements', [MouvementController::class, 'store'])->middleware('token:admin_central,admin,gestionnaire');
