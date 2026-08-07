<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $title = 'Alt Kategoriler';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedRectangleGroup;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Alt Kategori Adı')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

            TextInput::make('slug')
                ->label('Slug (Adres Tanımlayıcı)')
                ->required()
                ->unique(ignoreRecord: true),

            Textarea::make('description')
                ->label('Açıklama'),

            Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),

            Toggle::make('show_in_menu')
                ->label('Menüde Göster')
                ->default(true),

            Toggle::make('show_in_mega_menu')
                ->label('Mega Menüde Göster')
                ->default(true),

            TextInput::make('sort_order')
                ->label('Sıra')
                ->numeric()
                ->default(0),

            ColorPicker::make('color')
                ->label('Renk'),

            ColorPicker::make('background_color')
                ->label('Arka Plan Rengi'),

            ColorPicker::make('text_color')
                ->label('Metin Rengi'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Ad')
                    ->searchable(),

                TextColumn::make('slug')
                    ->searchable(),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),

                ToggleColumn::make('show_in_menu')
                    ->label('Menüde Göster'),

                TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('programs_count')
                    ->label('Bağlı Program Sayısı')
                    ->counts('programs'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Yeni Alt Kategori Ekle')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['parent_id'] = $this->getOwnerRecord()->getKey();
                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
