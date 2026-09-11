<?php

namespace App\Filament\Resources\InstagramCategories;

use App\Filament\Resources\InstagramCategories\Pages\CreateInstagramCategory;
use App\Filament\Resources\InstagramCategories\Pages\EditInstagramCategory;
use App\Filament\Resources\InstagramCategories\Pages\ListInstagramCategories;
use App\Filament\Resources\InstagramCategories\Schemas\InstagramCategoryForm;
use App\Filament\Resources\InstagramCategories\Tables\InstagramCategoriesTable;
use App\Models\InstagramCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InstagramCategoryResource extends Resource
{
    protected static ?string $model = InstagramCategory::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\UnitEnum|null $navigationGroup = 'İçerik Yönetimi';

    protected static ?string $navigationLabel = 'Reel Kategorileri';

    protected static ?string $modelLabel = 'Reel Kategorisi';

    protected static ?string $pluralModelLabel = 'Reel Kategorileri';

    protected static ?int $navigationSort = 8;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return InstagramCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstagramCategoriesTable::configure($table);
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
            'index' => ListInstagramCategories::route('/'),
            'create' => CreateInstagramCategory::route('/create'),
            'edit' => EditInstagramCategory::route('/{record}/edit'),
        ];
    }
}
