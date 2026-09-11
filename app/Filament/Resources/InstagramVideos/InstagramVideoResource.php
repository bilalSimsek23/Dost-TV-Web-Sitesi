<?php

namespace App\Filament\Resources\InstagramVideos;

use App\Filament\Resources\InstagramVideos\Pages\CreateInstagramVideo;
use App\Filament\Resources\InstagramVideos\Pages\EditInstagramVideo;
use App\Filament\Resources\InstagramVideos\Pages\ListInstagramVideos;
use App\Filament\Resources\InstagramVideos\Schemas\InstagramVideoForm;
use App\Filament\Resources\InstagramVideos\Tables\InstagramVideosTable;
use App\Models\InstagramVideo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InstagramVideoResource extends Resource
{
    protected static ?string $model = InstagramVideo::class;

    protected static string|\UnitEnum|null $navigationGroup = 'İçerik Yönetimi';

    protected static ?string $navigationLabel = 'Instagram Videoları';

    protected static ?string $modelLabel = 'Instagram Videosu';

    protected static ?string $pluralModelLabel = 'Instagram Videoları';

    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    public static function form(Schema $schema): Schema
    {
        return InstagramVideoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstagramVideosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstagramVideos::route('/'),
            'create' => CreateInstagramVideo::route('/create'),
            'edit' => EditInstagramVideo::route('/{record}/edit'),
        ];
    }
}
