<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use Filament\Actions\AttachAction;

use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProgramsRelationManager extends RelationManager
{
    protected static string $relationship = 'programs';

    protected static ?string $title = 'Programlar';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedTv;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Program Adı')
                ->required(),

            Toggle::make('is_active')
                ->label('Aktif'),

            Toggle::make('is_featured')
                ->label('Öne Çıkan'),

            TextInput::make('sort_order')
                ->label('Sıra')
                ->numeric(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('Kapak Görseli')
                    ->square(),

                TextColumn::make('title')
                    ->label('Program Adı')
                    ->searchable(),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),

                ToggleColumn::make('is_featured')
                    ->label('Öne Çıkan'),

                TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Güncellenme Tarihi')
                    ->dateTime(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Programa Kategori Ata')
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make()
                    ->label('Kategoriden Çıkar'),
            ]);
    }
}
