<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

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
                        'page_id' => $record->id,
                        'page_slug' => $record->slug,
                        'user_id' => auth()->id(),
                        'design' => $formData['settings']['design'] ?? [],
                    ];

                    \Illuminate\Support\Facades\Cache::put('page_preview_' . $token, $previewData, now()->addHours(2));
                    session()->put('page_preview_token', $token);
                    session()->put('page_preview_data', $previewData);

                    $targetUrl = url("/{$record->slug}?page_preview_token={$token}");

                    \Filament\Notifications\Notification::make()
                        ->title('Canlı Sayfa Test Modu Başlatıldı')
                        ->body('Kaydedilmemiş görünüm ayarları yeni sekmede test ediliyor.')
                        ->warning()
                        ->send();

                    $this->js("window.open('{$targetUrl}', '_blank')");
                }),

            \Filament\Actions\Action::make('previewInNewTab')
                ->label('Yeni Sekmede Önizle')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn ($record) => url("/{$record->slug}"))
                ->openUrlInNewTab(),

            DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        return \Filament\Notifications\Notification::make()
            ->success()
            ->title('Kurumsal Bilgi Güncellendi')
            ->body('Sayfa içeriği ve iletişim bilgileri başarıyla kaydedildi.');
    }
}
