<?php

namespace App\Filament\Resources\Programs\Schemas;

use App\Models\Episode;
use App\Models\Program;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('ProgramFormTabs')
                    ->tabs([
                        Tab::make('📌 Genel Bilgiler')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Program Adı')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                                    TextInput::make('slug')
                                        ->label('Bağlantı Adresi (URL)')
                                        ->required()
                                        ->unique(ignoreRecord: true),
                                ]),

                                Textarea::make('short_description')
                                    ->label('Yayın Akışı Kısa Tanımı')
                                    ->helperText('Yayın akışında program adının yanında veya altında gösterilen kısa tanıtım metni.')
                                    ->placeholder('Yayın akışında görünecek kısa tanıtım metni...')
                                    ->maxLength(160)
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Textarea::make('description')
                                    ->label('Program Tanıtım Metni')
                                    ->helperText('Program detay sayfasında ve uygun büyük tanıtım alanlarında gösterilen program açıklaması.')
                                    ->placeholder('Program detay sayfasında görünecek detaylı tanıtım açıklaması...')
                                    ->rows(4)
                                    ->columnSpanFull(),

                                Grid::make(3)->schema([
                                    Toggle::make('is_featured')
                                        ->label('Öne Çıkan Program')
                                        ->default(false),

                                    TextInput::make('trailer_url')
                                        ->label('Tanıtım Fragmanı (YouTube URL)')
                                        ->placeholder('https://www.youtube.com/watch?v=...')
                                        ->url(),

                                    TextInput::make('youtube_channel_url')
                                        ->label('YouTube Kanal URL')
                                        ->placeholder('https://www.youtube.com/@kanaladi')
                                        ->url()
                                        ->helperText('Programın resmi YouTube kanal bağlantısı (Bilgilendirme amaçlıdır)'),
                                ]),
                            ]),

                        Tab::make('🖼️ Görseller')
                            ->schema([
                                Grid::make(2)->schema([
                                    FileUpload::make('cover_image')
                                        ->label('Program Kapak Görseli (Dikey)')
                                        ->image()
                                        ->disk('public')
                                        ->directory('programs')
                                        ->maxSize(5120)
                                        ->helperText('Önerilen boyut: 1080 × 1350 px. Maksimum 5 MB.'),

                                    FileUpload::make('horizontal_image')
                                        ->label('Yatay Program Görseli')
                                        ->image()
                                        ->disk('public')
                                        ->directory('programs')
                                        ->maxSize(5120)
                                        ->helperText('Önerilen boyut: 1920 × 1080 px (16:9). Maksimum 5 MB.'),

                                    FileUpload::make('program_logo')
                                        ->label('Program Logosu')
                                        ->image()
                                        ->disk('public')
                                        ->directory('programs')
                                        ->maxSize(5120)
                                        ->helperText('Önerilen format: Şeffaf PNG veya WEBP. Maksimum 5 MB.'),

                                    FileUpload::make('default_episode_image')
                                        ->label('Varsayılan Bölüm Görseli')
                                        ->image()
                                        ->disk('public')
                                        ->directory('episodes')
                                        ->maxSize(5120)
                                        ->helperText('Görseli olmayan bölümler için kullanılır (1920 × 1080 px). Maksimum 5 MB.'),
                                ]),
                            ]),

                        Tab::make('🏷️ Kategoriler')
                            ->schema([
                                Select::make('categories')
                                    ->label('Kategoriler')
                                    ->relationship('categories', 'name', fn ($query) => $query->where('slug', '!=', \App\Models\Category::ALL_CATEGORIES_SLUG))
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Kategori Adı')
                                            ->required(),
                                    ]),
                            ]),

                        Tab::make('🔍 SEO')
                            ->schema([
                                TextInput::make('meta_title')
                                    ->label('Google Arama Başlığı (Meta Title)')
                                    ->placeholder('Boş bırakılırsa program adı kullanılır'),

                                Textarea::make('meta_description')
                                    ->label('SEO Açıklaması')
                                    ->helperText('Google ve arama motorları için özel açıklama. Boş bırakılırsa Program Tanıtım Metni kullanılır.')
                                    ->rows(3)
                                    ->placeholder('Programın Google arama sonuçlarında görünecek özel SEO özeti'),
                            ]),

                        Tab::make('Önizleme')
                            ->schema([
                                Placeholder::make('program_preview')
                                    ->hiddenLabel()
                                    ->content(function (?Program $record, callable $get) {
                                        $coverImage = $get('cover_image') ?? ($record ? $record->cover_image : null);

                                        return view('components.site.program-card', [
                                            'preview' => true,
                                            'title' => $get('name') ?? ($record ? $record->name : 'Program Adı'),
                                            'coverImage' => $coverImage,
                                        ]);
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
