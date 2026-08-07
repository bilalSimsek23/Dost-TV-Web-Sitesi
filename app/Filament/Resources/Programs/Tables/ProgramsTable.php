<?php

namespace App\Filament\Resources\Programs\Tables;

use App\Models\Program;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProgramsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Program Adı')
                    ->searchable()
                    ->sortable(query: fn (Builder $query, string $direction) => static::applyTurkishSort($query, 'name', $direction))
                    ->weight('bold'),

                TextColumn::make('categories.name')
                    ->label('Kategoriler')
                    ->badge()
                    ->color('primary')
                    ->separator(', '),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Program::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'season_break' => 'warning',
                        'completed' => 'gray',
                        'archived' => 'danger',
                        default => 'info',
                    }),

                TextColumn::make('episodes_count')
                    ->label('Bölüm Sayısı')
                    ->counts('episodes')
                    ->sortable(),

                IconColumn::make('show_on_public')
                    ->label('Public')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray'),

                IconColumn::make('is_featured')
                    ->label('Öne Çıkan')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('amber')
                    ->falseColor('gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Program Durumu')
                    ->options(Program::STATUSES),

                SelectFilter::make('categories')
                    ->label('Kategori')
                    ->relationship('categories', 'name'),

                TernaryFilter::make('show_on_public')
                    ->label('Public Görünürlük'),

                TernaryFilter::make('is_featured')
                    ->label('Öne Çıkanlar'),
            ])
            ->actions([
                EditAction::make(),

                Action::make('preview')
                    ->label('Önizle')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Program $record) => route('programs.show', $record))
                    ->openUrlInNewTab(),

                Action::make('open_episodes')
                    ->label('Bölümleri Aç')
                    ->icon('heroicon-o-film')
                    ->color('amber')
                    ->url(fn (Program $record) => url("/admin/episodes?tableFilters[program_id][value]={$record->id}")),

                Action::make('add_episode')
                    ->label('Yeni Bölüm Ekle')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->url(fn (Program $record) => url("/admin/episodes/create?program_id={$record->id}")),

                Action::make('open_schedule')
                    ->label('Yayın Akışını Aç')
                    ->icon('heroicon-o-calendar')
                    ->color('info')
                    ->url(fn () => url('/admin/schedule-calendar')),

                Action::make('archive')
                    ->label('Arşivle')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->visible(fn (Program $record) => $record->status !== 'archived')
                    ->requiresConfirmation()
                    ->action(function (Program $record) {
                        $record->update([
                            'status' => 'archived',
                            'show_on_public' => false,
                            'is_active' => false,
                        ]);
                        Notification::make()
                            ->title("{$record->name} arşive kaldırıldı.")
                            ->warning()
                            ->send();
                    }),

                Action::make('unarchive')
                    ->label('Arşivden Çıkar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (Program $record) => $record->status === 'archived')
                    ->action(function (Program $record) {
                        $record->update([
                            'status' => 'active',
                            'show_on_public' => true,
                            'is_active' => true,
                        ]);
                        Notification::make()
                            ->title("{$record->name} tekrar aktif yapıldı.")
                            ->success()
                            ->send();
                    }),

                Action::make('toggle_public')
                    ->label(fn (Program $record) => $record->show_on_public ? 'Pasife Al' : 'Aktif Et')
                    ->icon(fn (Program $record) => $record->show_on_public ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Program $record) => $record->show_on_public ? 'gray' : 'success')
                    ->action(function (Program $record) {
                        $newPublic = ! $record->show_on_public;
                        $record->update([
                            'show_on_public' => $newPublic,
                            'is_active' => $newPublic && $record->status === 'active',
                        ]);
                        Notification::make()
                            ->title("{$record->name} public görünürlüğü güncellendi.")
                            ->send();
                    }),

                DeleteAction::make()
                    ->visible(fn (Program $record) => $record->episodes()->count() === 0 && $record->schedules()->count() === 0),
            ]);
    }

    public static function applyTurkishSort(Builder $query, string $column = 'name', string $direction = 'asc'): Builder
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $connection = $query->getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $version = '';
            try {
                $versionResult = $connection->select('SELECT VERSION() as v');
                $version = $versionResult[0]->v ?? '';
            } catch (\Throwable $e) {
                // Fallback if version check fails
            }

            $isMariaDb = str_contains(strtolower($version), 'mariadb');
            $isMysql8 = ! $isMariaDb && version_compare($version, '8.0.0', '>=');

            $collation = $isMysql8 ? 'utf8mb4_tr_0900_ai_ci' : 'utf8mb4_turkish_ci';

            return $query->orderByRaw("{$column} COLLATE {$collation} {$direction}");
        }

        if ($driver === 'sqlite') {
            return $query->orderByRaw("LOWER({$column}) {$direction}");
        }

        return $query->orderBy($column, $direction);
    }
}

