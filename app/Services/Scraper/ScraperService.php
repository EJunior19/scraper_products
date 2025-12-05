<?php

namespace App\Services\Scraper;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\ImagenProducto;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ScraperService
{
    protected ProductExtractor $extractor;

    public function __construct(ProductExtractor $extractor)
    {
        $this->extractor = $extractor;
    }

    /**
     * ===========================================================
     *  SCRAPEAR UNA CATEGORÍA COMPLETA USANDO LA STORE API
     * ===========================================================
     */
    public function scrapeCategoria(string $urlCategoria, string $nombreCategoria): int
    {
        // Guardar o recuperar categoría
        $categoria = Categoria::firstOrCreate(['nombre' => $nombreCategoria]);

        // Página 1 (se puede mejorar con paginación futura)
        $response = Http::withOptions(['verify' => false])
            ->get('https://www.mapy.com.py/wp-json/wc/store/products', [
                'per_page' => 100,
                'page'     => 1,
            ]);

        if (!$response->successful()) {
            return 0;
        }

        $items = $response->json(); // array de productos JSON

        $insertados = 0;

        foreach ($items as $item) {
            if ($this->scrapeProductoDesdeApi($item, $categoria->id)) {
                $insertados++;
            }
        }

        return $insertados;
    }

    /**
     * ===========================================================
     *  PROCESAR UN PRODUCTO INDIVIDUAL DESDE LA STORE API
     * ===========================================================
     */
    public function scrapeProductoDesdeApi(array $item, int $categoriaId): bool
    {
        try {
            $urlProducto = $item['permalink'];

            // Evitar duplicados
            if (Producto::where('url_producto', $urlProducto)->exists()) {
                return false;
            }

            // Normalizar datos usando el extractor
            $data = $this->extractor->fromApi($item);

            if (!$data) {
                return false;
            }

            DB::beginTransaction();

            // Crear producto
            $producto = Producto::create([
                'categoria_id' => $categoriaId,
                'nombre'       => $data['nombre'],
                'descripcion'  => $data['descripcion'],
                'precio'       => $data['precio_gs'], // GUARANÍES
                'sku'          => $data['sku'],
                'url_producto' => $urlProducto,
                'extra_json'   => [
                    'precio_usd' => $data['precio_usd'] ?? null,
                    'precio_brl' => $data['precio_brl'] ?? null,
                    'atributos'  => $data['atributos'] ?? [],
                ],
            ]);

            // Guardar imágenes (SOLO URL, NO descargar)
            if (!empty($data['imagenes'])) {
                foreach ($data['imagenes'] as $imgUrl) {
                    ImagenProducto::create([
                        'producto_id'  => $producto->id,
                        'ruta_local'   => null,
                        'url_original' => $imgUrl,
                    ]);
                }
            }

            DB::commit();
            return true;

        } catch (\Throwable $e) {
            DB::rollBack();
            return false;
        }
    }
}
