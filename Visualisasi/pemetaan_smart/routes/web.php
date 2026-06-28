<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PetaController;
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
Route::get('/', [PetaController::class, 'index'])->name('Dashboard.index');
Route::get('/Dashboard', [PetaController::class, 'index']);
Route::get('/Dashboard/chart', [PetaController::class, 'chart'])->name('Dashboard.chart');
