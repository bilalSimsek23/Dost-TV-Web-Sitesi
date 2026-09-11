<?php

namespace App\Filament\Resources\InstagramVideos\Schemas;

use App\Services\Instagram\InstagramFetchService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InstagramVideoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Instagram Reel / Video')
                ->description('Instagram Reel URL adresini girin, Hoca Adı ve Başlık bilgilerini düzenleyin.')
                ->schema([
                    TextInput::make('permalink')
                        ->label('Instagram Reel / Video URL')
                        ->placeholder('https://www.instagram.com/reel/...')
                        ->required()
                        ->url()
                        ->maxLength(500)
                        ->suffixAction(
                            Action::make('fetchSuffixAction')
                                ->label('Bilgileri Getir')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('rose')
                                ->action(function (callable $get, callable $set, $record) {
                                    static::performFetch($get, $set, $record);
                                })
                        ),

                    Actions::make([
                        Action::make('fetchMediaInfo')
                            ->label('Bilgileri Getir')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('rose')
                            ->action(function (callable $get, callable $set, $record) {
                                static::performFetch($get, $set, $record);
                            }),
                    ])->columnSpanFull(),

                    Select::make('categories')
                        ->label('Kategoriler')
                        ->multiple()
                        ->relationship('categories', 'name')
                        ->preload()
                        ->placeholder('Kategori seçin...')
                        ->helperText('Bu Reel\'in dahil olduğu kategorileri seçin (Pazartesi: Hocalar, Salı: Ayetler vb.).'),

                    TextInput::make('speaker_name')
                        ->label('Hoca Adı')
                        ->placeholder('Prof. Dr. Nevzat Tarhan')
                        ->maxLength(255)
                        ->helperText('İçerikte konuşan hoca veya programcı adı (opsiyonel).'),

                    Textarea::make('caption')
                        ->label('Açıklama / Başlık')
                        ->placeholder('Instagram varsayılan açıklaması veya özelleştirilmiş başlık...')
                        ->rows(3)
                        ->helperText('Instagram\'dan otomatik gelir, dilerseniz kendiniz düzenleyebilirsiniz.')
                        ->columnSpanFull(),

                    FileUpload::make('cover_image')
                        ->label('Kapak Görseli (Opsiyonel)')
                        ->image()
                        ->disk('public')
                        ->directory('instagram-covers')
                        ->imagePreviewHeight('240')
                        ->helperText('Önerilen Kapak Ölçüsü: 1080 × 1920 px (9:16). Boş bırakılırsa Instagram API kapak görseli kullanılır.')
                        ->columnSpanFull(),

                    Placeholder::make('video_preview')
                        ->label('')
                        ->visible(fn (callable $get) => filled($get('shortcode')) || filled($get('embed_html')) || filled($get('permalink')))
                        ->content(function (callable $get, $record) {
                            return view('filament.resources.instagram-videos.preview', [
                                'embedHtml' => $get('embed_html'),
                                'permalink' => $get('permalink'),
                                'shortcode' => $get('shortcode'),
                                'username' => $get('username'),
                                'speakerName' => $get('speaker_name'),
                                'caption' => $get('caption'),
                                'thumbnailUrl' => $record?->cover_image_url ?: $get('thumbnail_url'),
                            ]);
                        })
                        ->columnSpanFull(),

                    Hidden::make('instagram_media_id'),
                    Hidden::make('shortcode'),
                    Hidden::make('media_type')->default('reel'),
                    Hidden::make('embed_html'),
                    Hidden::make('username'),
                    Hidden::make('thumbnail_url'),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Pasif Instagram içerikleri yayınlanan sitede gizlenir.'),
                ]),
        ]);
    }

    protected static function performFetch(callable $get, callable $set, $record = null): void
    {
        $url = $get('permalink');

        if (blank($url)) {
            Notification::make()
                ->title('Lütfen geçerli bir Instagram Reel / Video URL adresi girin.')
                ->warning()
                ->send();
            return;
        }

        try {
            $service = app(InstagramFetchService::class);
            $ignoreId = $record ? $record->id : null;
            $info = $service->fetchMediaData($url, $ignoreId);

            $set('permalink', $info['permalink']);
            $set('shortcode', $info['shortcode']);
            $set('embed_html', $info['embed_html']);
            $set('instagram_media_id', $info['instagram_media_id']);
            $set('media_type', $info['media_type']);
            $set('username', $info['username']);
            $set('thumbnail_url', $info['thumbnail_url']);

            // Only update caption if the field is currently blank
            if (blank($get('caption')) && filled($info['caption'])) {
                $set('caption', $info['caption']);
            }

            Notification::make()
                ->title('✓ Instagram İçeriği Doğrulandı!')
                ->body('Resmi Instagram embed verisi başarıyla çekildi.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Bilgi Alınamadı')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
