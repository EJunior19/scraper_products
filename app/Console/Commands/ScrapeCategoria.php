<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Scraper\ScraperService;

class ScrapeCategoria extends Command
{
    protected $signature = 'scrape:categoria {url} {nombre?}';
    protected $description = 'Scrapea una categoría de e-commerce y guarda productos en la base de datos';

    public function handle(ScraperService $scraperService): int
    {
        $url = $this->argument('url');
        $nombre = $this->argument('nombre');

        $this->info("Iniciando scraping de la categoría:");
        $this->info("URL: {$url}");
        if ($nombre) {
            $this->info("Nombre: {$nombre}");
        }

        $scraperService->scrapearCategoria($url, $nombre);

        $this->info("✅ Scraping finalizado.");
        return self::SUCCESS;
    }
}
