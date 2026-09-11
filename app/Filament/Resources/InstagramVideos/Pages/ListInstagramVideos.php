<?php

namespace App\Filament\Resources\InstagramVideos\Pages;

use App\Filament\Resources\InstagramVideos\InstagramVideoResource;
use App\Models\InstagramCategory;
use App\Models\InstagramReelsSchedule;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ListInstagramVideos extends ListRecords implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = InstagramVideoResource::class;

    protected string $view = 'filament.resources.instagram-videos.pages.list-instagram-videos';

    public ?string $activeTab = 'reels'; // 'reels', 'categories', 'schedule', 'settings'

    public ?array $scheduleData = [];
    public ?array $settingsData = [];

    public function mount(): void
    {
        parent::mount();

        $tab = request()->query('tab');
        if ($tab && in_array($tab, ['reels', 'categories', 'schedule', 'settings'], true)) {
            $this->activeTab = $tab;
        }

        $schedule = InstagramReelsSchedule::current();

        $this->scheduleForm->fill([
            'monday_category_id' => $schedule->monday_category_id,
            'tuesday_category_id' => $schedule->tuesday_category_id,
            'wednesday_category_id' => $schedule->wednesday_category_id,
            'thursday_category_id' => $schedule->thursday_category_id,
            'friday_category_id' => $schedule->friday_category_id,
            'saturday_category_id' => $schedule->saturday_category_id,
            'sunday_category_id' => $schedule->sunday_category_id,
        ]);

        $this->settingsForm->fill([
            'sort_mode' => $schedule->sort_mode ?: 'latest',
        ]);
    }

    protected function getForms(): array
    {
        return [
            'scheduleForm',
            'settingsForm',
        ];
    }

    public function scheduleForm(Schema $schema): Schema
    {
        $categoryOptions = InstagramCategory::query()
            ->active()
            ->orderBy('sort_order', 'asc')
            ->pluck('name', 'id')
            ->toArray();

        return $schema
            ->statePath('scheduleData')
            ->components([
                Section::make('HAFTALIK REELS YAYIN PLANI')
                    ->description('Hangi gün hangi Instagram Reel kategorisinin gösterileceğini seçin. Saat 00:00 sonrası otomatik değişir.')
                    ->schema([
                        Select::make('monday_category_id')
                            ->label('Pazartesi')
                            ->options($categoryOptions)
                            ->nullable()
                            ->placeholder('Kategori Seçin'),

                        Select::make('tuesday_category_id')
                            ->label('Salı')
                            ->options($categoryOptions)
                            ->nullable()
                            ->placeholder('Kategori Seçin'),

                        Select::make('wednesday_category_id')
                            ->label('Çarşamba')
                            ->options($categoryOptions)
                            ->nullable()
                            ->placeholder('Kategori Seçin'),

                        Select::make('thursday_category_id')
                            ->label('Perşembe')
                            ->options($categoryOptions)
                            ->nullable()
                            ->placeholder('Kategori Seçin'),

                        Select::make('friday_category_id')
                            ->label('Cuma')
                            ->options($categoryOptions)
                            ->nullable()
                            ->placeholder('Kategori Seçin'),

                        Select::make('saturday_category_id')
                            ->label('Cumartesi')
                            ->options($categoryOptions)
                            ->nullable()
                            ->placeholder('Kategori Seçin'),

                        Select::make('sunday_category_id')
                            ->label('Pazar')
                            ->options($categoryOptions)
                            ->nullable()
                            ->placeholder('Kategori Seçin'),
                    ])
                    ->columns(2),
            ]);
    }

    public function settingsForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('settingsData')
            ->components([
                Section::make('GÖSTERİM VE SIRALAMA AYARI')
                    ->description('Günün kategorisi içindeki Reel videolarının ortak yayın sıralama kuralını belirleyin.')
                    ->schema([
                        Select::make('sort_mode')
                            ->label('Reel Sıralaması')
                            ->options([
                                'manual' => 'Manuel Sıra (Kategori içi sıralama)',
                                'latest' => 'En Yeni (Yayın tarihine göre en yeni önce)',
                                'oldest' => 'En Eski (Yayın tarihine göre en eski önce)',
                                'random' => 'Karışık (Gün boyunca sabit kalan karıştırma)',
                            ])
                            ->default('latest')
                            ->required()
                            ->helperText('Karışık seçildiğinde aynı gün boyunca sıralama sabit kalır; ertesi gün otomatik yenilenir.'),
                    ]),
            ]);
    }

    public function saveSchedule(): void
    {
        $data = $this->scheduleForm->getState();

        InstagramReelsSchedule::current()->update($data);

        Notification::make()
            ->title('Haftalık Reels Yayın Planı başarıyla kaydedildi.')
            ->success()
            ->send();
    }

    public function saveSettings(): void
    {
        $data = $this->settingsForm->getState();

        InstagramReelsSchedule::current()->update($data);

        Notification::make()
            ->title('Gösterim ayarları başarıyla kaydedildi.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        if ($this->activeTab === 'categories') {
            return [
                CreateAction::make('createCategory')
                    ->label('Yeni Kategori Ekle')
                    ->url(\App\Filament\Resources\InstagramCategories\InstagramCategoryResource::getUrl('create')),
            ];
        }

        if ($this->activeTab === 'reels') {
            return [
                CreateAction::make()
                    ->label('Instagram Videosu Ekle'),
            ];
        }

        return [];
    }
}
