<?php

namespace App\Filament\Resources\Menus\RelationManagers;

use App\Filament\Concerns\PersistsTablePaginationInUrl;
use App\Filament\Resources\MenuItems\Schemas\MenuItemForm;
use App\Filament\Resources\MenuItems\Tables\MenuItemsTable;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MenuItemsRelationManager extends RelationManager
{
    use PersistsTablePaginationInUrl;

    protected static string $relationship = 'items';

    protected static ?string $title = 'Menü Öğeleri';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('menu_id')
                ->default(fn () => $this->getOwnerRecord()->getKey()),
            ...MenuItemForm::components(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns(MenuItemsTable::columns(includeMenuColumn: false))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordTitleAttribute('title')
            ->filters(MenuItemsTable::filters(includeMenuFilter: false))
            ->recordActions(MenuItemsTable::recordActions())
            ->toolbarActions(MenuItemsTable::toolbarActions())
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
