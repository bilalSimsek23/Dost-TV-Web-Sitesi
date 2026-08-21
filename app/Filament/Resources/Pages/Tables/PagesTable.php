<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(Page::query()->where('page_type', 'corporate'))
            ->columns([
                TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable(['title', 'slug', 'content'])
                    ->sortable()
                    ->weight('bold'),
            ])
            ->filters([
                TernaryFilter::make('show_in_menu')
                    ->label('Menüde Gösterilenler'),

                TernaryFilter::make('show_in_header')
                    ->label('Header\'da Gösterilenler'),

                TernaryFilter::make('show_in_footer')
                    ->label('Footer\'da Gösterilenler'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Düzenle')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray'),

                Action::make('preview')
                    ->label('Önizle')
                    ->tooltip('Public Sayfayı Aç')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Page $record) => url("/{$record->slug}"))
                    ->openUrlInNewTab(),
            ]);
    }
}
