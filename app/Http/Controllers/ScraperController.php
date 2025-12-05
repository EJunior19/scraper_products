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
            return back()->with('error', 'Error procesando la URL. Verifique la página.');
        }
    }
}
