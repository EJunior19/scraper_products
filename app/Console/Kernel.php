<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Comandos Artisan registrados manualmente
     */
    protected $commands = [
        \App\Console\Commands\MakeScraperStructure::class,
    ];

    /**
     * Definición del Scheduler de Laravel
     */
    protected function schedule(Schedule $schedule)
    {
        // Scraper automático - Perfumería (Mapy)
        $schedule->command(
            'scrape:categoria "https://www.mapy.com.py/categoria-produto/perfumeria/" "Perfumería"'
        )
        ->everySixHours()          // cada 6 horas
        ->withoutOverlapping()     // evita ejecuciones duplicadas
        ->runInBackground();       // no bloquea otros procesos
    }

    /**
     * Registro automático de comandos
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
