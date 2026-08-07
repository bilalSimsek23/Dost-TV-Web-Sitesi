<?php

namespace App\Filament\Pages\SiteLayout;

use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\Programs\ProgramResource;
use App\Filament\Pages\ScheduleCalendarPage;
use App\Models\SiteSetting;
use App\Services\Home\HomepageDataService;
use App\Support\SiteCache;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class HomepageLayoutPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.site-layout.homepage-layout';

    protected static ?string $navigationLabel = 'Ana Sayfa Düzeni';

    protected static string|\UnitEnum|null $navigationGroup = 'Site Düzeni';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $title = 'Ana Sayfa Düzen Yönetimi';

    protected static ?string $slug = 'site-layout/homepage';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'administrator', 'designer']) ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::current();
        $this->form->fill([
            'homepage_sections' => $settings->normalized_homepage_sections,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('HomepageTabs')
                    ->tabs([
                        Tab::make('Düzen & Sıralama')
                            ->schema([
                                Repeater::make('homepage_sections')
                                    ->label('Ana Sayfa Bölümleri')
                                    ->itemLabel(fn (array $state): string => SiteSetting::CANONICAL_HOMEPAGE_SECTIONS[$state['key'] ?? ''] ?? 'Bölüm')
                                    ->reorderable(true)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->schema([
                                        Select::make('key')
                                            ->label('Bölüm Adı')
                                            ->options(SiteSetting::CANONICAL_HOMEPAGE_SECTIONS)
                                            ->disabled()
                                            ->dehydrated()
                                            ->required(),

                                        Toggle::make('visible')
                                            ->label('Göster')
                                            ->default(true),

                                        \Filament\Forms\Components\Placeholder::make('manage_link')
                                            ->label('İçerik Yönlendirmesi')
                                            ->content(function (callable $get) {
                                                $key = $get('key');
                                                $url = match ($key) {
                                                    'hero' => BannerResource::getUrl(),
                                                    'live_intro' => HeaderLayoutPage::getUrl(),
                                                    'today_schedule' => class_exists(ScheduleCalendarPage::class) ? ScheduleCalendarPage::getUrl() : '/admin',
                                                    'featured_programs' => ProgramResource::getUrl(),
                                                    default => '/admin',
                                                };

                                                $label = match ($key) {
                                                    'hero' => 'Banner / Manşetleri Yönet',
                                                    'live_intro' => 'Header / Tanıtım Ayarlarını Yönet',
                                                    'today_schedule' => 'Yayın Akışını Yönet',
                                                    'featured_programs' => 'Programları Yönet',
                                                    default => 'Yönet',
                                                };

                                                return new HtmlString('<a href="' . e($url) . '" class="inline-flex items-center text-xs font-semibold text-rose-400 hover:underline">&rarr; ' . e($label) . '</a>');
                                            }),
                                    ])
                                    ->columns(3),
                            ]),

                        Tab::make('Canlı Önizleme')
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('preview_box')
                                    ->label('')
                                    ->content(function (callable $get) {
                                        $formSections = $get('homepage_sections');
                                        $normalized = (new SiteSetting(['homepage_sections' => $formSections]))->normalized_homepage_sections;

                                        $dataService = app(HomepageDataService::class);
                                        $homepageData = $dataService->getHomepageData($normalized);

                                        return view('filament.pages.site-layout.homepage-preview', [
                                            'homepageSections' => $normalized,
                                            'settings' => $homepageData['settings'],
                                            'banners' => $homepageData['banners'],
                                            'featuredPrograms' => $homepageData['featuredPrograms'],
                                            'todaySchedule' => $homepageData['todaySchedule'],
                                        ]);
                                    }),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = SiteSetting::current();

        $settings->update([
            'homepage_sections' => $data['homepage_sections'] ?? SiteSetting::getDefaultHomepageSections(),
        ]);

        SiteCache::forgetHomepage();

        Notification::make()
            ->title('Ana Sayfa Düzeni Kaydedildi')
            ->body('Bölüm sıralaması ve görünürlük ayarları başarıyla güncellendi.')
            ->success()
            ->send();
    }
}
