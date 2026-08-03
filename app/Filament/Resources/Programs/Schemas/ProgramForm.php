<?php

namespace App\Filament\Resources\Programs\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
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
                                TextInput::make('name')
                                    ->label('Program Adı')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                                TextInput::make('slug')
                                    ->label('Bağlantı Adresi (URL)')
                                    ->required()
                                    ->unique(ignoreRecord: true),

                                Select::make('categories')
                                    ->label('Kategoriler')
                                    ->relationship('categories', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Kategori Adı')
                                            ->required(),
                                    ]),

                                Textarea::make('description')
                                    ->label('Program Özeti & Açıklaması')
                                    ->rows(4)
                                    ->columnSpanFull(),

                                Toggle::make('is_active')
                                    ->label('Yayında ve Aktif')
                                    ->default(true),

                                TextInput::make('sort_order')
                                    ->label('Sıralama Önceliği')
                                    ->numeric()
                                    ->default(0),
                            ]),

                        Tab::make('🖼️ Görsel & Medya')
                            ->schema([
                                FileUpload::make('cover_image')
                                    ->label('Program Kapak Resmi')
                                    ->image()
                                    ->disk('public')
                                    ->directory('programs'),

                                TextInput::make('trailer_url')
                                    ->label('Tanıtım Fragmanı (YouTube / Video URL)')
                                    ->url(),
                            ]),

                        Tab::make('🔍 SEO & Arama Motoru')
                            ->schema([
                                TextInput::make('meta_title')
                                    ->label('Google Arama Başlığı')
                                    ->placeholder('Boş bırakılırsa program adı kullanılır'),

                                Textarea::make('meta_description')
                                    ->label('Google Arama Açıklaması')
                                    ->rows(3)
                                    ->placeholder('Programın Google sonuçlarında görünecek kısa özeti'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
