<?php

namespace App\Filament\Resources\ScheduleTemplates\Pages;

use App\Filament\Concerns\PersistsTablePaginationInUrl;
use App\Filament\Resources\ScheduleTemplates\ScheduleTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScheduleTemplates extends ListRecords
{
    use PersistsTablePaginationInUrl;

    protected static string $resource = ScheduleTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_template')
                ->label('Excel Şablonunu İndir')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('admin.schedule.download-template')),

            Action::make('excel_import')
                ->label("Excel'den Aktar")
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->url(static::getResource()::getUrl('excel-import')),

            CreateAction::make()
                ->label('Yeni Yayın Dönemi'),
        ];
    }
}

