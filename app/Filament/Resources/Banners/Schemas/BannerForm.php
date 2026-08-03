<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Başlık')
                    ->required(),
                TextInput::make('subtitle')
                    ->label('Alt Başlık'),
                FileUpload::make('image')
                    ->label('Görsel')
                    ->image()
                    ->disk('public')
                    ->directory('banners')
                    ->required(),
                TextInput::make('link_url')
                    ->label('Link (opsiyonel)')
                    ->url(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                TextInput::make('sort_order')
                    ->label('Sıralama')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
