<?php

namespace App\Filament\Resources\Khatms\Pages;

use App\Filament\Resources\Khatms\KhatmResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKhatm extends EditRecord
{
    protected static string $resource = KhatmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
