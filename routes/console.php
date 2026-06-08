<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Limpar jobs falhos automaticamente todos os dias à meia-noite
Schedule::command('jobs:cleanup-failed --days=7 --force')->dailyAt('00:00');
