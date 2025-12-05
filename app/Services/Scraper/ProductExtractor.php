<?php

namespace App\Services\Scraper;

use OpenAI; // 👈 IA para renombrar productos

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
                return null; // producto inválido
            }

            // Formatear CamelCase
            $nombreFormateado = $this->formatearNombre($nombreOriginal);

            // Generar nombre alternativo usando IA
            $nombreIA = $this->generarNombreConIA($nombreFormateado);

            // ====================================
            // 2️⃣ PRECIO EN GUARANÍES
            // ====================================
            $precio_gs = null;
            if (isset($item['prices']['price'])) {
                $precio_gs = $this->parseNumber($item['prices']['price']);
            }

            // ====================================
            // 3️⃣ SKU
            // ====================================
            $sku = $item['sku'] ?? null;

            // ====================================
            // 4️⃣ DESCRIPCIÓN
            // ====================================
            $descripcion = $item['description'] ?? null;

            // ====================================
            // 5️⃣ IMÁGENES
            // ====================================
            $imagenes = [];
            if (!empty($item['images'])) {
                foreach ($item['images'] as $img) {
                    if (!empty($img['src'])) {
                        $imagenes[] = $img['src'];
                    }
                }
            }

            // ====================================
            // 6️⃣ PRECIOS USD/BRL
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
                'nombre'        => $nombreIA,          // 👈 nombre final generado por IA
                'nombre_base'   => $nombreFormateado,  // 👈 opcional: guardar nombre formateado
                'nombre_raw'    => $nombreOriginal,    // 👈 opcional: guardar nombre real del e-commerce
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
     * Llama a OpenAI para generar un nombre alternativo atractivo.
     */
    private function generarNombreConIA(string $nombre): string
    {
        try {
            $client = OpenAI::client(env('OPENAI_API_KEY'));

            $prompt = "
Crea un nuevo nombre atractivo, elegante y diferente para este perfume: '{$nombre}'.
No repitas exactamente el nombre original.
Debe sonar comercial, profesional y usable en un catálogo de perfumes.
Mantén la capacidad en ml si aparece.
Ejemplo de estilo:
'Invictus 100ml – Fragancia Masculina'
'One Million – Edición 100ml'
";

            $respuesta = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un experto en branding y nombres comerciales.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]);

            return trim($respuesta['choices'][0]['message']['content']);

        } catch (\Throwable $e) {
            // Si la IA falla, usamos nombre formateado
            return $nombre;
        }
    }

    /**
     * Formatea el nombre a Camel Case
     */
    private function formatearNombre(?string $nombre): ?string
    {
        if (!$nombre) return null;

        // 1️⃣ minúsculas
        $nombre = mb_strtolower($nombre, 'UTF-8');

        // 2️⃣ eliminar espacios múltiples
        $nombre = preg_replace('/\s+/', ' ', $nombre);

        // 3️⃣ CamelCase
        $nombre = mb_convert_case($nombre, MB_CASE_TITLE, "UTF-8");

        // 4️⃣ siglas a mayúsculas
        $siglas = ['Edt', 'Edp', 'Vip', 'Eau'];
        foreach ($siglas as $sigla) {
            $nombre = preg_replace_callback(
                "/\b{$sigla}\b/i",
                fn($m) => strtoupper($m[0]),
                $nombre
            );
        }

        return trim($nombre);
    }

    /**
     * Normaliza precios numéricos.
     */
    private function parseNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = preg_replace('/[^0-9.,]/', '', $value);
        $clean = str_replace('.', '', $clean);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? floatval($clean) : null;
    }
}
