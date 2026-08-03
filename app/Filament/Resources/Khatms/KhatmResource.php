<?php

namespace App\Filament\Resources\Khatms;

use App\Filament\Resources\Khatms\Pages\CreateKhatm;
use App\Filament\Resources\Khatms\Pages\EditKhatm;
use App\Filament\Resources\Khatms\Pages\ListKhatms;
use App\Filament\Resources\Khatms\RelationManagers\JuzClaimsRelationManager;
use App\Filament\Resources\Khatms\Schemas\KhatmForm;
use App\Filament\Resources\Khatms\Tables\KhatmsTable;
use App\Models\Khatm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KhatmResource extends Resource
{
    protected static ?string $model = Khatm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Yayın Yönetimi';

    protected static ?string $navigationLabel = 'Hatim / Cüz Al Yönetimi';

    protected static ?string $modelLabel = 'Hatim';

    protected static ?string $pluralModelLabel = 'Hatimler';

    protected static bool $shouldRegisterNavigation = false;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return KhatmForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KhatmsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            JuzClaimsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKhatms::route('/'),
            'create' => CreateKhatm::route('/create'),
            'edit' => EditKhatm::route('/{record}/edit'),
        ];
    }
}
