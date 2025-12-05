<?php

namespace App\Services\Scraper;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImageDownloader
{
    /**
     * Descarga una imagen y devuelve la ruta local relativa (ej: "productos/abc123.jpg").
     */
    public function download(string $imageUrl): ?string
    {
        try {
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                return null;
            }

            $response = Http::get($imageUrl);

            if (!$response->successful()) {
                return null;
            }

            $ext = 'jpg';
            $parsed = parse_url($imageUrl, PHP_URL_PATH);
            if ($parsed) {
                $pathInfo = pathinfo($parsed);
                if (!empty($pathInfo['extension'])) {
                    $ext = strtolower($pathInfo['extension']);
                }
            }

            $fileName = uniqid('prod_', true) . '.' . $ext;
            $relativePath = 'productos/' . $fileName;

            Storage::disk('public')->put($relativePath, $response->body());

            return $relativePath;
        } catch (\Throwable $e) {
            // Podés loguear si querés
            return null;
        }
    }
}
