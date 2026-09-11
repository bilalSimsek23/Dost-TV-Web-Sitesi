<?php

namespace App\Filament\Resources\Banners\Pages;

use App\Filament\Resources\Banners\BannerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBanner extends EditRecord
{
    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('previewInNewTab')
                ->label('Yeni Sekmede Önizle')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn ($record) => $record->link_url ?: url('/'))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
