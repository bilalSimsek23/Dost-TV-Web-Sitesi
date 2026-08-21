<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Models\Page;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                                    ->columnSpanFull(),
                            ]),

                        // 3. SEO Sekmesi
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

                        // 4. Önizleme Sekmesi
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
