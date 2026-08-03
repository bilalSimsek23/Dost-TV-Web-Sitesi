<?php

namespace App\Filament\Resources\Menus\Schemas;

use App\Models\Menu;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Ad')
                    ->required(),

                Select::make('location')
                    ->label('Konum')
                    ->options(Menu::LOCATIONS)
                    ->required()
                    ->helperText('Aynı konum için yalnızca bir aktif menü olabilir.'),

                Textarea::make('description')
                    ->label('Açıklama')
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->helperText('Bu konumda başka aktif bir menü varsa kaydetme sırasında uyarı alırsınız.'),
            ]);
    }
}
