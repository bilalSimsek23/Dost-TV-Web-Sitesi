<?php

namespace App\Filament\Pages\SiteLayout;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page as PageModel;
use App\Models\SiteSetting;
use App\Support\SiteCache;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class FooterLayoutPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.site-layout.footer-layout';

    protected static ?string $navigationLabel = 'Footer';

    protected static string|\UnitEnum|null $navigationGroup = 'Site Düzeni';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $title = 'Footer Yönetimi';

    protected static ?string $slug = 'site-layout/footer';

    public ?array $data = [];

    public string $search = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'administrator', 'designer', 'editor']) ?? false;
    }

    public function mount(): void
    {
        $settings = SiteSetting::current();
        $this->form->fill($settings->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('footer-layout-tabs')
                    ->tabs([
                        Tab::make('Kurumsal')
                            ->schema([
                                Placeholder::make('corporate_pages_list')
                                    ->label('Kurumsal Bilgiler')
                                    ->content(function () {
                                        $createUrl = PageResource::getUrl('create');

                                        $query = PageModel::query()
                                            ->where('page_type', 'corporate');

                                        if (filled($this->search)) {
                                            $searchTerm = trim($this->search);
                                            $query->where('title', 'like', '%' . $searchTerm . '%');
                                        }

                                        $pages = $query
                                            ->orderBy('sort_order')
                                            ->orderBy('title')
                                            ->get();

                                        $rowsHtml = '';
                                        if ($pages->isNotEmpty()) {
                                            foreach ($pages as $page) {
                                                $editUrl = PageResource::getUrl('edit', ['record' => $page]);

                                                $contentType = match(true) {
                                                    str_contains($page->slug, 'kunye') => 'Künye Bilgisi',
                                                    str_contains($page->slug, 'hesap') || str_contains($page->slug, 'vakfi') => 'Hesap Bilgisi',
                                                    str_contains($page->slug, 'kvkk') || str_contains($page->slug, 'kisisel') || str_contains($page->slug, 'uyelik') || str_contains($page->slug, 'gizlilik') => 'Yasal Bilgi',
                                                    $page->page_type === 'legal' => 'Yasal Bilgi',
                                                    $page->page_type === 'contact' => 'İletişim Bilgisi',
                                                    default => 'Kurumsal Bilgi',
                                                };

                                                $rowsHtml .= "
                                                    <tr onclick=\"window.location.href='{$editUrl}'\" class='hover:bg-slate-800/60 transition cursor-pointer border-b border-slate-800/80 last:border-0'>
                                                        <td class='px-4 py-3.5 text-sm font-medium text-slate-100'>
                                                            {$page->title}
                                                        </td>
                                                        <td class='px-4 py-3.5 text-sm text-slate-300'>
                                                            {$contentType}
                                                        </td>
                                                        <td class='px-4 py-3.5 text-right text-xs'>
                                                            <a href='{$editUrl}' onclick='event.stopPropagation();' class='inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-medium transition'>
                                                                <span>Düzenle</span>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                ";
                                            }
                                        } else {
                                            $rowsHtml = "<tr><td colspan='3' class='p-4 text-slate-400 text-xs text-center'>Kurumsal bilgi bulunamadı.</td></tr>";
                                        }

                                        return new HtmlString("
                                            <div class='space-y-4'>
                                                <div class='flex flex-wrap items-center justify-between gap-3'>
                                                    <div class='relative min-w-[240px] sm:w-72'>
                                                        <input type='text' wire:model.live.debounce.250ms='search' placeholder='Kurumsal bilgi ara...' class='w-full px-3.5 py-2 rounded-lg bg-slate-900 border border-slate-700/80 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-rose-500 transition'>
                                                    </div>

                                                    <a href='{$createUrl}' class='px-3.5 py-2 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold shadow transition inline-flex items-center gap-1.5'>
                                                        <span>+ Yeni Kurumsal Bilgi</span>
                                                    </a>
                                                </div>

                                                <div class='rounded-xl border border-slate-700/80 bg-slate-900 overflow-hidden shadow-lg'>
                                                    <table class='w-full text-left border-collapse'>
                                                        <thead>
                                                            <tr class='bg-slate-950 border-b border-slate-800 text-xs font-semibold text-slate-400 uppercase tracking-wider'>
                                                                <th class='px-4 py-3'>Sayfa</th>
                                                                <th class='px-4 py-3'>İçerik Türü</th>
                                                                <th class='px-4 py-3 text-right'>İşlem</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class='divide-y divide-slate-800/80'>
                                                            {$rowsHtml}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        ");
                                    })
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('İletişim')
                            ->schema([
                                TextInput::make('phone')
                                    ->label('Telefon')
                                    ->placeholder('+90 (312) 341 21 21')
                                    ->maxLength(50)
                                    ->helperText('Footer İletişim sütununda tel: bağlantısı olarak gösterilir.'),

                                TextInput::make('email')
                                    ->label('E-Posta')
                                    ->email()
                                    ->placeholder('iletisim@dosttv.com')
                                    ->maxLength(100)
                                    ->helperText('Footer İletişim sütununda mailto: bağlantısı olarak gösterilir.'),
                            ]),

                        Tab::make('Sosyal Medya')
                            ->schema([
                                TextInput::make('instagram_url')
                                    ->label('Instagram')
                                    ->url()
                                    ->placeholder('https://instagram.com/dosttv'),

                                TextInput::make('facebook_url')
                                    ->label('Facebook')
                                    ->url()
                                    ->placeholder('https://facebook.com/dosttv'),

                                TextInput::make('youtube_url')
                                    ->label('YouTube')
                                    ->url()
                                    ->placeholder('https://youtube.com/@DostRadyoTV'),

                                TextInput::make('x_url')
                                    ->label('X / Twitter')
                                    ->url()
                                    ->placeholder('https://x.com/dosttv'),

                                TextInput::make('whatsapp_url')
                                    ->label('WhatsApp')
                                    ->url()
                                    ->placeholder('https://wa.me/903120000000'),

                                TextInput::make('telegram_url')
                                    ->label('Telegram')
                                    ->url()
                                    ->placeholder('https://t.me/dosttv'),
                            ]),

                        Tab::make('Alt Bilgi')
                            ->schema([
                                TextInput::make('copyright_text')
                                    ->label('Telif Metni')
                                    ->placeholder('© {year} Dost TV. Tüm hakları saklıdır.')
                                    ->helperText('Footer alt satırında yalnız bir kez gösterilir. {year} otomatik yıla dönüşür.')
                                    ->maxLength(150),
                            ]),

                        Tab::make('Önizleme')
                            ->schema([
                                Placeholder::make('footer_simulated_preview')
                                    ->hiddenLabel()
                                    ->content(function (callable $get) {
                                        $renderedFooter = view('components.site.footer', [
                                            'preview' => true,
                                            'phone' => $get('phone'),
                                            'email' => $get('email'),
                                            'facebookUrl' => $get('facebook_url'),
                                            'instagramUrl' => $get('instagram_url'),
                                            'xUrl' => $get('x_url'),
                                            'youtubeUrl' => $get('youtube_url'),
                                            'whatsappUrl' => $get('whatsapp_url'),
                                            'telegramUrl' => $get('telegram_url'),
                                            'copyrightText' => $get('copyright_text'),
                                        ])->render();

                                        return new HtmlString("
                                            <div class='w-full rounded-xl border border-slate-800 bg-slate-950 overflow-hidden shadow-2xl'>
                                                {$renderedFooter}
                                            </div>
                                        ");
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        SiteSetting::current()->update($data);
        SiteCache::forgetSiteSetting();

        Notification::make()
            ->title('Footer ayarları başarıyla kaydedildi.')
            ->success()
            ->send();
    }
}
