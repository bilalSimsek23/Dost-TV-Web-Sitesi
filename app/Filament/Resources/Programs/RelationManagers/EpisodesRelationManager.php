<?php

namespace App\Filament\Resources\Programs\RelationManagers;

use App\Filament\Resources\Episodes\Schemas\EpisodeForm;
use App\Models\Episode;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EpisodesRelationManager extends RelationManager
{
    protected static string $relationship = 'episodes';

    protected static ?string $title = 'Bölümler';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('program_id')
                ->default(fn () => $this->getOwnerRecord()->getKey()),
            ...EpisodeForm::configure($schema)->getComponents(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->header(fn () => view('filament.resources.programs.episodes-relation-header', ['program' => $this->getOwnerRecord()]))
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(fn ($query) => $query->orderByRaw('CASE WHEN episode_number IS NULL THEN 1 ELSE 0 END')->orderBy('episode_number', 'desc')->orderBy('created_at', 'desc'))
            ->columns([
                TextColumn::make('episode_number')
                    ->label('Bölüm No')
                    ->formatStateUsing(function ($state, Episode $record) {
                        $season = $record->season_number ? "S{$record->season_number} " : '';
                        $ep = $state ? "B{$state}" : '-';
                        return "{$season}{$ep}";
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Bölüm Başlığı')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('video_source')
                    ->label('Video')
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
            ->headerActions([
                Action::make('sync_youtube_playlist')
                    ->label("YouTube'u Şimdi Kontrol Et")
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn () => filled($this->getOwnerRecord()->youtube_playlist_url))
                    ->action(function () {
                        $service = app(\App\Services\YouTube\YouTubePlaylistSyncService::class);
                        $result = $service->syncProgramPlaylist($this->getOwnerRecord(), false);

                        if (! ($result['success'] ?? true)) {
                            \Filament\Notifications\Notification::make()
                                ->title('YouTube kontrolü başarısız oldu.')
                                ->body($result['message'] ?? 'Bilinmeyen hata oluştu.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $checked = $result['total_items'] ?? 0;
                        $count = $result['created_episodes'] ?? 0;

                        if ($count > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title("{$checked} video kontrol edildi. {$count} yeni bölüm eklendi.")
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Yeni video bulunamadı.')
                                ->info()
                                ->send();
                        }
                    }),

                CreateAction::make()
                    ->label('+ Yeni Bölüm')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['program_id'] = $this->getOwnerRecord()->getKey();
                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make(),

                Action::make('open_youtube')
                    ->label("YouTube'da Aç")
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('danger')
                    ->visible(fn (Episode $record) => filled($record->youtube_url))
                    ->url(fn (Episode $record) => $record->canonical_url ?: $record->youtube_url)
                    ->openUrlInNewTab(),

                DeleteAction::make(),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
