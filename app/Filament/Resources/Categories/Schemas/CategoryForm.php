<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('category-tabs')
                    ->tabs([
                        Tab::make('Genel Bilgiler')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Kategori Adı')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                                TextInput::make('slug')
                                    ->label('Slug (Adres Tanımlayıcı)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Sayfa web adresinde görünecek metin. Boş bırakılırsa kategori adından otomatik üretilir.'),

                                Select::make('parent_id')
                                    ->label('Üst Kategori')
                                    ->options(function (?Category $record) {
                                        $excludedIds = $record ? [$record->getKey(), ...$record->descendantIds()] : [];

                                        $categories = Category::query()
                                            ->with('parent')
                                            ->where('slug', '!=', Category::ALL_CATEGORIES_SLUG)
                                            ->when($excludedIds, fn ($query) => $query->whereNotIn('id', $excludedIds))
                                            ->orderBy('name')
                                            ->get();

                                        $options = [];
                                        foreach ($categories as $cat) {
                                            if ($cat->parent) {
                                                $options[$cat->id] = "{$cat->parent->name} → {$cat->name} (Alt Kategori)";
                                            } else {
                                                $options[$cat->id] = "📁 {$cat->name} (Ana Kategori)";
                                            }
                                        }

                                        return $options;
                                    })
                                    ->searchable()
                                    ->placeholder('Yok (Ana Kategori Yap)')
                                    ->helperText('Üst kategori seçilirse bu kayıt alt kategoriye dönüşür.'),

                                Textarea::make('description')
                                    ->label('Açıklama')
                                    ->columnSpanFull(),

                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true)
                                    ->helperText('Pasif kategoriler sitede ve filtrelerde görüntülenmez.'),

                                Toggle::make('is_featured')
                                    ->label('Öne Çıkan')
                                    ->helperText('Anasayfa veya özel bloklarda öne çıkarılsın.'),

                                TextInput::make('sort_order')
                                    ->label('Sıra')
                                    ->numeric()
                                    ->default(0),
                            ]),

                        Tab::make('Görseller')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Kategori Görseli')
                                    ->image()
                                    ->disk('public')
                                    ->directory('categories')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->helperText('İzin verilen türler: jpg, jpeg, png, webp. Maksimum 5 MB.'),

                                TextInput::make('icon')
                                    ->label('İkon Kodu')
                                    ->helperText('Örn: heroicon-o-tag'),

                                ColorPicker::make('text_color')
                                    ->label('Metin Rengi'),

                                ColorPicker::make('background_color')
                                    ->label('Arka Plan Rengi'),

                                ColorPicker::make('hover_color')
                                    ->label('Hover Rengi'),

                                Select::make('card_variant')
                                    ->label('Kart Varyantı')
                                    ->options([
                                        'default' => 'Standart Kart',
                                        'compact' => 'Kompakt Kart',
                                        'featured' => 'Öne Çıkan Kart',
                                    ])
                                    ->default('default'),
                            ]),

                        Tab::make('Menü')
                            ->schema([
                                Toggle::make('show_in_menu')
                                    ->label('Ana Menüde Göster')
                                    ->default(true)
                                    ->helperText('Bu kategori sitenin üst navigasyon menüsünde gösterilsin mi?'),

                                Toggle::make('show_in_mega_menu')
                                    ->label('Mega Menüde Göster')
                                    ->default(true)
                                    ->helperText('Bu kategori Programlar açılır mega menüsünde gösterilsin mi?'),
                            ]),

                        Tab::make('SEO')
                            ->schema([
                                TextInput::make('seo_title')
                                    ->label('SEO Başlığı')
                                    ->helperText('Boş bırakılırsa kategori adı kullanılır.'),

                                Textarea::make('seo_description')
                                    ->label('SEO Açıklaması')
                                    ->columnSpanFull(),

                                FileUpload::make('og_image')
                                    ->label('OG Görseli (Sosyal Medya)')
                                    ->image()
                                    ->disk('public')
                                    ->directory('seo')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),

                                TextInput::make('canonical_url')
                                    ->label('Canonical URL')
                                    ->url(),

                                Select::make('index_policy')
                                    ->label('Index Politikası')
                                    ->options([
                                        'index' => 'Index (Arama motorları dizine eklesin)',
                                        'noindex' => 'NoIndex (Dizine eklenmesin)',
                                    ])
                                    ->default('index'),

                                Select::make('follow_policy')
                                    ->label('Follow Politikası')
                                    ->options([
                                        'follow' => 'Follow (Linkleri takip et)',
                                        'nofollow' => 'NoFollow (Linkleri takip etme)',
                                    ])
                                    ->default('follow'),

                                KeyValue::make('metadata')
                                    ->label('Ek Veri (Metadata)')
                                    ->keyLabel('Anahtar')
                                    ->valueLabel('Değer')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
