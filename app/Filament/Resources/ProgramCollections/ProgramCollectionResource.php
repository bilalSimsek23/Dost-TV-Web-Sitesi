<?php

namespace App\Filament\Resources\ProgramCollections;

use App\Filament\Resources\ProgramCollections\Pages\CreateProgramCollection;
use App\Filament\Resources\ProgramCollections\Pages\EditProgramCollection;
use App\Filament\Resources\ProgramCollections\Pages\ListProgramCollections;
use App\Filament\Resources\ProgramCollections\RelationManagers\AutoResolvedProgramsRelationManager;
use App\Filament\Resources\ProgramCollections\RelationManagers\ProgramsRelationManager;
use App\Filament\Resources\ProgramCollections\Schemas\ProgramCollectionForm;
use App\Filament\Resources\ProgramCollections\Tables\ProgramCollectionsTable;
use App\Models\ProgramCollection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProgramCollectionResource extends Resource
{
    protected static ?string $model = ProgramCollection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare2Stack;

    protected static string|\UnitEnum|null $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Program Koleksiyonları';

    protected static ?string $modelLabel = 'Program Koleksiyonu';

    protected static ?string $pluralModelLabel = 'Program Koleksiyonları';

    public static function form(Schema $schema): Schema
    {
        return ProgramCollectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProgramCollectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProgramsRelationManager::class,
            AutoResolvedProgramsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProgramCollections::route('/'),
            'create' => CreateProgramCollection::route('/create'),
            'edit' => EditProgramCollection::route('/{record}/edit'),
        ];
    }
}
