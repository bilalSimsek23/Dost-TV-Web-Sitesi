<?php

namespace App\Filament\Resources\Programs\RelationManagers;

use App\Filament\Concerns\PersistsTablePaginationInUrl;
use App\Filament\Resources\Episodes\Schemas\EpisodeForm;
use App\Models\Episode;
use App\Support\Youtube;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class EpisodesRelationManager extends RelationManager
{
    use PersistsTablePaginationInUrl;

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

                TextColumn::make('programSeries.name')
                    ->label('Seri')
                    ->badge()
                    ->color('warning')
                    ->placeholder('—')
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

                IconColumn::make('show_on_public')
                    ->label('Public')
                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-eye' : 'heroicon-o-eye-slash')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->tooltip(fn (bool $state): string => $state ? 'Sitede görünüyor' : 'Sitede gizli')
                    ->action(function (Episode $record) {
                        $newPublic = ! (bool) $record->show_on_public;
                        $record->update([
                            'show_on_public' => $newPublic,
                            'is_active' => $newPublic && $record->status === 'published',
                        ]);

                        $userName = auth()->user()?->name ?? 'Kullanıcı';
                        $programName = $record->program?->name ?? $this->getOwnerRecord()?->name ?? 'Program';
                        $epNumber = $record->episode_number ?: 'Bölüm';
                        $action = $newPublic ? 'published' : 'unpublished';
                        $msg = $newPublic
                            ? "{$userName}, {$programName} {$epNumber}. Bölüm'ü yayına aldı."
                            : "{$userName}, {$programName} {$epNumber}. Bölüm'ü yayından kaldırdı.";

                        \App\Services\Audit\AuditLogger::log(
                            action: $action,
                            message: $msg,
                            subject: $record,
                            subjectLabel: "{$programName} {$epNumber}. Bölüm",
                        );

                        Notification::make()
                            ->title($newPublic ? 'Bölüm sitede görünür yapıldı.' : 'Bölüm siteden gizlendi.')
                            ->success()
                            ->duration(2500)
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('sync_youtube_playlist')
                    ->label("YouTube ile Senkronize Et")
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn () => filled($this->getOwnerRecord()->youtube_playlist_url)
                        || $this->getOwnerRecord()->programSeasons()->whereNotNull('youtube_playlist_url')->exists()
                        || $this->getOwnerRecord()->programSeries()->whereNotNull('youtube_playlist_url')->exists())
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

                        $userName = auth()->user()?->name ?? 'Kullanıcı';
                        $programName = $this->getOwnerRecord()?->name ?? 'Program';
                        \App\Services\Audit\AuditLogger::log(
                            action: 'synced',
                            message: "{$userName}, {$programName} YouTube senkronizasyonunu çalıştırdı.",
                            subject: $this->getOwnerRecord(),
                            subjectLabel: $programName,
                            metadata: [
                                'total_items' => $total,
                                'created_episodes' => $new,
                                'updated_episodes' => $updated,
                            ]
                        );

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
                    })
                    ->after(function (Episode $record) {
                        $userName = auth()->user()?->name ?? 'Kullanıcı';
                        $programName = $record->program?->name ?? $this->getOwnerRecord()?->name ?? 'Program';
                        $epNumber = $record->episode_number ?: 'Yeni';
                        \App\Services\Audit\AuditLogger::log(
                            action: 'created',
                            message: "{$userName}, {$programName} {$epNumber}. Bölüm'ü ekledi.",
                            subject: $record,
                            subjectLabel: "{$programName} {$epNumber}. Bölüm",
                        );
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->after(function (Episode $record) {
                        $userName = auth()->user()?->name ?? 'Kullanıcı';
                        $programName = $record->program?->name ?? $this->getOwnerRecord()?->name ?? 'Program';
                        $epNumber = $record->episode_number ?: 'Bölüm';
                        \App\Services\Audit\AuditLogger::log(
                            action: 'updated',
                            message: "{$userName}, {$programName} {$epNumber}. Bölüm'ü düzenledi.",
                            subject: $record,
                            subjectLabel: "{$programName} {$epNumber}. Bölüm",
                        );
                    }),
                DeleteAction::make()
                    ->before(function (Episode $record) {
                        $userName = auth()->user()?->name ?? 'Kullanıcı';
                        $programName = $record->program?->name ?? $this->getOwnerRecord()?->name ?? 'Program';
                        $epNumber = $record->episode_number ?: 'Bölüm';
                        \App\Services\Audit\AuditLogger::log(
                            action: 'deleted',
                            message: "{$userName}, {$programName} {$epNumber}. Bölüm'ü sildi.",
                            subject: $record,
                            subjectLabel: "{$programName} {$epNumber}. Bölüm",
                            isDestructive: true,
                        );
                    }),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
