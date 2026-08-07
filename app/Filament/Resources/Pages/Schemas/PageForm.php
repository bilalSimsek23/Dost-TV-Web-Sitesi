<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Models\MenuItem;
use App\Models\Page;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('page-tabs')
                    ->tabs([
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

                                Select::make('page_type')
                                    ->label('İçerik Türü')
                                    ->options([
                                        'corporate' => 'Kurumsal Bilgi',
                                        'legal' => 'Yasal Bilgi',
                                        'contact' => 'İletişim',
                                        'donation' => 'Destek / Hesap Bilgisi',
                                    ])
                                    ->default('corporate')
                                    ->required(),

                                Select::make('status')
                                    ->label('Durum')
                                    ->options(Page::STATUSES)
                                    ->default('published')
                                    ->required(),

                                DateTimePicker::make('published_at')
                                    ->label('Yayın Tarihi')
                                    ->default(now()),
                            ]),

                        Tab::make('İçerik')
                            ->schema([
                                RichEditor::make('content')
                                    ->label('İçerik Metni')
                                    ->helperText('Bu metin, ilgili kurumsal bilgi sayfasında ziyaretçilere gösterilir.')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Sitedeki Konum')
                            ->schema([
                                Toggle::make('show_in_menu')
                                    ->label('Menüde Göster')
                                    ->default(false),

                                Toggle::make('show_in_header')
                                    ->label('Header\'da Göster')
                                    ->default(false),

                                Toggle::make('show_in_footer')
                                    ->label('Footer\'da Göster')
                                    ->default(true),

                                TextInput::make('menu_group')
                                    ->label('Menü Grubu'),

                                TextInput::make('menu_location')
                                    ->label('Menü Konumu'),

                                TextInput::make('sort_order')
                                    ->label('Sıralama')
                                    ->numeric()
                                    ->default(0),

                                Select::make('parent_id')
                                    ->label('Üst Sayfa')
                                    ->options(function (?Page $record) {
                                        return Page::query()
                                            ->where('page_type', 'corporate')
                                            ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                            ->pluck('title', 'id');
                                    })
                                    ->searchable()
                                    ->placeholder('Yok (Ana Sayfa Yap)'),

                                Placeholder::make('linked_menu_info')
                                    ->label('Bağlı Menü Durumu')
                                    ->content(function (?Page $record) {
                                        if (! $record) {
                                            return 'Sayfa kaydedildikten sonra bağlı menü bilgisi görünecektir.';
                                        }

                                        $menuItem = MenuItem::where('linked_model_type', 'page')
                                            ->where('linked_model_id', $record->id)
                                            ->first();

                                        if ($menuItem) {
                                            $parent = $menuItem->parent ? "{$menuItem->parent->title} → " : '';
                                            return new HtmlString("Bu içerik şu alanda bağlıdır: <strong class='text-amber-500'>{$parent}{$menuItem->title}</strong>");
                                        }

                                        return new HtmlString("<span class='text-slate-400'>Bu içerik şu anda hiçbir menü ögesine doğrudan bağlı değildir.</span>");
                                    }),
                            ]),

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

                        Tab::make('Önizleme')
                            ->schema([
                                Placeholder::make('page_preview')
                                    ->hiddenLabel()
                                    ->content(function (?Page $record, callable $get) {
                                        return view('components.site.page-card', [
                                            'preview' => true,
                                            'title' => $get('title') ?? ($record ? $record->title : 'Sayfa Başlığı'),
                                            'content' => $get('content') ?? ($record ? $record->content : ''),
                                        ]);
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
