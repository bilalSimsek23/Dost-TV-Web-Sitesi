<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var ContactMessage $contactMessage */
        $contactMessage = $this->getRecord();
        $contactMessage->markAsRead();
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Durum Güncelle'),
            DeleteAction::make()->label('Sil'),
        ];
    }
}
