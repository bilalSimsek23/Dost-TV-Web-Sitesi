<?php

namespace App\Filament\Resources\Programs\Schemas;

use App\Models\Episode;
use App\Models\Program;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ProgramForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('ProgramFormTabs')
                    ->tabs([
                        Tab::make('📌 Genel Bilgiler')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('name')
                                        ->label('Program Adı')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                                    TextInput::make('slug')
                                        ->label('Bağlantı Adresi (URL)')
                                        ->required()
                                        ->unique(ignoreRecord: true),
                                ]),

                                Textarea::make('short_description')
                                    ->label('Kısa Açıklama')
                                    ->placeholder('Program listelerinde ve kartlarda görünecek kısa özet...')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Textarea::make('description')
                                    ->label('Detaylı Açıklama')
                                    ->rows(4)
                                    ->columnSpanFull(),

                                Grid::make(3)->schema([
                                    Select::make('status')
                                        ->label('Program Durumu')
                                        ->options(Program::STATUSES)
                                        ->default('active')
                                        ->required(),

                                    Toggle::make('show_on_public')
                                        ->label('Public Sitede Göster')
                                        ->default(true),

                                    Toggle::make('is_featured')
                                        ->label('Öne Çıkan Program')
                                        ->default(false),
                                ]),

                                Grid::make(2)->schema([
                                    TextInput::make('sort_order')
                                        ->label('Sıralama Önceliği')
                                        ->numeric()
                                        ->default(0),

                                    TextInput::make('trailer_url')
                                        ->label('Tanıtım Fragmanı (YouTube URL)')
                                        ->placeholder('https://www.youtube.com/watch?v=...')
                                        ->url(),
                                ]),
                            ]),

                        Tab::make('🖼️ Görseller')
                            ->schema([
                                Grid::make(2)->schema([
                                    FileUpload::make('cover_image')
                                        ->label('Program Kapak Görseli (Dikey)')
                                        ->image()
                                        ->disk('public')
                                        ->directory('programs')
                                        ->maxSize(5120)
                                        ->helperText('Önerilen boyut: 1080 × 1350 px. Maksimum 5 MB.'),

                                    FileUpload::make('horizontal_image')
                                        ->label('Yatay Program Görseli')
                                        ->image()
                                        ->disk('public')
                                        ->directory('programs')
                                        ->maxSize(5120)
                                        ->helperText('Önerilen boyut: 1920 × 1080 px (16:9). Maksimum 5 MB.'),

                                    FileUpload::make('program_logo')
                                        ->label('Program Logosu')
                                        ->image()
                                        ->disk('public')
                                        ->directory('programs')
                                        ->maxSize(5120)
                                        ->helperText('Önerilen format: Şeffaf PNG veya WEBP. Maksimum 5 MB.'),

                                    FileUpload::make('default_episode_image')
                                        ->label('Varsayılan Bölüm Görseli')
                                        ->image()
                                        ->disk('public')
                                        ->directory('episodes')
                                        ->maxSize(5120)
                                        ->helperText('Görseli olmayan bölümler için kullanılır (1920 × 1080 px). Maksimum 5 MB.'),
                                ]),
                            ]),

                        Tab::make('🏷️ Kategoriler')
                            ->schema([
                                Select::make('categories')
                                    ->label('Kategoriler')
                                    ->relationship('categories', 'name')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Kategori Adı')
                                            ->required(),
                                    ]),
                            ]),

                        Tab::make('🎞️ Bölümler')
                            ->schema([
                                Placeholder::make('episodes_summary')
                                    ->label('Bölüm Yönetimi Özeti')
                                    ->content(function ($record) {
                                        if (! $record) {
                                            return new HtmlString('<div class="p-4 bg-gray-800 rounded-lg text-sm text-gray-400">Bölümleri görmek ve eklemek için önce programı kaydediniz.</div>');
                                        }

                                        $total = $record->episodes()->count();
                                        $episodes = $record->episodes()->latest()->take(5)->get();

                                        $list = '';
                                        foreach ($episodes as $ep) {
                                            $date = $ep->aired_at ? $ep->aired_at->format('d.m.Y') : 'Tarih Yok';
                                            $list .= "<li class='py-1 border-b border-gray-700/50 flex justify-between text-sm'><span class='font-medium text-white'>{$ep->title}</span><span class='text-gray-400'>{$date}</span></li>";
                                        }

                                        if (blank($list)) {
                                            $list = "<li class='py-2 text-sm text-gray-500 italic'>Bu programa henüz bir bölüm eklenmedi.</li>";
                                        }

                                        $openUrl = url("/admin/episodes?tableFilters[program_id][value]={$record->id}");
                                        $addUrl = url("/admin/episodes/create?program_id={$record->id}");

                                        return new HtmlString("
                                            <div class='space-y-4'>
                                                <div class='flex items-center gap-6 p-4 bg-gray-900 border border-gray-800 rounded-lg'>
                                                    <div>
                                                        <span class='text-2xl font-bold text-amber-400'>{$total}</span>
                                                        <span class='text-xs block text-gray-400 font-medium uppercase'>Toplam Bölüm</span>
                                                    </div>
                                                </div>

                                                <div>
                                                    <h4 class='text-sm font-semibold text-gray-300 mb-2'>Son Eklenen 5 Bölüm:</h4>
                                                    <ul class='bg-gray-900 border border-gray-800 rounded-lg p-3'>{$list}</ul>
                                                </div>

                                                <div class='flex items-center gap-3 pt-2'>
                                                    <a href='{$openUrl}' class='inline-flex items-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white text-sm font-semibold rounded-lg border border-gray-700 transition'>
                                                        📂 Tüm Bölümleri Aç
                                                    </a>
                                                    <a href='{$addUrl}' class='inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold rounded-lg transition'>
                                                        ➕ Yeni Bölüm Ekle
                                                    </a>
                                                </div>
                                            </div>
                                        ");
                                    })
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('📡 Yayın Bilgileri')
                            ->schema([
                                Placeholder::make('schedule_summary')
                                    ->label('Yayın Akışı Özeti')
                                    ->content(function ($record) {
                                        if (! $record) {
                                            return new HtmlString('<div class="p-4 bg-gray-800 rounded-lg text-sm text-gray-400">Yayın bilgilerini görmek için önce programı kaydediniz.</div>');
                                        }

                                        $totalSchedule = $record->schedules()->count();
                                        $calendarUrl = url('/admin/schedule-calendar');

                                        return new HtmlString("
                                            <div class='space-y-4'>
                                                <div class='p-4 bg-gray-900 border border-gray-800 rounded-lg'>
                                                    <span class='text-2xl font-bold text-amber-400'>{$totalSchedule}</span>
                                                    <span class='text-xs block text-gray-400 font-medium uppercase'>Yayın Akışı Kaydı</span>
                                                </div>

                                                <div class='pt-2'>
                                                    <a href='{$calendarUrl}' class='inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold rounded-lg transition'>
                                                        📅 Yayın Akışını Aç
                                                    </a>
                                                </div>
                                            </div>
                                        ");
                                    })
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('🔍 SEO')
                            ->schema([
                                TextInput::make('meta_title')
                                    ->label('Google Arama Başlığı (Meta Title)')
                                    ->placeholder('Boş bırakılırsa program adı kullanılır'),

                                Textarea::make('meta_description')
                                    ->label('Google Arama Açıklaması (Meta Description)')
                                    ->rows(3)
                                    ->placeholder('Programın Google arama sonuçlarında görünecek kısa özeti'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
