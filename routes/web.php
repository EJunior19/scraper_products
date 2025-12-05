<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ScraperController;

// Catálogo
Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
Route::get('/productos/{id}', [ProductoController::class, 'show'])->name('productos.show');

// Scraping desde el panel
Route::post('/productos/scrapear', [ScraperController::class, 'scrapear'])->name('productos.scrapear');
