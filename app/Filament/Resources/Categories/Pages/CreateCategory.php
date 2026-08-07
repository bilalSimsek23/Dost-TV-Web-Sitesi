<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Pages\BaseCreateRecord;
use App\Filament\Resources\Categories\CategoryResource;

class CreateCategory extends BaseCreateRecord
{
    protected static string $resource = CategoryResource::class;
}
