<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ScraperController;

Route::get('/productos', [ProductoController::class, 'index'])
    ->name('productos.index');

Route::get('/productos/{id}', [ProductoController::class, 'show'])
    ->name('productos.show');

Route::post('/scrapear', [ScraperController::class, 'scrapear'])
    ->name('scrapear.categoria');