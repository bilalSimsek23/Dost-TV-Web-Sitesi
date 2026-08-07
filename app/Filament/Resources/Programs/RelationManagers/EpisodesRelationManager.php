<?php

namespace App\Filament\Resources\Programs\RelationManagers;

use App\Filament\Resources\Episodes\Schemas\EpisodeForm;
use App\Models\Episode;
use App\Support\Youtube;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

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
            ->defaultSort('episode_number', 'desc')
            ->columns([
                TextColumn::make('thumbnail')
                    ->label('Thumbnail')
                    ->formatStateUsing(function ($state, Episode $record) {
                        $imgUrl = null;
                        if (filled($state)) {
                            $imgUrl = Str::startsWith($state, ['http://', 'https://']) ? $state : asset('storage/' . $state);
                        } else {
                            $vId = Youtube::extractVideoId($record->youtube_url);
                            if ($vId) {
                                $imgUrl = "https://i.ytimg.com/vi/{$vId}/hqdefault.jpg";
                            }
                        }

                        if ($imgUrl) {
                            return new HtmlString("
                                <div class='w-[120px] h-[68px] aspect-[16/9] rounded overflow-hidden bg-gray-800 flex-shrink-0 border border-gray-800 select-none'>
                                    <img src='{$imgUrl}' class='w-full h-full object-cover' alt='Thumbnail' loading='lazy' onerror=\"this.onerror=null; this.parentElement.innerHTML='<div class=\\'w-full h-full flex items-center justify-center text-gray-500 text-xs font-semibold\\'>Visual</div>';\" />
                                </div>
                            ");
                        }

                        return new HtmlString("
                            <div class='w-[120px] h-[68px] aspect-[16/9] rounded bg-gray-800 flex items-center justify-center text-gray-400 text-xs font-semibold flex-shrink-0 select-none border border-gray-800'>
                                Görsel Yok
                            </div>
                        ");
                    }),

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
                    ->wrap()
                    ->tooltip(fn (Episode $record) => $record->title)
                    ->weight('bold'),

                TextColumn::make('aired_at')
                    ->label('Yayın Tarihi')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('youtube_url')
                    ->label('YouTube')
                    ->formatStateUsing(function ($state, Episode $record) {
                        $url = $record->canonical_url ?: $state;
                        if (blank($url)) {
                            return new HtmlString("<span class='text-gray-500 text-xs'>-</span>");
                        }

                        return new HtmlString("
                            <a href='{$url}' target='_blank' class='inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-500 text-white font-medium text-xs rounded-lg transition select-none shadow-sm'>
                                <span>▶</span>
                                <span>YouTube'da Aç ↗</span>
                            </a>
                        ");
                    }),
            ])
            ->headerActions([
                Action::make('sync_youtube_playlist')
                    ->label("YouTube ile Senkronize Et")
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn () => filled($this->getOwnerRecord()->youtube_playlist_url))
                    ->action(function () {
                        $service = app(\App\Services\YouTube\YouTubePlaylistSyncService::class);
                        $result = $service->syncProgramPlaylist($this->getOwnerRecord(), false, true);

                        if (! ($result['success'] ?? true)) {
                            \Filament\Notifications\Notification::make()
                                ->title('YouTube senkronizasyonu başarısız oldu.')
                                ->body($result['message'] ?? 'Bilinmeyen hata oluştu.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $total = $result['total_items'] ?? 0;
                        $new = $result['created_episodes'] ?? 0;
                        $updated = $result['updated_episodes'] ?? 0;
                        $unchanged = $result['unchanged_episodes'] ?? 0;
                        $errors = $result['errors'] ?? 0;

                        $summary = "Toplam video: {$total}\n"
                            . "Yeni eklenen: {$new}\n"
                            . "Güncellenen: {$updated}\n"
                            . "Değişmeyen: {$unchanged}\n"
                            . "Hatalı: {$errors}";

                        \Filament\Notifications\Notification::make()
                            ->title('YouTube Senkronizasyonu Tamamlandı')
                            ->body($summary)
                            ->success()
                            ->send();
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
                DeleteAction::make(),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
