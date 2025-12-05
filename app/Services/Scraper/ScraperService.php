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
     * SCRAPEAR AUTOMÁTICAMENTE SOLO "PERFUMERÍA"
     * ===========================================================
     */
    public function scrapePerfumeria(): int
    {
        $insertados = 0;
        $page = 1;

        do {
            $response = Http::withOptions([
                'verify'  => false,
                'timeout' => 60,
            ])
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0',
                'Accept'     => 'application/json',
                'Referer'    => 'https://www.mapy.com.py/',
                'Origin'     => 'https://www.mapy.com.py',
            ])
            ->get('https://www.mapy.com.py/wp-json/wc/store/v1/products', [
                'per_page' => 100,
                'page'     => $page,
            ]);

            if (!$response->successful()) {
                break;
            }

            $items = $response->json();
            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {

                // ---------------------------------------------
                // 🔥 1) Detectar si el producto es de PERFUMERÍA
                // ---------------------------------------------
                $esPerfumeria = false;
                $categoriaNombre = "Perfumería";

                foreach ($item['categories'] as $cat) {
                    if (strtolower($cat['slug']) === 'perfumeria') {
                        $esPerfumeria = true;
                        $categoriaNombre = $cat['name']; // ejemplo: Perfumería
                        break;
                    }
                }

                if (!$esPerfumeria) {
                    continue;
                }

                // ---------------------------------------------
                // 🔥 2) Crear o encontrar categoría automáticamente
                // ---------------------------------------------
                $categoria = Categoria::firstOrCreate([
                    'nombre' => $categoriaNombre
                ]);

                // ---------------------------------------------
                // 🔥 3) Guardar producto
                // ---------------------------------------------
                if ($this->scrapeProductoDesdeApi($item, $categoria->id)) {
                    $insertados++;
                }
            }

            $page++;

        } while (true);

        return $insertados;
    }

    /**
     * ===========================================================
     * GUARDAR PRODUCTO INDIVIDUAL
     * ===========================================================
     */
    public function scrapeProductoDesdeApi(array $item, int $categoriaId): bool
    {
        try {
            $urlProducto = $item['permalink'];

            if (Producto::where('url_producto', $urlProducto)->exists()) {
                return false;
            }

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
                'precio'       => $data['precio_gs'],
                'sku'          => $data['sku'],
                'url_producto' => $urlProducto,
                'extra_json'   => [
                    'precio_usd' => $data['precio_usd'],
                    'precio_brl' => $data['precio_brl'],
                    'atributos'  => $data['atributos'],
                ],
            ]);

            // Guardar imágenes
            foreach ($data['imagenes'] as $imgUrl) {
                ImagenProducto::create([
                    'producto_id'  => $producto->id,
                    'ruta_local'   => null,
                    'url_original' => $imgUrl,
                ]);
            }

            DB::commit();
            return true;

        } catch (\Throwable $e) {
            DB::rollBack();
            return false;
        }
    }
}
