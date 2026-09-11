<?php

namespace App\Filament\Resources\VideoCollections;

use App\Filament\Resources\VideoCollections\Pages\CreateVideoCollection;
use App\Filament\Resources\VideoCollections\Pages\EditVideoCollection;
use App\Filament\Resources\VideoCollections\Pages\ListVideoCollections;
use App\Filament\Resources\VideoCollections\RelationManagers\AutoResolvedEpisodesRelationManager;
use App\Filament\Resources\VideoCollections\RelationManagers\EpisodesRelationManager;
use App\Filament\Resources\VideoCollections\Schemas\VideoCollectionForm;
use App\Filament\Resources\VideoCollections\Tables\VideoCollectionsTable;
use App\Models\VideoCollection;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VideoCollectionResource extends Resource
{
    protected static ?string $model = VideoCollection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFilm;

    protected static string|\UnitEnum|null $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Video Koleksiyonları';

    protected static ?string $modelLabel = 'Video Koleksiyonu';

    protected static ?string $pluralModelLabel = 'Video Koleksiyonları';

    public static function form(Schema $schema): Schema
    {
        return VideoCollectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VideoCollectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EpisodesRelationManager::class,
            AutoResolvedEpisodesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVideoCollections::route('/'),
            'create' => CreateVideoCollection::route('/create'),
            'edit' => EditVideoCollection::route('/{record}/edit'),
        ];
    }
}
