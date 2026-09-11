<?php

namespace App\Filament\Resources\ProgramCollections\RelationManagers;

use App\Models\Program;
use App\Models\ProgramCollection;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProgramsRelationManager extends RelationManager
{
    protected static string $relationship = 'programs';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return in_array($ownerRecord->source_type, ['manual', 'hybrid'], true);
    }

    public function getTableHeading(): ?string
    {
        /** @var ProgramCollection $collection */
        $collection = $this->getOwnerRecord();

        if ($collection->source_type === 'hybrid') {
            return 'Sabitlenen Programlar';
        }

        return 'Koleksiyondaki Programlar';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Program Adı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('categories.name')
                    ->label('Kategoriler')
                    ->badge()
                    ->separator(', '),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Program::STATUSES[$state] ?? $state),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('+ Program Ekle')
                    ->modalHeading('Koleksiyona Program Ekle')
                    ->recordSelect(
                        fn (Select $select) => $select
                            ->placeholder('Koleksiyona eklenecek programı arayın veya seçin...')
                            ->searchable()
                            ->options(function () {
                                /** @var ProgramCollection $ownerRecord */
                                $ownerRecord = $this->getOwnerRecord();
                                $attachedIds = $ownerRecord->programs()->pluck('programs.id')->all();

                                return Program::query()
                                    ->where('show_on_public', true)
                                    ->where('is_active', true)
                                    ->when(! empty($attachedIds), fn ($q) => $q->whereNotIn('id', $attachedIds))
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->getSearchResultsUsing(function (string $search): array {
                                /** @var ProgramCollection $ownerRecord */
                                $ownerRecord = $this->getOwnerRecord();
                                $attachedIds = $ownerRecord->programs()->pluck('programs.id')->all();

                                return Program::query()
                                    ->where('show_on_public', true)
                                    ->where('is_active', true)
                                    ->when(! empty($attachedIds), fn ($q) => $q->whereNotIn('id', $attachedIds))
                                    ->where('name', 'like', "%{$search}%")
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                    ),
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Koleksiyondan Çıkar')
                    ->modalHeading('Programı Koleksiyondan Çıkar')
                    ->modalDescription('Bu işlem programı koleksiyondan çıkarır. Program sistemden silinmez.')
                    ->color('danger'),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(50);
    }
}
