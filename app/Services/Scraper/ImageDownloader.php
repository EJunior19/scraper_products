<?php

namespace App\Services\Scraper;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImageDownloader
{
    /**
     * Descarga una imagen y devuelve la ruta local (ej: "productos/123abc.jpg").
     */
    public function download(string $imageUrl): ?string
    {
        try {
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                return null;
            }

            // Evitar descargar la misma imagen dos veces
            $hash = md5($imageUrl);

            // Verificar si ya existe una imagen asociada a esta URL
            $existing = collect(Storage::disk('public')->files('productos'))
                ->first(fn($f) => str_contains($f, $hash));

            if ($existing) {
                return 'productos/' . basename($existing);
            }

            // Descargar imagen
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Scraper Bot)'
            ])->get($imageUrl);

            if (!$response->successful() || empty($response->body())) {
                return null;
            }

            // Detectar extensión real
            $contentType = $response->header('Content-Type');
            $ext = $this->guessExtension($imageUrl, $contentType);

            // Nombre final del archivo
            $fileName = "prod_{$hash}_" . uniqid() . '.' . $ext;
            $relativePath = 'productos/' . $fileName;

            // Guardar en storage
            Storage::disk('public')->put($relativePath, $response->body());

            return $relativePath;

        } catch (\Throwable $e) {
            return null;
        }
    }


    /**
     * Detecta la extensión más adecuada.
     */
    private function guessExtension(string $url, ?string $contentType): string
    {
        // Prioridad 1: Content-Type HTTP
        if ($contentType) {
            if (str_contains($contentType, 'image/jpeg')) return 'jpg';
            if (str_contains($contentType, 'image/jpg'))  return 'jpg';
            if (str_contains($contentType, 'image/png'))  return 'png';
            if (str_contains($contentType, 'image/webp')) return 'webp';
        }

        // Prioridad 2: extensión en la URL
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                return $ext === 'jpeg' ? 'jpg' : $ext;
            }
        }

        // Último recurso
        return 'jpg';
    }
}
