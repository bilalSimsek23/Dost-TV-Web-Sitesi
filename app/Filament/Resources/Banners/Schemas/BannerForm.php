<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Models\Banner;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('banner-tabs')
                    ->tabs([
                        Tab::make('Genel Bilgiler')
                            ->schema([
                                Select::make('content_type')
                                    ->label('İçerik Türü')
                                    ->options(Banner::CONTENT_TYPES)
                                    ->default('hero')
                                    ->required()
                                    ->helperText('Bu içerik, Claude tarafından hazırlanmış tasarımdaki ilgili görsel alanda gösterilir.'),

                                TextInput::make('title')
                                    ->label('Başlık')
                                    ->required(),

                                TextInput::make('subtitle')
                                    ->label('Alt Başlık'),

                                Textarea::make('description')
                                    ->label('Açıklama')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Görsel')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Masaüstü Görseli')
                                    ->helperText('Önerilen çözünürlük: 1920 × 1080 px (16:9). JPG, PNG, WEBP. Maks 5 MB.')
                                    ->image()
                                    ->disk('public')
                                    ->directory('banners')
                                    ->maxSize(5120)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->required(),

                                FileUpload::make('mobile_image')
                                    ->label('Mobil Görseli (İsteğe Bağlı)')
                                    ->helperText('Önerilen çözünürlük: 1080 × 1350 px (4:5). JPG, PNG, WEBP. Maks 5 MB.')
                                    ->image()
                                    ->disk('public')
                                    ->directory('banners')
                                    ->maxSize(5120)
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),

                                TextInput::make('alt_text')
                                    ->label('Alternatif Metin (Alt Text)')
                                    ->maxLength(160)
                                    ->helperText('Erişilebilirlik için kullanılır.'),
                            ]),

                        Tab::make('Bağlantı ve Buton')
                            ->schema([
                                TextInput::make('button_text')
                                    ->label('Buton Metni')
                                    ->placeholder('Örn: Detayları Gör'),

                                TextInput::make('link_url')
                                    ->label('Bağlantı Adresi')
                                    ->placeholder('https://... veya /programlar/...')
                                    ->url(),

                                Toggle::make('open_in_new_tab')
                                    ->label('Yeni Sekmede Aç')
                                    ->default(false),
                            ]),

                        Tab::make('Yayınlama')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),

                                DateTimePicker::make('starts_at')
                                    ->label('Başlangıç Tarihi')
                                    ->helperText('Tarih girilmezse hemen gösterilir.'),

                                DateTimePicker::make('ends_at')
                                    ->label('Bitiş Tarihi')
                                    ->afterOrEqual('starts_at')
                                    ->helperText('Tarih girilmezse süresiz gösterilir.'),

                                TextInput::make('sort_order')
                                    ->label('Sıralama')
                                    ->numeric()
                                    ->default(0),
                            ]),

                        Tab::make('Önizleme')
                            ->schema([
                                Placeholder::make('hero_banner_preview')
                                    ->hiddenLabel()
                                    ->content(function (?Banner $record, callable $get) {
                                        $image = $get('image') ?? ($record ? $record->image : null);

                                        return view('components.site.hero-banner', [
                                            'preview' => true,
                                            'title' => $get('title') ?? ($record ? $record->title : 'Banner Başlığı'),
                                            'subtitle' => $get('subtitle') ?? ($record ? $record->subtitle : null),
                                            'image' => $image,
                                            'linkUrl' => $get('link_url') ?? ($record ? $record->link_url : null),
                                        ]);
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
