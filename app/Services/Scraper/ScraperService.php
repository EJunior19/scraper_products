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
     * ============================================
     * MÉTODO QUE EL COMANDO ARTISAN USA
     * ============================================
     */
    public function scrapeCategoria(string $urlCategoria, string $nombreCategoria): int
    {
        return $this->scrapeCategoriaHtml($urlCategoria, $nombreCategoria);
    }

    /**
     * ============================================
     * 🔥 SCRAPEAR TODA UNA CATEGORÍA DESDE HTML
     * ============================================
     */
    public function scrapeCategoriaHtml(string $urlCategoria, string $nombreCategoria): int
    {
        $insertados = 0;

        // 1) obtener HTML de la categoría
        $resp = Http::withOptions([
            'verify' => false,
            'timeout' => 60
        ])->get($urlCategoria);

        if (!$resp->successful()) {
            return 0;
        }

        $html = $resp->body();

        // 2) extraer URLs de productos
        $productUrls = $this->extraerUrlsProductos($html);

        if (empty($productUrls)) {
            return 0;
        }

        // 3) crear categoría si no existe
        $categoria = Categoria::firstOrCreate([
            'nombre' => $nombreCategoria
        ]);

        // 4) scrapear cada producto individual
        foreach ($productUrls as $url) {
            if ($this->scrapeProductoHtml($url, $categoria->id)) {
                $insertados++;
            }
        }

        return $insertados;
    }

    /**
     * ============================================
     * EXTRAER URLs DE PRODUCTOS DESDE UNA CATEGORÍA
     * ============================================
     */
    private function extraerUrlsProductos(string $html): array
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        $urls = [];

        $nodes = $xpath->query('//ul[contains(@class,"products")]//a[@href]');
        foreach ($nodes as $node) {
            $href = $node->getAttribute('href');

            if (str_contains($href, '/produto/')) {
                $urls[] = $href;
            }
        }

        return array_unique($urls);
    }

    /**
     * ============================================
     * SCRAPEAR UN PRODUCTO COMPLETO DESDE HTML
     * ============================================
     */
    public function scrapeProductoHtml(string $urlProducto, int $categoriaId): bool
    {
        try {
            // evitar duplicados
            if (Producto::where('url_producto', $urlProducto)->exists()) {
                return false;
            }

            // obtener HTML del producto
            $resp = Http::withOptions([
                'verify' => false,
                'timeout' => 60
            ])->get($urlProducto);

            if (!$resp->successful()) {
                return false;
            }

            $data = $this->extractor->fromHtml($resp->body());

            if (!$data) {
                return false;
            }

            DB::beginTransaction();

            // crear producto
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
                    'categorias_html' => $data['categorias'],
                ]
            ]);

            // guardar imágenes
            foreach ($data['imagenes'] as $imgUrl) {
                ImagenProducto::create([
                    'producto_id'  => $producto->id,
                    'ruta_local'   => null,
                    'url_original' => $imgUrl
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
