<?php

namespace App\Filament\Resources\SiteLayout\HomepageLayoutResource;

use App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\CreateHomepageLayout;
use App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\EditHomepageLayout;
use App\Filament\Resources\SiteLayout\HomepageLayoutResource\Pages\ListHomepageLayouts;
use App\Filament\Resources\SiteLayout\HomepageLayoutResource\Schemas\HomepageLayoutForm;
use App\Filament\Resources\SiteLayout\HomepageLayoutResource\Tables\HomepageLayoutsTable;
use App\Models\HomepageLayout;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HomepageLayoutResource extends Resource
{
    protected static ?string $model = HomepageLayout::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|\UnitEnum|null $navigationGroup = 'Site Düzeni';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Sayfa Düzenleri';

    protected static ?string $modelLabel = 'Sayfa Düzeni';

    protected static ?string $pluralModelLabel = 'Sayfa Düzenleri';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'administrator', 'designer']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return HomepageLayoutForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomepageLayoutsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomepageLayouts::route('/'),
            'create' => CreateHomepageLayout::route('/create'),
            'edit' => EditHomepageLayout::route('/{record}/edit'),
        ];
    }
}
