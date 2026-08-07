<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Models\MenuItem;
use App\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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

                TextColumn::make('page_type')
                    ->label('İçerik Türü')
                    ->badge()
                    ->formatStateUsing(fn ($state, Page $record) => match ($record->slug) {
                        'iletisim' => 'İletişim',
                        'yayinci-kunye-bilgisi' => 'Künye Bilgisi',
                        'dost-vakfi-hesap-numaralari' => 'Destek / Hesap Bilgisi',
                        'kisisel-verilerin-korunmasi-ve-gizlilik-politikasi', 'uyelik-kosullari' => 'Yasal Bilgi',
                        default => 'Kurumsal Bilgi',
                    })
                    ->color('primary'),

                TextColumn::make('location')
                    ->label('Sitedeki Konum')
                    ->badge()
                    ->state(function (Page $record) {
                        $menuItem = MenuItem::where('linked_model_type', 'page')
                            ->where('linked_model_id', $record->id)
                            ->first();

                        if ($menuItem) {
                            return 'TOP HEADER (Kurumsal Menü)';
                        }

                        if ($record->show_in_header) {
                            return 'TOP HEADER';
                        }

                        if ($record->show_in_footer) {
                            return 'Footer';
                        }

                        if ($record->show_in_menu) {
                            return 'Kurumsal Menü';
                        }

                        return 'Doğrudan Bağlantı';
                    })
                    ->color(fn ($state) => str_contains($state, 'HEADER') ? 'amber' : (str_contains($state, 'Footer') ? 'info' : 'gray')),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Page::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'published' => 'success',
                        'review' => 'warning',
                        'draft' => 'gray',
                        'archived' => 'danger',
                        default => 'info',
                    }),

                TextColumn::make('slug')
                    ->label('Public URL')
                    ->formatStateUsing(fn ($state) => "/{$state}")
                    ->color('primary')
                    ->url(fn (Page $record) => url("/{$record->slug}"))
                    ->openUrlInNewTab(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Durum')
                    ->options(Page::STATUSES),

                TernaryFilter::make('show_in_menu')
                    ->label('Menüde Gösterilenler'),

                TernaryFilter::make('show_in_header')
                    ->label('Header\'da Gösterilenler'),

                TernaryFilter::make('show_in_footer')
                    ->label('Footer\'da Gösterilenler'),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Önizle')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->action(function (Page $record, Action $action) {
                        if ($record->status !== 'published') {
                            Notification::make()
                                ->title('İçerik Yayında Değil')
                                ->body('Bu içerik şu anda public sitede yayınlanmıyor.')
                                ->warning()
                                ->send();
                        }

                        $url = url("/{$record->slug}");
                        $action->getLivewire()->js("window.open('{$url}', '_blank')");
                    }),

                EditAction::make(),

                Action::make('view_menu_location')
                    ->label('Menü Konumu')
                    ->icon('heroicon-o-map-pin')
                    ->color('amber')
                    ->action(function (Page $record) {
                        $menuItem = MenuItem::where('linked_model_type', 'page')
                            ->where('linked_model_id', $record->id)
                            ->first();

                        if ($menuItem) {
                            $parent = $menuItem->parent ? "{$menuItem->parent->title} → " : '';
                            Notification::make()
                                ->title('Menü Bağlantı Bilgisi')
                                ->body("Bu içerik '{$parent}{$menuItem->title}' menü ögesine bağlıdır.")
                                ->info()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Menü Bağlantısı Yok')
                                ->body('Bu içerik şu anda hiçbir menüye doğrudan bağlı değildir.')
                                ->warning()
                                ->send();
                        }
                    }),
            ]);
    }
}
