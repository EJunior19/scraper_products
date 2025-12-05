<?php

namespace App\Services\Scraper;

use App\Models\Producto;
use App\Models\Categoria;
use App\Models\ImagenProducto;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ScraperService
{
    protected ProductExtractor $extractor;
    protected ImageDownloader  $downloader;

    public function __construct(ProductExtractor $extractor, ImageDownloader $downloader)
    {
        $this->extractor  = $extractor;
        $this->downloader = $downloader;
    }

    public function scrapeCategoria(string $urlCategoria, string $nombreCategoria): int
    {
        $categoria = Categoria::firstOrCreate([
            'nombre' => $nombreCategoria
        ]);

        $html = Http::withOptions(['verify' => false])
            ->get($urlCategoria)
            ->body();

        $linkRegex = '/https:\/\/www\.mapy\.com\.py\/produto\/[a-zA-Z0-9\-]+\/?/i';
        preg_match_all($linkRegex, $html, $matches);

        $links = array_unique($matches[0]);

        $insertados = 0;

        foreach ($links as $productUrl) {
            if ($this->scrapeProducto($productUrl, $categoria->id)) {
                $insertados++;
            }
        }

        return $insertados;
    }

    public function scrapeProducto(string $urlProducto, int $categoriaId): bool
    {
        try {
            if (Producto::where('url_producto', $urlProducto)->exists()) {
                return false;
            }

            // 👉 El extractor debe recibir la URL, NO el HTML
            $data = $this->extractor->extract($urlProducto);

            if (!$data) {
                return false;
            }

            DB::beginTransaction();

            $producto = Producto::create([
                'categoria_id' => $categoriaId,
                'nombre'       => $data['nombre'],
                'descripcion'  => $data['descripcion'],
                'precio'       => $data['precio'],
                'sku'          => $data['sku'],
                'url_producto' => $urlProducto,
                'extra_json'   => json_encode([
                    'precio_usd' => $data['precio_usd'] ?? null,
                    'precio_brl' => $data['precio_brl'] ?? null,
                    'atributos'  => $data['atributos'] ?? [],
                ])
            ]);

            // GUARDADO DE IMÁGENES
            if (!empty($data['imagenes'])) {
                foreach ($data['imagenes'] as $imgUrl) {

                    $localPath = $this->downloader->download($imgUrl);

                    if ($localPath) {
                        ImagenProducto::create([
                            'producto_id'  => $producto->id,
                            'ruta_local'   => $localPath,
                            'url_original' => $imgUrl,
                        ]);
                    }
                }
            }

            DB::commit();
            return true;

        } catch (\Throwable $e) {
            DB::rollBack();
            return false;
        }
    }
}
