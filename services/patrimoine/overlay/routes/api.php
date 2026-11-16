<?php

use App\Http\Controllers\BienController;
use App\Http\Controllers\EngagementFinancierController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::get('categories', [BienController::class, 'publicCategories']);
    Route::get('biens', [BienController::class, 'publicIndex']);
    Route::get('biens/{bien}', [BienController::class, 'publicShow'])->whereNumber('bien');
});

Route::middleware('token')->group(function () {
    Route::get('categories', [BienController::class, 'categories']);
    Route::get('biens', [BienController::class, 'index']);
    Route::get('biens/{bien}', [BienController::class, 'show'])->whereNumber('bien');
    Route::get('biens/stats', [BienController::class, 'stats']);
    Route::get('biens/export', [BienController::class, 'export']);
    Route::get('engagements-financiers', [EngagementFinancierController::class, 'index']);
    Route::get('engagements-financiers/{engagement}', [EngagementFinancierController::class, 'show']);
});

Route::middleware('token:admin_central,admin,gestionnaire')->group(function () {
    Route::post('biens', [BienController::class, 'store']);
    Route::put('biens/{bien}', [BienController::class, 'update']);
    Route::post('engagements-financiers', [EngagementFinancierController::class, 'store']);
    Route::put('engagements-financiers/{engagement}', [EngagementFinancierController::class, 'update']);
});

Route::post('categories', [BienController::class, 'storeCategorie'])->middleware('token:admin_central');

Route::middleware('token:admin_central,admin')->group(function () {
    Route::delete('biens/{bien}', [BienController::class, 'destroy']);
    Route::delete('engagements-financiers/{engagement}', [EngagementFinancierController::class, 'destroy']);
});
