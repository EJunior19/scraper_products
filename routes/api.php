<?php
use App\Http\Controllers\Api\ProductoApiController;
use App\Http\Controllers\Api\CategoriaApiController;

Route::get('/productos', [ProductoApiController::class, 'index']);
Route::get('/productos/{id}', [ProductoApiController::class, 'show']);

Route::get('/categorias', [CategoriaApiController::class, 'index']);
Route::get('/categorias/{id}/productos', [CategoriaApiController::class, 'productos']);

Route::get('/sync/export', [ProductoApiController::class, 'export']);
