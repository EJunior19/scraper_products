<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::with('imagenes', 'categoria')
            ->orderByDesc('id');

        if ($search = $request->get('q')) {
            $query->where('nombre', 'ILIKE', '%' . $search . '%');
        }

        $productos = $query->paginate(24);

        return view('productos.index', compact('productos'));
    }

    public function show($id)
    {
        $producto = Producto::with('imagenes', 'categoria')
            ->findOrFail($id);

        return view('productos.show', compact('producto'));
    }
}
