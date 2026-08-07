<?php

namespace App\Filament\Resources\Episodes\Tables;

use App\Models\Episode;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class EpisodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('program.name')
                    ->label('Program')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('amber'),

                TextColumn::make('episode_number')
                    ->label('Bölüm No')
                    ->formatStateUsing(function ($state, Episode $record) {
                        $season = $record->season_number ? "S{$record->season_number} " : '';
                        $ep = $state ? "B{$state}" : '-';
                        return "{$season}{$ep}";
                    })
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Bölüm Başlığı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('video_source')
                    ->label('Video Durumu')
                    ->badge()
                    ->formatStateUsing(fn ($state, Episode $record) => match ($state) {
                        'youtube' => 'YouTube',
                        'upload' => 'Yüklenmiş Video',
                        'vimeo' => 'Vimeo',
                        'hls' => 'HLS Stream',
                        default => (filled($record->youtube_url) || filled($record->video_path)) ? 'Video Var' : 'Video Yok',
                    })
                    ->color(fn ($state, Episode $record) => match ($state) {
                        'youtube' => 'danger',
                        'upload', 'vimeo', 'hls' => 'info',
                        default => (filled($record->youtube_url) || filled($record->video_path)) ? 'info' : 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Episode::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'published' => 'success',
                        'ready' => 'warning',
                        'draft' => 'gray',
                        'archived' => 'danger',
                        default => 'info',
                    }),

                IconColumn::make('show_on_public')
                    ->label('Public')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('aired_at')
                    ->label('Yayın Tarihi')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('program_id')
                    ->label('Program')
                    ->relationship('program', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Bölüm Durumu')
                    ->options(Episode::STATUSES),

                TernaryFilter::make('show_on_public')
                    ->label('Public Görünürlük'),

                SelectFilter::make('video_source')
                    ->label('Video Kaynağı')
                    ->options([
                        'youtube' => 'YouTube Linki',
                        'upload' => 'Sunucuya Yükle',
                        'vimeo' => 'Vimeo Linki',
                        'hls' => 'HLS Stream',
                    ]),
            ])
            ->actions([
                EditAction::make(),

                Action::make('open_video')
                    ->label('Videoyu Aç')
                    ->icon('heroicon-o-play-circle')
                    ->color('gray')
                    ->visible(fn (Episode $record) => filled($record->youtube_url) || filled($record->video_path))
                    ->url(fn (Episode $record) => $record->youtube_url ?: Storage::disk('public')->url($record->video_path))
                    ->openUrlInNewTab(),

                Action::make('go_to_program')
                    ->label('Programa Git')
                    ->icon('heroicon-o-rectangle-stack')
                    ->color('amber')
                    ->url(fn (Episode $record) => url("/admin/programs/{$record->program_id}/edit")),

                Action::make('add_to_schedule')
                    ->label('Yayın Akışına Ekle')
                    ->icon('heroicon-o-calendar')
                    ->color('info')
                    ->url(fn () => url('/admin/schedule-calendar')),

                Action::make('archive')
                    ->label('Arşivle')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->visible(fn (Episode $record) => $record->status !== 'archived')
                    ->requiresConfirmation()
                    ->action(function (Episode $record) {
                        $record->update([
                            'status' => 'archived',
                            'show_on_public' => false,
                            'is_active' => false,
                        ]);
                        Notification::make()
                            ->title("{$record->title} arşive kaldırıldı.")
                            ->warning()
                            ->send();
                    }),

                Action::make('unarchive')
                    ->label('Arşivden Çıkar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (Episode $record) => $record->status === 'archived')
                    ->action(function (Episode $record) {
                        $record->update([
                            'status' => 'published',
                            'show_on_public' => true,
                            'is_active' => true,
                        ]);
                        Notification::make()
                            ->title("{$record->title} tekrar yayına alındı.")
                            ->success()
                            ->send();
                    }),

                Action::make('toggle_public')
                    ->label(fn (Episode $record) => $record->show_on_public ? 'Pasife Al' : 'Aktif Et')
                    ->icon(fn (Episode $record) => $record->show_on_public ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Episode $record) => $record->show_on_public ? 'gray' : 'success')
                    ->action(function (Episode $record) {
                        $newPublic = ! $record->show_on_public;
                        $record->update([
                            'show_on_public' => $newPublic,
                            'is_active' => $newPublic && $record->status === 'published',
                        ]);
                        Notification::make()
                            ->title("{$record->title} public görünürlüğü güncellendi.")
                            ->send();
                    }),

                DeleteAction::make(),
            ]);
    }
}
