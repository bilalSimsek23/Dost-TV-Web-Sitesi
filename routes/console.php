<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('youtube:sync-playlists')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('audit:prune')
    ->daily()
    ->withoutOverlapping();

