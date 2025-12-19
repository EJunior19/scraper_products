<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;

class ProductoApiController extends Controller
{
    // GET /api/productos
    public function index()
    {
        $productos = Producto::with('imagenes', 'categoria')
            ->paginate(50);

        return response()->json($productos);
    }

    // GET /api/productos/{id}
    public function show($id)
    {
        $producto = Producto::with('imagenes', 'categoria')->findOrFail($id);

        return response()->json($producto);
    }

    /**
     * Exportación masiva para sincronización automática del ecommerce
     * GET /api/sync/export
     */
    public function export()
    {
        $productos = Producto::with('imagenes', 'categoria')->get();

        return response()->json([
            'total' => $productos->count(),
            'fecha_actualizacion' => now()->toDateTimeString(),
            'productos' => $productos,
        ]);
    }
}
