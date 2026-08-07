<?php

namespace App\Filament\Resources\Programs\Pages;

use App\Filament\Resources\Programs\ProgramResource;
use App\Models\Program;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProgram extends EditRecord
{
    protected static string $resource = ProgramResource::class;

    public function hasCombinedRelationManagerTabsWithForm(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_on_site')
                ->label('Sitede Göster')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('success')
                ->url(fn (Program $record): string => route('programs.show', $record))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
