<?php

namespace App\Filament\Resources\YoutubeChannels\Schemas;

use App\Services\YouTube\YouTubeChannelFetchService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class YoutubeChannelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('YouTube Kanal Bilgileri')
                ->description('Kanal adını ve YouTube URL adresini girin, ardından bilgileri YouTube üzerinden otomatik çekin.')
                ->schema([
                    TextInput::make('name')
                        ->label('Kanal / Program Adı')
                        ->placeholder('Örn: Çocuk ve Biz')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('url')
                        ->label('YouTube Kanal URL')
                        ->placeholder('Örn: https://www.youtube.com/@CocukveBiz')
                        ->required()
                        ->url()
                        ->maxLength(500)
                        ->suffixAction(
                            Action::make('fetchChannelSuffix')
                                ->label('Bilgileri Getir')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('rose')
                                ->action(function (callable $get, callable $set, $record) {
                                    static::performFetch($get, $set, $record);
                                })
                        ),

                    Actions::make([
                        Action::make('fetchChannelInfo')
                            ->label('Kanal Bilgilerini Getir')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('rose')
                            ->action(function (callable $get, callable $set, $record) {
                                static::performFetch($get, $set, $record);
                            }),
                    ])->columnSpanFull(),

                    Placeholder::make('channel_preview')
                        ->label('')
                        ->visible(fn (callable $get) => filled($get('logo')) || filled($get('handle')))
                        ->content(function (callable $get) {
                            return view('filament.resources.youtube-channels.channel-preview', [
                                'logo' => $get('logo'),
                                'name' => $get('name'),
                                'handle' => $get('handle'),
                            ]);
                        })
                        ->columnSpanFull(),

                    Hidden::make('handle'),
                    Hidden::make('channel_id'),
                    Hidden::make('logo'),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Pasif kanallar canlı sitede gösterilmez.'),
                ]),
        ]);
    }

    protected static function performFetch(callable $get, callable $set, $record): void
    {
        $url = $get('url');
        $customName = $get('name');

        if (blank($url)) {
            Notification::make()
                ->title('Lütfen geçerli bir YouTube Kanal URL adresi girin.')
                ->warning()
                ->send();
            return;
        }

        try {
            $service = app(YouTubeChannelFetchService::class);
            $ignoreId = $record ? $record->id : null;
            $info = $service->fetchChannelInfo($url, $ignoreId);

            if (blank($customName)) {
                $set('name', $info['name']);
            }
            $set('url', $info['canonical_url']);
            $set('handle', $info['handle']);
            $set('channel_id', $info['channel_id']);
            $set('logo', $info['avatar_url']);

            Notification::make()
                ->title('Kanal Bulundu!')
                ->body("{$info['name']} ({$info['handle']}) bilgileri YouTube üzerinden başarıyla çekildi.")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Kanal Bilgisi Alınamadı')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
