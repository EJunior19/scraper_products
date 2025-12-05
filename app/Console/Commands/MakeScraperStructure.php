<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeScraperStructure extends Command
{
    protected $signature = 'make:scraper-structure';
    protected $description = 'Crea toda la estructura base del scraper (modelos, controladores, servicios, vistas, rutas y archivos).';

    public function handle()
    {
        $this->info("🚧 Creando estructura del Scraper...\n");

        // 1) Crear carpetas necesarias
        $folders = [
            app_path('Services/Scraper'),
            resource_path('views/productos'),
            storage_path('app/public/productos'),
        ];

        foreach ($folders as $folder) {
            if (!File::exists($folder)) {
                File::makeDirectory($folder, 0777, true);
                $this->info("📁 Carpeta creada: $folder");
            }
        }

        // 2) Crear modelos
        $this->callSilent('make:model', ['name' => 'Producto']);
        $this->callSilent('make:model', ['name' => 'Categoria']);
        $this->callSilent('make:model', ['name' => 'ImagenProducto']);
        $this->info("📄 Modelos creados");

        // 3) Crear controlador
        $this->callSilent('make:controller', ['name' => 'ProductoController']);
        $this->info("📄 ProductoController creado");

        // 4) Crear comando de Scraping
        $this->callSilent('make:command', ['name' => 'ScrapeCategoria']);
        $this->info("⚙️ Comando ScrapeCategoria creado");

        // 5) Crear archivos del servicio Scraper
        $serviceFiles = [
            'ScraperService.php',
            'ProductExtractor.php',
            'ImageDownloader.php'
        ];

        foreach ($serviceFiles as $file) {
            $path = app_path("Services/Scraper/$file");
            File::put($path, "<?php\n\n// TODO: implementar clase $file\n");
            $this->info("📄 Archivo creado: $path");
        }

        // 6) Crear vistas
        File::put(
            resource_path('views/productos/index.blade.php'),
            "<h1>Lista de productos</h1>\n{{-- TODO: mostrar productos --}}"
        );

        File::put(
            resource_path('views/productos/show.blade.php'),
            "<h1>Detalle del producto</h1>\n{{-- TODO: mostrar información y fotos --}}"
        );

        $this->info("📄 Vistas creadas");

        // 7) Agregar rutas al panel
        $routesToAdd = <<<ROUTES

// Rutas del panel del scraper
Route::get('/productos', [App\Http\Controllers\ProductoController::class, 'index']);
Route::get('/productos/{id}', [App\Http\Controllers\ProductoController::class, 'show']);

ROUTES;

        File::append(base_path('routes/web.php'), $routesToAdd);
        $this->info("🔗 Rutas agregadas en routes/web.php");

        $this->info("\n✅ ESTRUCTURA COMPLETA GENERADA EXITOSAMENTE\n");
        return 0;
    }
}
