<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Scraper\ScraperService;

class ScraperController extends Controller
{
    public function scrapear(Request $request, ScraperService $scraper)
    {
        $request->validate([
            'url' => 'required|url',
            'categoria' => 'required|string|max:255'
        ]);

        $url = $request->url;
        $categoria = $request->categoria;

        try {
            $cantidad = $scraper->scrapeCategoria($url, $categoria);

            return back()->with('success', "Scraping completado. Productos insertados: {$cantidad}");
        } catch (\Throwable $e) {

            // 👉 Guardar detalles del error para debugging
            \Log::error("Scraper error: " . $e->getMessage(), [
                'url' => $url,
                'categoria' => $categoria,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Ocurrió un error durante el scraping. Revisa el log para más detalles.');
        }
    }
}
