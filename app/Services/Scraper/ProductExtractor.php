<?php

namespace App\Services\Scraper;

use OpenAI;
use Illuminate\Support\Facades\Http;

class ProductExtractor
{
    /**
     * Normaliza la respuesta JSON de la Store API de WooCommerce
     * y la convierte en un array listo para insertar en DB.
     */
    public function fromApi(array $item): ?array
    {
        try {

            // ====================================
            // 1️⃣ NOMBRE ORIGINAL
            // ====================================
            $nombreOriginal = $item['name'] ?? null;
            if (!$nombreOriginal) {
                return null;
            }

            // Formatear en CamelCase
            $nombreFormateado = $this->formatearNombre($nombreOriginal);

            // Nombre alternativo generado por IA
            $nombreIA = $this->generarNombreConIA($nombreFormateado);

            // ====================================
            // 2️⃣ PRECIO GUARANÍES
            // ====================================
            $precio_gs = isset($item['prices']['price'])
                ? $this->parseNumber($item['prices']['price'])
                : null;

            // ====================================
            // 3️⃣ SKU
            // ====================================
            $sku = $item['sku'] ?? null;

            // ====================================
            // 4️⃣ DESCRIPCIÓN
            // ====================================
            $descripcion = $item['description'] ?? null;

            // ====================================
            // 5️⃣ IMAGEN (Mejor fuente disponible)
            // ====================================
            $imagen = $this->getProductImage($item);
            $imagenes = $imagen ? [$imagen] : [];

            // ====================================
            // 6️⃣ PRECIOS USD / BRL
            // ====================================
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

            // ====================================
            // 7️⃣ ATRIBUTOS
            // ====================================
            $atributos = [];
            if (!empty($item['attributes'])) {
                foreach ($item['attributes'] as $attr) {
                    $label = $attr['name'] ?? null;
                    $value = implode(', ', $attr['terms'] ?? []);

                    if ($label && $value) {
                        $atributos[$label] = $value;
                    }
                }
            }

            return [
                'nombre'        => $nombreIA,
                'nombre_base'   => $nombreFormateado,
                'nombre_raw'    => $nombreOriginal,
                'precio_gs'     => $precio_gs,
                'precio_usd'    => $precio_usd,
                'precio_brl'    => $precio_brl,
                'descripcion'   => $descripcion,
                'sku'           => $sku,
                'imagenes'      => $imagenes,
                'atributos'     => $atributos,
            ];

        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * ===========================================
     * 🔥 NUEVO: OBTENER IMAGEN OFICIAL DEL PRODUCTO
     * ===========================================
     */
    private function getProductImage(array $product): ?string
    {
        // 1️⃣ featured_media → mejor calidad
        if (!empty($product['featured_media'])) {

            $media = Http::withOptions(['verify' => false])
                ->get("https://www.mapy.com.py/wp-json/wp/v2/media/" . $product['featured_media'])
                ->json();

            if (!empty($media['media_details']['sizes']['full']['source_url'])) {
                return $media['media_details']['sizes']['full']['source_url'];
            }
        }

        // 2️⃣ og:image desde yoast_head
        if (!empty($product['yoast_head'])) {
            if (preg_match('/og:image" content="([^"]+)"/', $product['yoast_head'], $match)) {
                return $match[1];
            }
        }

        // 3️⃣ og_image desde yoast_head_json
        if (!empty($product['yoast_head_json']['og_image'][0]['url'])) {
            return $product['yoast_head_json']['og_image'][0]['url'];
        }

        return null;
    }

    /**
     * Genera un nombre alternativo con IA.
     */
    private function generarNombreConIA(string $nombre): string
    {
        try {
            $client = OpenAI::client(env('OPENAI_API_KEY'));

            $prompt = "
Crea un nombre elegante y comercial para este perfume: '{$nombre}'.
No uses frases largas ni comillas.
Mantén los ml si aparecen.
Devuelve solo el nombre final.
";

            $respuesta = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un experto en branding de perfumes.'],
                    ['role' => 'user', 'content' => $prompt]
                ]
            ]);

            return trim($respuesta['choices'][0]['message']['content']);

        } catch (\Throwable $e) {
            return $nombre;
        }
    }

    /**
     * Formatea nombre a CamelCase.
     */
    private function formatearNombre(?string $nombre): ?string
    {
        if (!$nombre) return null;

        $nombre = mb_strtolower($nombre, 'UTF-8');
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        $nombre = mb_convert_case($nombre, MB_CASE_TITLE, "UTF-8");

        return trim($nombre);
    }

    /**
     * Normaliza números de precio.
     */
    private function parseNumber($value): ?float
    {
        if ($value === null || $value === '') return null;

        $clean = preg_replace('/[^0-9.,]/', '', $value);
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? floatval($clean) : null;
    }
}
