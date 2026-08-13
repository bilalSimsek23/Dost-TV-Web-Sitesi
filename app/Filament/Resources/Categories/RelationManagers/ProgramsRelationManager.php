<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use App\Models\Program;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProgramsRelationManager extends RelationManager
{
    protected static string $relationship = 'programs';

    protected static ?string $title = 'Bu Kategorideki Programlar';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('name', 'asc')
            ->columns([
                TextColumn::make('name')
                    ->label('Program Adı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => 'Aktif',
                        'passive' => 'Pasif',
                        'draft' => 'Taslak',
                        default => $state ?? 'Aktif',
                    })
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'passive' => 'danger',
                        'draft' => 'gray',
                        default => 'info',
                    }),

                TextColumn::make('episodes_count')
                    ->label('Bölüm Sayısı')
                    ->counts('episodes')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Public')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('+ Program Ata')
                    ->preloadRecordSelect()
                    ->recordTitleAttribute('name'),
            ])
            ->recordActions([
                Action::make('go_to_program')
                    ->label('Programa Git')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (Program $record) => \App\Filament\Resources\Programs\ProgramResource::getUrl('edit', ['record' => $record])),


                DetachAction::make()
                    ->label('Kategoriden Çıkar')
                    ->color('danger'),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
