<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Program;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VideoArchivePage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected string $view = 'filament.pages.video-archive';

    protected static ?string $slug = 'video-archive';

    protected static string|\UnitEnum|null $navigationGroup = 'Program ve Video Yönetimi';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Video Arşivi';

    protected static ?string $title = 'Video Arşivi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    public function table(Table $table): Table
    {
        return $table
            ->query(Program::query()->where('status', 'archived'))
            ->columns([
                TextColumn::make('name')
                    ->label('Program Adı')
                    ->searchable(['name', 'description', 'short_description'])
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('categories.name')
                    ->label('Kategoriler')
                    ->badge()
                    ->color('primary')
                    ->separator(', '),

                TextColumn::make('episodes_count')
                    ->label('Bölüm Sayısı')
                    ->counts('episodes')
                    ->sortable(),

                IconColumn::make('show_on_public')
                    ->label('Public Görünürlük')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('status')
                    ->label('Arşiv Durumu')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Program::STATUSES[$state] ?? $state)
                    ->color('danger'),
            ])
            ->filters([
                SelectFilter::make('categories')
                    ->label('Kategori')
                    ->relationship('categories', 'name'),

                TernaryFilter::make('show_on_public')
                    ->label('Public Görünürlük'),

                Filter::make('has_episodes')
                    ->label('Bölümü Olanlar')
                    ->query(fn (Builder $query) => $query->whereHas('episodes')),

                Filter::make('no_episodes')
                    ->label('Bölümü Olmayanlar')
                    ->query(fn (Builder $query) => $query->whereDoesntHave('episodes')),
            ])
            ->recordActions([
                Action::make('edit_program')
                    ->label('Programı Düzenle')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (Program $record) => url("/admin/programs/{$record->id}/edit")),

                Action::make('view_episodes')
                    ->label('Bölümleri Gör')
                    ->icon('heroicon-o-film')
                    ->color('amber')
                    ->url(fn (Program $record) => url("/admin/episodes?tableFilters[program_id][value]={$record->id}")),

                Action::make('preview_public')
                    ->label('Public Önizle')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->action(function (Program $record, Action $action) {
                        if (! $record->show_on_public) {
                            Notification::make()
                                ->title('Program Public Olarak Kapalı')
                                ->body('Bu program public sitede görünür (show_on_public) durumda değildir.')
                                ->warning()
                                ->send();
                        }

                        $url = route('programs.show', $record);
                        $action->getLivewire()->js("window.open('{$url}', '_blank')");
                    }),

                Action::make('unarchive')
                    ->label('Arşivden Çıkar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->modalHeading('Programı Arşivden Çıkar')
                    ->modalDescription('Lütfen programın yayındaki yeni durumunu seçin.')
                    ->form([
                        Select::make('new_status')
                            ->label('Yeni Program Durumu')
                            ->options([
                                'active' => 'Aktif',
                                'season_break' => 'Sezon Arasında',
                                'completed' => 'Sona Erdi',
                            ])
                            ->required()
                            ->placeholder('Durum seçiniz...'),
                    ])
                    ->action(function (Program $record, array $data) {
                        $newStatus = $data['new_status'];
                        $isPublic = $newStatus === 'active';
                        $record->update([
                            'status' => $newStatus,
                            'show_on_public' => $isPublic,
                            'is_active' => $isPublic,
                        ]);

                        Notification::make()
                            ->title("{$record->name} arşivden çıkarıldı (" . (Program::STATUSES[$newStatus] ?? $newStatus) . ').')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
