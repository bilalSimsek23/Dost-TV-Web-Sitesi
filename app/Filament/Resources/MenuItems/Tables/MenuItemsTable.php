<?php

namespace App\Filament\Resources\MenuItems\Tables;

use App\Models\MenuItem;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

/**
 * Shared table pieces used by both the standalone MenuItemResource table and
 * MenuResource's nested MenuItemsRelationManager, so columns/filters/actions
 * are defined in exactly one place.
 */
class MenuItemsTable
{
    /**
     * @return array<int, \Filament\Tables\Columns\Column>
     */
    public static function columns(bool $includeMenuColumn = true): array
    {
        return [
            TextColumn::make('title')
                ->label('Başlık')
                ->searchable(),

            ...($includeMenuColumn ? [
                TextColumn::make('menu.name')
                    ->label('Menü')
                    ->searchable(),
            ] : []),

            TextColumn::make('parent.title')
                ->label('Üst Öğe')
                ->placeholder('—'),

            TextColumn::make('item_type')
                ->label('Tür')
                ->badge()
                ->formatStateUsing(fn (string $state) => MenuItem::ITEM_TYPES[$state] ?? $state),

            TextColumn::make('resolved_url')
                ->label('URL / Rota')
                ->state(fn (MenuItem $record) => $record->resolved_url ?? '—')
                ->limit(40),

            IconColumn::make('show_on_desktop')
                ->label('Masaüstü')
                ->boolean(),

            IconColumn::make('show_on_mobile')
                ->label('Mobil')
                ->boolean(),

            ToggleColumn::make('is_active')
                ->label('Aktif'),

            TextColumn::make('sort_order')
                ->label('Sıra')
                ->numeric()
                ->sortable(),
        ];
    }

    /**
     * @return array<int, \Filament\Tables\Filters\BaseFilter>
     */
    public static function filters(bool $includeMenuFilter = true): array
    {
        return [
            ...($includeMenuFilter ? [
                SelectFilter::make('menu_id')
                    ->label('Menü')
                    ->relationship('menu', 'name'),
            ] : []),
            SelectFilter::make('item_type')
                ->label('Tür')
                ->options(MenuItem::ITEM_TYPES),
            TernaryFilter::make('is_active')
                ->label('Aktif/Pasif'),
        ];
    }

    /**
     * @return array<int, \Filament\Actions\Action>
     */
    public static function recordActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->modalDescription(fn (MenuItem $record) => $record->children()->count() > 0
                    ? "Bu öğenin {$record->children()->count()} alt öğesi var. Silindiğinde alt öğeler de birlikte silinir."
                    : 'Bu menü öğesini silmek istediğinize emin misiniz?'),
        ];
    }

    /**
     * @return array<int, \Filament\Actions\BulkActionGroup>
     */
    public static function toolbarActions(): array
    {
        return [
            BulkActionGroup::make([
                BulkAction::make('activate')
                    ->label('Aktif Yap')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('deactivate')
                    ->label('Pasif Yap')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ]),
        ];
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns())
            ->defaultSort('sort_order')
            ->filters(self::filters())
            ->recordActions(self::recordActions())
            ->toolbarActions(self::toolbarActions());
    }
}
