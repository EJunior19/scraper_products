<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Rutas del panel del scraper
Route::get('/productos', [App\Http\Controllers\ProductoController::class, 'index']);
Route::get('/productos/{id}', [App\Http\Controllers\ProductoController::class, 'show']);
