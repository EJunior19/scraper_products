<?php

namespace App\Services\Scraper;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ImagenProducto;
use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;

class ScraperService
{
    public function __construct(
        protected ProductExtractor $productExtractor,
        protected ImageDownloader $imageDownloader
    ) {}

    /**
     * Scrapea una categoría completa (una página).
     * Si después quieres agregar paginación, aquí es donde se manejaría.
     */
    public function scrapearCategoria(string $urlCategoria, ?string $nombre = null): void
    {
        // 1) Crear/obtener categoría
        $categoria = Categoria::firstOrCreate(
            ['url' => $urlCategoria],
            ['nombre' => $nombre ?: 'Categoría Scrap']
        );

        // 2) Descargar HTML de la categoría
        $html = Http::get($urlCategoria)->body();

        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        // 🔧 IMPORTANTE:
        // Ajustá el selector según el ecommerce:
        // Aquí buscamos <a> que tengan clase product-link o similar.
        $productLinkNodes = $xpath->query('//a[contains(@class, "product") or contains(@class, "product-link")]');

        // Si no encuentra nada, como fallback buscamos todos los enlaces que
        // contengan "/producto" en la URL (ajustable).
        if ($productLinkNodes->length === 0) {
            $productLinkNodes = $xpath->query('//a[contains(@href, "producto")]');
        }

        $baseUrl = $this->getBaseUrl($urlCategoria);

        foreach ($productLinkNodes as $node) {
            $href = $node->getAttribute('href');
            if (!$href) {
                continue;
            }

            $productUrl = $this->makeAbsoluteUrl($href, $baseUrl);

            // Evitar duplicar productos
            if (Producto::where('url_producto', $productUrl)->exists()) {
                continue;
            }

            $data = $this->productExtractor->extract($productUrl);
            if (!$data) {
                continue;
            }

            // Crear producto
            $producto = Producto::create([
                'categoria_id' => $categoria->id,
                'nombre'       => $data['nombre'],
                'descripcion'  => $data['descripcion'],
                'precio'       => $data['precio'],
                'sku'          => $data['sku'],
                'url_producto' => $productUrl,
                'extra_json'   => $data['extra'] ?? [],
            ]);

            // Guardar imágenes
            foreach ($data['imagenes'] as $imageUrl) {
                $rutaLocal = $this->imageDownloader->download(
                    $this->makeAbsoluteUrl($imageUrl, $baseUrl)
                );

                if ($rutaLocal) {
                    ImagenProducto::create([
                        'producto_id' => $producto->id,
                        'ruta_local'  => $rutaLocal,
                        'url_original'=> $imageUrl,
                    ]);
                }
            }
        }
    }

    protected function getBaseUrl(string $url): string
    {
        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? 'https';
        $host   = $parts['host']   ?? '';
        return $scheme . '://' . $host;
    }

    protected function makeAbsoluteUrl(string $href, string $baseUrl): string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        if (str_starts_with($href, '//')) {
            return 'https:' . $href;
        }

        if (str_starts_with($href, '/')) {
            return rtrim($baseUrl, '/') . $href;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
    }
}
