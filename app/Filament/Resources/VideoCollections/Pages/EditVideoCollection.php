<?php

namespace App\Filament\Resources\VideoCollections\Pages;

use App\Filament\Resources\VideoCollections\VideoCollectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVideoCollection extends EditRecord
{
    protected static string $resource = VideoCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('livePreview')
                ->label('Canlı Test Et')
                ->icon('heroicon-o-beaker')
                ->color('amber')
                ->action(function () {
                    $record = $this->getRecord();
                    $formData = $this->form->getState();

                    $token = \Illuminate\Support\Str::random(32);
                    $previewData = [
                        'type' => 'video_collection',
                        'id' => $record->id,
                        'slug' => $record->slug,
                        'user_id' => auth()->id(),
                        'public_settings' => $formData['public_settings'] ?? [],
                    ];

                    \Illuminate\Support\Facades\Cache::put('collection_preview_' . $token, $previewData, now()->addHours(2));
                    session()->put('collection_preview_token', $token);
                    session()->put('collection_preview_data', $previewData);

                    $targetUrl = url("/koleksiyonlar/{$record->slug}?collection_preview_token={$token}");

                    \Filament\Notifications\Notification::make()
                        ->title('Canlı Koleksiyon Test Modu Başlatıldı')
                        ->body('Kaydedilmemiş görünüm ayarları yeni sekmede test ediliyor.')
                        ->warning()
                        ->send();

                    $this->js("window.open('{$targetUrl}', '_blank')");
                }),

            \Filament\Actions\Action::make('previewInNewTab')
                ->label('Yeni Sekmede Önizle')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn ($record) => url("/koleksiyonlar/{$record->slug}"))
                ->openUrlInNewTab(),

            DeleteAction::make()->label('Koleksiyonu Sil'),
        ];
    }
}
