<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Comandos Artisan
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler (Tareas programadas)
|--------------------------------------------------------------------------
| Acá se definen las tareas automáticas en Laravel 11/12
*/

Schedule::command(
    'scrape:categoria "https://www.mapy.com.py/categoria-produto/perfumeria/" "Perfumería"'
)
    ->everySixHours()          // cada 6 horas
    ->withoutOverlapping()     // evita duplicados si tarda
    ->runInBackground();       // no bloquea otros procesos
