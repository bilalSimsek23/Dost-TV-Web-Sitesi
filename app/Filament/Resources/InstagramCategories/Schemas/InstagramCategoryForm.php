<?php

namespace App\Filament\Resources\InstagramCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class InstagramCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Instagram Reel Kategorisi')
                ->description('Reel videolarını gruplamak için kategori tanımlayın.')
                ->schema([
                    TextInput::make('name')
                        ->label('Kategori Adı')
                        ->placeholder('Örn: Hocalar, Ayetler, Psikoloji...')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, $state, callable $set) {
                            if ($operation === 'create') {
                                $set('slug', Str::slug($state));
                            }
                        }),

                    TextInput::make('slug')
                        ->label('Slug / Bağlantı Adı')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    TextInput::make('sort_order')
                        ->label('Sıralama')
                        ->numeric()
                        ->default(0),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Pasif kategoriler haftalık plana atanmış olsa bile sitede yayınlanmaz.'),
                ]),
        ]);
    }
}
