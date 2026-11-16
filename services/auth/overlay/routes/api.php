<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::get('me', [AuthController::class, 'me']);
Route::get('users', [AuthController::class, 'users']);
Route::post('users', [AuthController::class, 'createUser']);
Route::get('organisations', [AuthController::class, 'organisations']);
Route::post('organisations', [AuthController::class, 'createOrganisation']);
