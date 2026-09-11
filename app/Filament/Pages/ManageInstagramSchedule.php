<?php

namespace App\Filament\Pages;

use App\Models\InstagramCategory;
use App\Models\InstagramReelsSchedule;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageInstagramSchedule extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.manage-instagram-schedule';

    protected static ?string $navigationLabel = 'Haftalık Reels Planı';

    protected static string|\UnitEnum|null $navigationGroup = 'İçerik Yönetimi';

    protected static ?int $navigationSort = 9;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $title = 'Haftalık Reels Yayın Takvimi';

    protected static ?string $slug = 'instagram-reels-schedule';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public ?array $data = [];

    public function getSubheading(): ?string
    {
        return 'Hangi gün hangi Instagram Reel kategorisinin gösterileceğini ve videoların sıralama kuralını belirleyin.';
    }

    public function mount(): void
    {
        $schedule = InstagramReelsSchedule::current();
        $this->form->fill($schedule->toArray());
    }

    public function form(Schema $schema): Schema
    {
        $categoryOptions = InstagramCategory::query()
            ->active()
            ->orderBy('sort_order', 'asc')
            ->pluck('name', 'id')
            ->toArray();

        return $schema
            ->components([
                Section::make('HAFTALIK REELS YAYIN PLANI')
                    ->description('Her gün için görüntülenecek Instagram Reel kategorisini seçin. Saat 00:00 sonrası otomatik değişir.')
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

                Section::make('REELS SIRALAMA AYARI')
                    ->description('Günün kategorisi içindeki Reel videolarının sıralanma mantığı.')
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
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        InstagramReelsSchedule::current()->update($data);

        Notification::make()
            ->title('Haftalık Reels Yayın Planı kaydedildi.')
            ->success()
            ->send();
    }
}
