<?php

namespace App\Filament\Resources\FontFamilies;

use App\Filament\Resources\FontFamilies\Pages\CreateFontFamily;
use App\Filament\Resources\FontFamilies\Pages\EditFontFamily;
use App\Filament\Resources\FontFamilies\Pages\ListFontFamilies;
use App\Filament\Resources\FontFamilies\Schemas\FontFamilyForm;
use App\Filament\Resources\FontFamilies\Tables\FontFamiliesTable;
use App\Models\FontFamily;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FontFamilyResource extends Resource
{
    protected static ?string $model = FontFamily::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|\UnitEnum|null $navigationGroup = 'Site Yönetimi';

    protected static ?string $navigationLabel = 'Fontlar';

    protected static ?string $modelLabel = 'Font';

    protected static ?string $pluralModelLabel = 'Fontlar';

    public static function form(Schema $schema): Schema
    {
        return FontFamilyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FontFamiliesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFontFamilies::route('/'),
            'create' => CreateFontFamily::route('/create'),
            'edit' => EditFontFamily::route('/{record}/edit'),
        ];
    }
}
