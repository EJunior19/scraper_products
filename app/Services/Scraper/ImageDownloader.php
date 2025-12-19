<?php

namespace App\Services\Scraper;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImageDownloader
{
    /**
     * Descarga UNA imagen y devuelve la ruta local
     * Ej: "productos/prod_xxx.jpg"
     */
    public function download(string $imageUrl): ?string
    {
        $paths = $this->downloadMany([$imageUrl]);
        return $paths[0] ?? null;
    }

    /**
     * Descarga VARIAS imágenes y devuelve un array de rutas locales.
     *
     * @param array $imageUrls
     * @return array
     */
    public function downloadMany(array $imageUrls): array
    {
        $result = [];

        foreach ($imageUrls as $url) {
            try {
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    continue;
                }

                $hash = md5($url);

                // Evitar descargar la misma imagen dos veces
                $existing = collect(
                    Storage::disk('public')->files('productos')
                )->first(fn ($f) => str_contains($f, $hash));

                if ($existing) {
                    $result[] = 'productos/' . basename($existing);
                    continue;
                }

                // Descargar imagen
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Catalogo Scraper Bot)'
                ])->timeout(20)->get($url);

                if (!$response->successful() || empty($response->body())) {
                    continue;
                }

                // Detectar extensión
                $contentType = $response->header('Content-Type');
                $ext = $this->guessExtension($url, $contentType);

                // Nombre final
                $fileName = "prod_{$hash}_" . uniqid() . '.' . $ext;
                $relativePath = 'productos/' . $fileName;

                // Guardar
                Storage::disk('public')->put($relativePath, $response->body());

                $result[] = $relativePath;

            } catch (\Throwable $e) {
                // seguimos con la siguiente imagen
                continue;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Detecta la extensión más adecuada.
     */
    private function guessExtension(string $url, ?string $contentType): string
    {
        // Prioridad 1: Content-Type
        if ($contentType) {
            if (str_contains($contentType, 'image/jpeg')) return 'jpg';
            if (str_contains($contentType, 'image/jpg'))  return 'jpg';
            if (str_contains($contentType, 'image/png'))  return 'png';
            if (str_contains($contentType, 'image/webp')) return 'webp';
        }

        // Prioridad 2: extensión en URL
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                return $ext === 'jpeg' ? 'jpg' : $ext;
            }
        }

        return 'jpg';
    }
}
