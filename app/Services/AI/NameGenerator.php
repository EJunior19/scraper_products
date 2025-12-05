<?php

namespace App\Services\AI;

use OpenAI;

class NameGenerator
{
    public function generarNombre(string $nombreOriginal): string
    {
        try {
            $client = OpenAI::client(env('OPENAI_API_KEY'));

            $prompt = "
Genera un nombre elegante, corto y comercial para este perfume: '{$nombreOriginal}'.
Reglas:
- NO repitas el nombre original.
- NO uses comillas.
- NO uses asteriscos ni formato markdown.
- Devuelve SOLO el nombre del perfume, nada más.
- Mantén la capacidad en ml si existe.
- No añadas explicaciones, solo la propuesta final.
Ejemplos:
Black Velvet 100ml
Royal Breeze 125ml
Oasis Imperial 90ml
";

            $respuesta = $client->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Eres un experto en naming premium para perfumes.'],
                    ['role' => 'user', 'content' => $prompt]
                ]
            ]);

            $nombre = trim($respuesta['choices'][0]['message']['content']);

            // ======================================================
            // 🔥 SANITIZAR TODO EL TEXTO: SIN COMILLAS, SIN MARKDOWN
            // ======================================================

            // 1️⃣ Tomar solo la primera línea (por si manda explicación)
            $nombre = preg_split("/\r\n|\r|\n/", $nombre)[0];

            // 2️⃣ Quitar markdown (**texto**, *texto*)
            $nombre = str_replace(['**', '*'], '', $nombre);

            // 3️⃣ Quitar TODAS las comillas comunes y raras
            $nombre = str_replace(
                ['"', "'", '“', '”', '‘', '’', '«', '»', '`', '´'],
                '',
                $nombre
            );

            // 4️⃣ Quitar frases típicas de IA
            $nombre = preg_replace('/^(claro|aquí tienes|te propongo|propuesta|nombre sugerido|sugerencia).*/i', '', $nombre);

            // 5️⃣ Normalizar espacios dobles
            $nombre = preg_replace('/\s+/', ' ', $nombre);

            return trim($nombre);

        } catch (\Throwable $e) {
            return $nombreOriginal;
        }
    }
}
