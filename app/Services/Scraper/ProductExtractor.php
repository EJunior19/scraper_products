<?php

namespace App\Services\Scraper;

use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;

class ProductExtractor
{
    /**
     * Extraer datos de un producto desde el HTML (NO desde URL).
     * Ya estás enviando el HTML desde ScraperService.
     */
    public function extraer(string $html): ?array
    {
        try {
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            // ===========================
            // 1️⃣ NOMBRE DEL PRODUCTO
            // ===========================
            $nombre = trim($xpath->evaluate('string(//h1[contains(@class, "product_title")])'));

            // ===========================
            // 2️⃣ PRECIO PRINCIPAL (GUARANÍES)
            // ===========================
            $priceRaw = trim($xpath->evaluate('string(//p[contains(@class, "price")]//bdi)'));
            $precio = $this->parsePrecio($priceRaw);

            // ===========================
            // 3️⃣ PRECIO USD & BRL (div valor-promo)
            // Ejemplo: "$18,47 / R$ 101,22"
            // ===========================
            $promoText = trim($xpath->evaluate('string(//div[@valor-promo])'));
            $precio_usd = null;
            $precio_brl = null;

            if ($promoText !== '') {
                // Extraer USD
                if (preg_match('/\$(\d+[.,]?\d*)/', $promoText, $m)) {
                    $precio_usd = $this->parsePrecio($m[1]);
                }

                // Extraer BRL
                if (preg_match('/R\$\s?(\d+[.,]?\d*)/', $promoText, $m)) {
                    $precio_brl = $this->parsePrecio($m[1]);
                }
            }

            // ===========================
            // 4️⃣ DESCRIPCIÓN DEL PRODUCTO
            // ===========================
            $descripcion = trim($xpath->evaluate('string(//div[contains(@class,"woocommerce-product-details__short-description")])'));

            if ($descripcion === '') {
                // Descripción larga si existe
                $descripcion = trim($xpath->evaluate('string(//div[contains(@class,"woocommerce-Tabs-panel--description")])'));
            }

            // ===========================
            // 5️⃣ SKU / INFORMACIONES ADICIONALES
            // ===========================
            $sku = null;
            $atributos = [];

            // Tabla "Informaciones adicionales"
            $rows = $xpath->query('//table[contains(@class,"woocommerce-product-attributes")]//tr');

            foreach ($rows as $tr) {
                $label = trim($xpath->evaluate('string(./th)', $tr));
                $value = trim($xpath->evaluate('string(./td)', $tr));

                if ($label !== '' && $value !== '') {
                    $atributos[$label] = $value;

                    // Detectar SKU si está en la tabla
                    if (stripos($label, 'SKU') !== false) {
                        $sku = $value;
                    }
                }
            }

            // ===========================
            // 6️⃣ IMÁGENES DEL PRODUCTO
            // ===========================
            $imagenes = [];
            $nodes = $xpath->query('//div[contains(@class,"woocommerce-product-gallery")]//img');

            foreach ($nodes as $img) {
                $src = $img->getAttribute('data-large_image') ?: $img->getAttribute('src');
                if ($src && !in_array($src, $imagenes)) {
                    $imagenes[] = $src;
                }
            }

            // Si no encuentra imágenes, fallback genérico
            if (empty($imagenes)) {
                $nodes = $xpath->query('//img');
                foreach ($nodes as $img) {
                    $src = $img->getAttribute('src');
                    if ($src && !in_array($src, $imagenes)) {
                        $imagenes[] = $src;
                    }
                }
            }

            // Validación mínima válida
            if ($nombre === '') {
                return null;
            }

            return [
                'nombre'      => $nombre,
                'precio'      => $precio,
                'precio_usd'  => $precio_usd,
                'precio_brl'  => $precio_brl,
                'descripcion' => $descripcion,
                'sku'         => $sku,
                'imagenes'    => $imagenes,
                'atributos'   => $atributos,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Normaliza números que vienen como:
     *  149.352 → 149352
     *  18,47 → 18.47
     */
    protected function parsePrecio(string $texto): ?float
    {
        if ($texto === '') return null;

        $clean = preg_replace('/[^0-9.,]/', '', $texto);
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? floatval($clean) : null;
    }
}
