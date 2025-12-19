<?php

namespace App\Services\Scraper;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;

class ProductExtractor
{
    /**
     * ================================================
     *  M O D O   A P I
     * ================================================
     */
    public function fromApi(array $item): ?array
    {
        try {
            $nombreOriginal = $item['name'] ?? null;
            if (!$nombreOriginal) return null;

            $nombreLimpio = $this->limpiarNombreProducto($nombreOriginal);
            $nombreFormateado = $this->formatearNombre($nombreLimpio);

            $precio_gs = $item['prices']['price'] ?? null;
            $precio_gs = $this->parseNumber($precio_gs);

            $sku = $item['sku'] ?? null;
            $descripcion = $item['description'] ?? null;

            // Imagen API
            $imagen = $this->getProductImage($item);
            $imagenes = $imagen ? [$imagen] : [];

            // Precios USD y BRL
            $precio_usd = null;
            $precio_brl = null;

            if (!empty($item['price_html'])) {
                $html = $item['price_html'];

                if (preg_match('/\$(\d+[.,]?\d*)/', $html, $m)) {
                    $precio_usd = $this->parseNumber($m[1]);
                }

                if (preg_match('/R\$\s?(\d+[.,]?\d*)/', $html, $m)) {
                    $precio_brl = $this->parseNumber($m[1]);
                }
            }

            return [
                'nombre'        => $nombreFormateado,
                'nombre_base'   => $nombreFormateado,
                'nombre_raw'    => $nombreOriginal,
                'precio_gs'     => $precio_gs,
                'precio_usd'    => $precio_usd,
                'precio_brl'    => $precio_brl,
                'descripcion'   => $descripcion,
                'sku'           => $sku,
                'imagenes'      => $imagenes,
                'atributos'     => $item['attributes'] ?? [],
            ];

        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * ========================================================
     *  M O D O   H T M L
     * ========================================================
     */
    public function fromHtml(string $html): ?array
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        // NOMBRE
        $nombreNode = $xpath->query('//h1[contains(@class,"product_title")]')->item(0);
        if (!$nombreNode) return null;

        $nombreOriginal = trim($nombreNode->textContent);
        $nombreLimpio = $this->limpiarNombreProducto($nombreOriginal);
        $nombreFormateado = $this->formatearNombre($nombreLimpio);

        // PRECIO GS
        $priceNode = $xpath->query('//p[@class="price"]//bdi')->item(0);
        $precio_gs = $priceNode ? $this->parseNumber($priceNode->textContent) : null;

        // PRECIO USD / BRL
        $usdNode = $xpath->query('//div[contains(@class,"font-14")]')->item(0);
        $precio_usd = null;
        $precio_brl = null;

        if ($usdNode) {
            $text = $usdNode->textContent;

            if (preg_match('/\$(\d+[.,]?\d*)/', $text, $m)) {
                $precio_usd = $this->parseNumber($m[1]);
            }

            if (preg_match('/R\$\s?(\d+[.,]?\d*)/', $text, $m)) {
                $precio_brl = $this->parseNumber($m[1]);
            }
        }

        // SKU
        $skuNode = $xpath->query('//span[@class="sku"]')->item(0);
        $sku = $skuNode ? trim($skuNode->textContent) : null;

        // CATEGORÍAS
        $categorias = [];
        foreach ($xpath->query('//span[contains(@class,"posted_in")]//a') as $cat) {
            $categorias[] = trim($cat->textContent);
        }

        // IMÁGENES (mejor calidad posible)
        $imagenes = [];

        foreach ($xpath->query('//img[contains(@class,"wp-post-image")]') as $img) {

            $full = $img->getAttribute('data-large_image');
            if ($full) {
                $imagenes[] = $full;
                continue;
            }

            $dataSrc = $img->getAttribute('data-src');
            if ($dataSrc) {
                $imagenes[] = $dataSrc;
                continue;
            }

            $srcset = $img->getAttribute('srcset');
            if ($srcset) {
                $urls = explode(',', $srcset);
                if (!empty($urls)) {
                    $first = trim(explode(' ', $urls[count($urls) - 1])[0]);
                    if ($first) {
                        $imagenes[] = $first;
                        continue;
                    }
                }
            }

            $src = $img->getAttribute('src');
            if ($src) {
                $imagenes[] = $src;
            }
        }

        // DESCRIPCIÓN
        $descNode = $xpath->query('//div[@id="tab-description"]')->item(0);
        $descripcion = $descNode ? trim($descNode->textContent) : null;

        return [
            'nombre'        => $nombreFormateado,
            'nombre_base'   => $nombreFormateado,
            'nombre_raw'    => $nombreOriginal,
            'precio_gs'     => $precio_gs,
            'precio_usd'    => $precio_usd,
            'precio_brl'    => $precio_brl,
            'descripcion'   => $descripcion,
            'sku'           => $sku,
            'imagenes'      => $imagenes,
            'categorias'    => $categorias,
            'atributos'     => [],
        ];
    }

    /* ============================== */
    /* Helpers                        */
    /* ============================== */

    private function limpiarNombreProducto(string $texto): string
    {
        $texto = preg_replace('/^para un perfume como\s*/i', '', $texto);
        $texto = preg_replace('/["“”]/', '', $texto);
        $texto = preg_split(
            '/(un nombre elegante|que refleja|ideal para|este perfume|podría ser)/i',
            $texto
        )[0];

        return trim(preg_replace('/\s+/', ' ', $texto));
    }

    private function getProductImage(array $product): ?string
    {
        if (!empty($product['featured_media'])) {
            $media = Http::withOptions(['verify' => false])
                ->get("https://www.mapy.com.py/wp-json/wp/v2/media/" . $product['featured_media'])
                ->json();

            return $media['media_details']['sizes']['full']['source_url'] ?? null;
        }

        if (!empty($product['yoast_head']) &&
            preg_match('/og:image" content="([^"]+)"/', $product['yoast_head'], $match)) {
            return $match[1];
        }

        return $product['yoast_head_json']['og_image'][0]['url'] ?? null;
    }

    private function formatearNombre(?string $nombre): ?string
    {
        if (!$nombre) return null;
        $nombre = mb_strtolower($nombre, 'UTF-8');
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        return mb_convert_case($nombre, MB_CASE_TITLE, 'UTF-8');
    }

    private function parseNumber($value): ?float
    {
        if (!$value) return null;

        $clean = preg_replace('/[^0-9.,]/', '', $value);
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float) $clean : null;
    }
}
