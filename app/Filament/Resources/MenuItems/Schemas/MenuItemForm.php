<?php

namespace App\Filament\Resources\MenuItems\Schemas;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Program;
use App\Support\SafeUrl;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Shared MenuItem form definition, used by both the standalone
 * MenuItemResource and MenuResource's nested MenuItemsRelationManager so the
 * item_type behaviour and validation live in exactly one place.
 */
class MenuItemForm
{
    /**
     * Full form for the standalone MenuItemResource — includes the menu
     * picker itself.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('menu_id')
                ->label('Menü')
                ->relationship('menu', 'name')
                ->required()
                ->live()
                ->searchable()
                ->preload()
                ->helperText('Üst öğe seçimi yalnızca bu menüye ait öğelerle sınırlıdır.'),
            ...self::components(),
        ]);
    }

    /**
     * Field set without the menu picker — used inside MenuItemsRelationManager,
     * where the menu is already implied by the parent record. Callers there
     * must provide a `menu_id` Hidden field of their own so the shared
     * parent_id/options closures below can still read it via $get('menu_id').
     */
    public static function components(): array
    {
        return [
            Select::make('parent_id')
                ->label('Üst Öğe')
                ->options(function ($get, ?MenuItem $record) {
                    $menuId = $get('menu_id');

                    if (blank($menuId)) {
                        return [];
                    }

                    $excludedIds = $record ? [$record->getKey(), ...$record->descendantIds()] : [];

                    return MenuItem::query()
                        ->where('menu_id', $menuId)
                        ->when($excludedIds, fn ($query) => $query->whereNotIn('id', $excludedIds))
                        ->orderBy('sort_order')
                        ->get()
                        ->filter(fn (MenuItem $item) => MenuItem::depthOf($item->getKey()) < MenuItem::MAX_DEPTH)
                        ->pluck('title', 'id');
                })
                ->searchable()
                ->helperText('Yalnızca aynı menüye ait, en fazla 2. seviyedeki öğeler üst öğe olabilir (maksimum 3 seviye kuralı).'),

            TextInput::make('title')
                ->label('Başlık')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

            TextInput::make('slug')
                ->label('Slug')
                ->helperText('Boş bırakılırsa başlıktan otomatik üretilir.'),

            Select::make('item_type')
                ->label('Tür')
                ->options(MenuItem::ITEM_TYPES)
                ->required()
                ->live()
                ->helperText('Bu öğenin nereye yönlendireceğini belirler.'),

            Select::make('linked_model_id')
                ->label(fn ($get) => match ($get('item_type')) {
                    'page' => 'Sayfa',
                    'program' => 'Program',
                    'category' => 'Kategori',
                    default => 'Bağlı Kayıt',
                })
                ->options(fn ($get) => match ($get('item_type')) {
                    'page' => Page::query()->orderBy('title')->pluck('title', 'id'),
                    'program' => Program::query()->orderBy('name')->pluck('name', 'id'),
                    'category' => Category::query()->orderBy('name')->pluck('name', 'id'),
                    default => [],
                })
                ->searchable()
                ->preload()
                ->visible(fn ($get) => in_array($get('item_type'), ['page', 'program', 'category']))
                ->required(fn ($get) => in_array($get('item_type'), ['page', 'program', 'category'])),

            Hidden::make('linked_model_type')
                ->dehydrateStateUsing(fn ($get) => in_array($get('item_type'), ['page', 'program', 'category'])
                    ? $get('item_type')
                    : null),

            TextInput::make('url')
                ->label('URL')
                ->visible(fn ($get) => in_array($get('item_type'), ['url', 'custom']))
                ->required(fn ($get) => $get('item_type') === 'url')
                ->helperText('Dış bağlantılar için https:// ile başlayan tam adres, iç bağlantılar için / ile başlayan bir yol girin.')
                ->rules([
                    fn () => function (string $attribute, $value, \Closure $fail) {
                        if (! SafeUrl::isSafe($value)) {
                            $fail('Geçersiz veya güvenli olmayan bir bağlantı (javascript:, data: vb. desteklenmez).');
                        }
                    },
                ]),

            Select::make('route_name')
                ->label('Adlandırılmış Rota')
                ->options(MenuItem::ROUTE_NAME_OPTIONS)
                ->visible(fn ($get) => $get('item_type') === 'route')
                ->required(fn ($get) => $get('item_type') === 'route'),

            Placeholder::make('auto_resolved_notice')
                ->label('')
                ->content(fn ($get) => match ($get('item_type')) {
                    'live_tv' => 'Bu öğe otomatik olarak Canlı TV sayfasına yönlendirilir.',
                    'live_radio' => 'Bu öğe otomatik olarak Canlı Radyo sayfasına yönlendirilir.',
                    'schedule' => 'Bu öğe otomatik olarak Yayın Akışı sayfasına yönlendirilir.',
                    'dropdown' => 'Bu öğe yalnızca bir açılır menü başlığıdır, tıklanabilir bir bağlantısı yoktur — bir URL girmenize gerek yok.',
                    default => null,
                })
                ->visible(fn ($get) => in_array($get('item_type'), ['live_tv', 'live_radio', 'schedule', 'dropdown'])),

            TextInput::make('icon')
                ->label('İkon')
                ->helperText('Örn: heroicon-o-home'),

            TextInput::make('badge_text')
                ->label('Rozet Metni'),

            ColorPicker::make('badge_color')
                ->label('Rozet Rengi'),

            ColorPicker::make('text_color')
                ->label('Metin Rengi'),

            ColorPicker::make('background_color')
                ->label('Arka Plan Rengi'),

            ColorPicker::make('hover_text_color')
                ->label('Hover Metin Rengi'),

            ColorPicker::make('hover_background_color')
                ->label('Hover Arka Plan Rengi'),

            TextInput::make('css_class')
                ->label('Özel CSS Sınıfı'),

            Toggle::make('open_in_new_tab')
                ->label('Yeni Sekmede Aç'),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),

            Toggle::make('show_on_desktop')
                ->label('Masaüstünde Göster')
                ->default(true)
                ->helperText('Bu seçenek yalnızca masaüstü menüde görünürlüğü etkiler.'),

            Toggle::make('show_on_mobile')
                ->label('Mobilde Göster')
                ->default(true)
                ->helperText('Bu seçenek yalnızca mobil menüde görünürlüğü etkiler.'),

            Toggle::make('requires_auth')
                ->label('Giriş Gerektirir'),

            TextInput::make('sort_order')
                ->label('Sıra')
                ->numeric()
                ->default(0),

            KeyValue::make('metadata')
                ->label('Ek Veri (Metadata)')
                ->keyLabel('Anahtar')
                ->valueLabel('Değer')
                ->helperText('Örn. route parametreleri: route_parameters.'),
        ];
    }
}
