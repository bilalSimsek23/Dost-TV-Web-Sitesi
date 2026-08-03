<?php

namespace App\Filament\Resources\FontFamilies\Pages;

use App\Filament\Resources\FontFamilies\FontFamilyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFontFamily extends EditRecord
{
    protected static string $resource = FontFamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
