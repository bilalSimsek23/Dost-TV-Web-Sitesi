<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Models\Page;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('page-tabs')
                    ->tabs([
                        // 1. Genel Bilgiler Sekmesi
                        Tab::make('Genel Bilgiler')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Başlık')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                                TextInput::make('slug')
                                    ->label('Slug (Adres Tanımlayıcı)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Sayfa adresinde görünecek metin. Değiştirilirse eski web adresi bağlantısı değişecektir.'),
                            ]),

                        // 2. İçerik Sekmesi
                        Tab::make('İçerik')
                            ->schema([
                                RichEditor::make('content')
                                    ->label('İçerik Metni')
                                    ->helperText('Bu metin, ilgili kurumsal bilgi sayfasında ziyaretçilere gösterilir.')
                                    ->visible(fn (?Page $record, callable $get) => ($get('slug') !== 'iletisim' && ($record?->slug !== 'iletisim')))
                                    ->columnSpanFull(),

                                \Filament\Schemas\Components\Section::make('İLETİŞİM BİLGİLERİ')
                                    ->description('Buraya girdiğiniz bilgiler doğrudan İletişim sayfasındaki kartlarda gösterilir.')
                                    ->visible(fn (?Page $record, callable $get) => ($get('slug') === 'iletisim' || ($record?->slug === 'iletisim')))
                                    ->schema([
                                        Textarea::make('settings.address')
                                            ->label('Adres')
                                            ->rows(3)
                                            ->placeholder('Zübeyde Hanım, İstanbul Cad., Devrez Sok. No:1, 06070 Altındağ/Ankara')
                                            ->columnSpanFull(),

                                        TextInput::make('settings.phone')
                                            ->label('Telefon')
                                            ->placeholder('+90 (312) 341 21 21')
                                            ->nullable()
                                            ->regex('/^[0-9+\s()\-]+$/')
                                            ->maxLength(30)
                                            ->validationMessages([
                                                'regex' => 'Lütfen geçerli bir telefon numarası giriniz (ör: +90 (312) 341 21 21).',
                                            ]),

                                        TextInput::make('settings.email')
                                            ->label('E-posta')
                                            ->email()
                                            ->placeholder('iletisim@dosttv.com'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),

                        // 3. Konum / Google Maps Sekmesi (Yalnız İletişim sayfasında gösterilir)
                        Tab::make('Konum / Google Maps')
                            ->icon('heroicon-o-map-pin')
                            ->visible(fn (?Page $record, callable $get) => ($get('slug') === 'iletisim' || ($record && $record->slug === 'iletisim')))
                            ->schema([
                                Toggle::make('settings.map_enabled')
                                    ->label('Haritayı Göster')
                                    ->default(true)
                                    ->helperText('İletişim sayfasında Google Maps haritasını göster veya gizle.'),

                                TextInput::make('settings.map_title')
                                    ->label('Harita Başlığı')
                                    ->placeholder('DOST TV')
                                    ->default('DOST TV')
                                    ->helperText('Haritanın üzerinde gösterilecek konum veya şirket başlığı.'),

                                Textarea::make('settings.map_embed_url')
                                    ->label('Google Maps Embed URL veya Embed Kodu')
                                    ->placeholder('https://www.google.com/maps/embed?pb=... veya <iframe src="..."></iframe>')
                                    ->helperText('Google Maps > Paylaş > Harita Yerleştir bölümünden kopyaladığınız adresi veya iframe kodunu yapıştırın.')
                                    ->columnSpanFull(),

                                TextInput::make('settings.map_height')
                                    ->label('Harita Yüksekliği (px)')
                                    ->numeric()
                                    ->default(320)
                                    ->placeholder('320')
                                    ->helperText('Haritanın piksel cinsinden yüksekliği (varsayılan: 320px).'),
                            ]),

                        // 4. Tasarım Sekmesi
                        Tab::make('Tasarım')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Toggle::make('settings.design.use_custom_design')
                                    ->label('Bu sayfaya özel tasarım kullan')
                                    ->default(false)
                                    ->live()
                                    ->helperText('Açık olduğunda bu sayfaya özel renk, genişlik ve görünüm ayarlarını uygular. Kapalıyken global Görünüm Yönetimi teması kullanılır.'),

                                \Filament\Schemas\Components\Section::make('SAYFA RENKLERİ')
                                    ->description('Bu sayfada görüntülenecek özel zemin, yüzey ve vurgu renklerini belirleyin.')
                                    ->visible(fn (callable $get) => (bool) $get('settings.design.use_custom_design'))
                                    ->schema([
                                        ColorPicker::make('settings.design.background_color')
                                            ->label('Sayfa Arka Planı')
                                            ->placeholder('#030712')
                                            ->helperText('Tüm sayfa zemin rengi.'),

                                        ColorPicker::make('settings.design.surface_color')
                                            ->label('İçerik Yüzeyi')
                                            ->placeholder('#0f172a')
                                            ->helperText('İçerik kartının arka plan rengi.'),

                                        ColorPicker::make('settings.design.accent_color')
                                            ->label('Vurgu Rengi')
                                            ->placeholder('#f43f5e')
                                            ->helperText('Butonlar ve vurgulu elemanlar.'),

                                        ColorPicker::make('settings.design.text_color')
                                            ->label('Ana Metin Rengi')
                                            ->placeholder('#f8fafc'),

                                        ColorPicker::make('settings.design.muted_text_color')
                                            ->label('Soluk Metin Rengi')
                                            ->placeholder('#94a3b8'),
                                    ])
                                    ->columns(2),

                                \Filament\Schemas\Components\Section::make('DÜZEN VE ÖLÇÜLER')
                                    ->description('Sayfa geneli genişlik, radius ve iç boşluk ayarları.')
                                    ->visible(fn (callable $get) => (bool) $get('settings.design.use_custom_design'))
                                    ->schema([
                                        TextInput::make('settings.design.content_max_width')
                                            ->label('İçerik Maksimum Genişliği (px)')
                                            ->numeric()
                                            ->default(1000)
                                            ->placeholder('1000')
                                            ->helperText('İçerik alanının piksel cinsinden maksimum genişliği (ör: 900).'),

                                        TextInput::make('settings.design.card_radius')
                                            ->label('İçerik Kutusu Radius (px)')
                                            ->numeric()
                                            ->default(16)
                                            ->placeholder('16')
                                            ->helperText('İçerik kartının yuvarlatılmış köşe yarıçapı (ör: 24).'),

                                        TextInput::make('settings.design.padding_top')
                                            ->label('Üst Boşluk (px)')
                                            ->numeric()
                                            ->default(32)
                                            ->placeholder('32'),

                                        TextInput::make('settings.design.padding_bottom')
                                            ->label('Alt Boşluk (px)')
                                            ->numeric()
                                            ->default(48)
                                            ->placeholder('48'),

                                        Toggle::make('settings.design.show_surface')
                                            ->label('İçerik Kutusu (Yüzey) Kullan / Kullanma')
                                            ->default(true)
                                            ->helperText('Kapalı olduğunda içerik metinleri şeffaf zemin üzerinde oturur.')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        // 5. SEO Sekmesi
                        Tab::make('SEO')
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('SEO Başlığı')
                                    ->helperText('Boş bırakılırsa sayfa başlığı kullanılır.'),

                                Textarea::make('seo_description')
                                    ->label('SEO Açıklaması')
                                    ->columnSpanFull(),

                                FileUpload::make('og_image')
                                    ->label('OG Görseli (Sosyal Medya)')
                                    ->image()
                                    ->disk('public')
                                    ->directory('seo')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
