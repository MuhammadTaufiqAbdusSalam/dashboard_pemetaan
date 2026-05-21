<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\AnalisisController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/peta/data', [PetaController::class, 'getData']);

// API Analisis Stabilitas Harga AI
Route::get('/analisis/stabilitas', [AnalisisController::class, 'getAnalisis']);
Route::post('/analisis/refresh-narasi', [AnalisisController::class, 'refreshNarasi']);
