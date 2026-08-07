<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('drag_handle')
                    ->label('')
                    ->state(fn (Category $record) => '
                        <button type="button" 
                                class="drag-handle cursor-grab active:cursor-grabbing p-1.5 text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 font-bold select-none text-base transition-colors" 
                                data-id="' . $record->id . '" 
                                data-parent-id="' . ($record->parent_id ?? 'root') . '" 
                                aria-label="' . e($record->name) . ' kategorisini taşı" 
                                title="Sürükleyerek sırala">
                            ⋮⋮
                        </button>
                    ')
                    ->html(),

                TextColumn::make('name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('parent.name')
                    ->label('Üst Kategori')
                    ->placeholder('Ana Kategori')
                    ->sortable(),

                TextColumn::make('programs_count')
                    ->label('Program Sayısı')
                    ->counts('programs')
                    ->sortable(),

                ToggleColumn::make('show_in_menu')
                    ->label('Menü'),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktif/Pasif'),

                TernaryFilter::make('is_featured')
                    ->label('Öne Çıkan'),

                TernaryFilter::make('show_in_menu')
                    ->label('Menüde Gösterilen'),

                SelectFilter::make('parent_id')
                    ->label('Üst Kategori')
                    ->relationship('parent', 'name'),

                Filter::make('only_roots')
                    ->label('Yalnızca Ana Kategoriler')
                    ->query(fn (Builder $query): Builder => $query->whereNull('parent_id')),

                Filter::make('only_children')
                    ->label('Yalnızca Alt Kategoriler')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('parent_id')),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('deactivate')
                    ->label('Pasife Al')
                    ->icon(Heroicon::OutlinedEyeSlash)
                    ->visible(fn (Category $record) => $record->is_active && $record->slug !== Category::ALL_CATEGORIES_SLUG)
                    ->action(fn (Category $record) => $record->update(['is_active' => false]))
                    ->requiresConfirmation(),
                DeleteAction::make()
                    ->hidden(fn (Category $record) => $record->slug === Category::ALL_CATEGORIES_SLUG)
                    ->modalDescription(function (Category $record) {
                        $programs = $record->programs()->count();
                        $episodes = $record->episodesCount();

                        if ($programs === 0 && $episodes === 0) {
                            return 'Bu kategoriyi silmek istediğinize emin misiniz?';
                        }

                        return "Bu kategoriye bağlı {$programs} program ve {$episodes} bölüm var. Kategori silinirse bu içerikler kategorisiz kalır (kendileri silinmez). Veri kaybetmemek için 'Pasife Al' seçeneğini de değerlendirebilirsiniz.";
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
