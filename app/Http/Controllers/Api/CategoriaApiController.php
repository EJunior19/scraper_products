<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;

class CategoriaApiController extends Controller
{
    public function index()
    {
        return response()->json(Categoria::all());
    }

    public function productos($id)
    {
        $categoria = Categoria::findOrFail($id);

        $productos = $categoria->productos()->with('imagenes')->get();

        return response()->json([
            'categoria' => $categoria->nombre,
            'total'     => $productos->count(),
            'productos' => $productos
        ]);
    }
}
