<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ekspor searah ke Google Sheets tiap 10 menit (hanya bila ada perubahan & SHEETS_URL/SHEETS_TOKEN terisi).
// Butuh satu cron di hosting: * * * * * php /path/artisan schedule:run
\Illuminate\Support\Facades\Schedule::command('spk:export-sheets')->everyTenMinutes()->withoutOverlapping();
