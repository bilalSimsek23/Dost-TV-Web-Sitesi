<?php

namespace App\Filament\Resources\YoutubeChannels;

use App\Filament\Resources\YoutubeChannels\Pages\CreateYoutubeChannel;
use App\Filament\Resources\YoutubeChannels\Pages\EditYoutubeChannel;
use App\Filament\Resources\YoutubeChannels\Pages\ListYoutubeChannels;
use App\Filament\Resources\YoutubeChannels\Schemas\YoutubeChannelForm;
use App\Filament\Resources\YoutubeChannels\Tables\YoutubeChannelsTable;
use App\Models\YoutubeChannel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class YoutubeChannelResource extends Resource
{
    protected static ?string $model = YoutubeChannel::class;

    protected static string|\UnitEnum|null $navigationGroup = 'İçerik Yönetimi';

    protected static ?string $navigationLabel = 'YouTube Kanalları';

    protected static ?string $modelLabel = 'YouTube Kanalı';

    protected static ?string $pluralModelLabel = 'YouTube Kanalları';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    public static function form(Schema $schema): Schema
    {
        return YoutubeChannelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return YoutubeChannelsTable::configure($table);
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
            'index' => ListYoutubeChannels::route('/'),
            'create' => CreateYoutubeChannel::route('/create'),
            'edit' => EditYoutubeChannel::route('/{record}/edit'),
        ];
    }
}
