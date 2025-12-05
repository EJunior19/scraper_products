<?php

namespace App\Services\Scraper;

use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;

class ProductExtractor
{
    /**
     * Extrae datos de un producto desde la URL.
     *
     * Devuelve un array:
     * [
     *   'nombre'      => string,
     *   'precio'      => float|null,
     *   'descripcion' => string|null,
     *   'sku'         => string|null,
     *   'imagenes'    => [ 'https://...', ... ],
     *   'extra'       => [ ... ]
     * ]
     */
    public function extract(string $productUrl): ?array
    {
        try {
            $html = Http::get($productUrl)->body();

            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            // 🔧 IMPORTANTE:
            // Ajustá estos selectores según el e-commerce real que vayas a scrapear.

            // Nombre (título h1)
            $nombre = trim($xpath->evaluate('string(//h1)'));

            // Precio (span con clase "price" o similar)
            $precioRaw = $xpath->evaluate('string(//span[contains(@class, "price")])');
            $precio = $this->parsePrecio($precioRaw);

            // Descripción (div con clase description, details, etc.)
            $descripcion = trim($xpath->evaluate('string(//div[contains(@class, "description")])'));

            // SKU (si existe algún span con sku)
            $sku = trim($xpath->evaluate('string(//span[contains(@class, "sku")])'));
            if ($sku === '') {
                $sku = null;
            }

            // Imágenes (ej: img con clase "product-image")
            $imagenes = [];
            $nodes = $xpath->query('//img[contains(@class, "product-image")]');
            foreach ($nodes as $node) {
                $src = $node->getAttribute('src');
                if ($src) {
                    $imagenes[] = $src;
                }
            }

            // Si no encontró imágenes con esa clase, probamos una genérica
            if (empty($imagenes)) {
                $nodes = $xpath->query('//img');
                foreach ($nodes as $node) {
                    $src = $node->getAttribute('src');
                    if ($src && !in_array($src, $imagenes)) {
                        $imagenes[] = $src;
                    }
                }
            }

            if ($nombre === '' && empty($imagenes)) {
                // Página rara o no es producto
                return null;
            }

            return [
                'nombre'      => $nombre,
                'precio'      => $precio,
                'descripcion' => $descripcion ?: null,
                'sku'         => $sku,
                'imagenes'    => $imagenes,
                'extra'       => [
                    'precio_raw' => $precioRaw,
                ],
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function parsePrecio(string $texto): ?float
    {
        if ($texto === '') {
            return null;
        }

        // Quitamos todo lo que no es número, coma o punto
        $clean = preg_replace('/[^0-9.,]/', '', $texto);

        // Si tiene coma y punto, intentamos adaptar,
        // pero como concepto: reemplazar coma por nada y dejar punto como decimal
        $clean = str_replace(['.'], '', $clean);    // sacamos separador de miles
        $clean = str_replace([','], '.', $clean);   // coma por punto decimal

        if ($clean === '') {
            return null;
        }

        return floatval($clean);
    }
}
