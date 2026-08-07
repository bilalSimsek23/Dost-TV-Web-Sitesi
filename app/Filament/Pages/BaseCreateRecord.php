<?php

namespace App\Filament\Pages;

use Filament\Resources\Pages\CreateRecord;

abstract class BaseCreateRecord extends CreateRecord
{
    /**
     * Disable Filament's default "Create another" ("Bir tane daha oluştur") button
     * project-wide across all Resource creation pages.
     */
    protected static bool $canCreateAnother = false;
}
