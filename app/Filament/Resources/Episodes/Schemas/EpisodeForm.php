<?php

namespace App\Filament\Resources\Episodes\Schemas;

use App\Models\Episode;
use Filament\Forms\Components\DatePicker;
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

class EpisodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('EpisodeFormTabs')
                    ->tabs([
                        Tab::make('📌 Genel Bilgiler')
                            ->schema([
                                Select::make('program_id')
                                    ->label('Program')
                                    ->relationship('program', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(fn ($livewire) => ! (property_exists($livewire, 'contextProgramId') && filled($livewire->contextProgramId)) && ! request()->has('program_id'))
                                    ->disabled(fn ($livewire) => (property_exists($livewire, 'contextProgramId') && filled($livewire->contextProgramId)) || filled(request()->query('program_id')))
                                    ->dehydrated(true),

                                Grid::make(2)->schema([
                                    TextInput::make('season_number')
                                        ->label('Sezon Numarası')
                                        ->numeric()
                                        ->placeholder('Örn: 1')
                                        ->disabled(fn ($livewire) => (property_exists($livewire, 'contextSeasonNumber') && $livewire->contextSeasonNumber !== null) || filled(request()->query('season_number')))
                                        ->dehydrated(true),

                                    TextInput::make('episode_number')
                                        ->label('Bölüm Numarası')
                                        ->numeric()
                                        ->placeholder('Örn: 12'),
                                ]),

                                Grid::make(2)->schema([
                                    TextInput::make('title')
                                        ->label('Bölüm Başlığı')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                                    TextInput::make('slug')
                                        ->label('Sayfa Bağlantısı (Slug)')
                                        ->required()
                                        ->unique(ignoreRecord: true),
                                ]),

                                Textarea::make('description')
                                    ->label('Bölüm Detay Açıklaması')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('🎬 Video')
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('video_source')
                                        ->label('Video Kaynağı')
                                        ->options([
                                            'youtube' => 'YouTube Linki',
                                            'upload' => 'Sunucuya Yükle',
                                            'vimeo' => 'Vimeo Linki',
                                            'hls' => 'HLS Stream (.m3u8)',
                                            'iframe' => 'iFrame / Gömülü Oyuncu',
                                            'custom' => 'Harici Bağlantı',
                                        ])
                                        ->default('youtube')
                                        ->live()
                                        ->required(),

                                    TextInput::make('duration')
                                        ->label('Video Süresi')
                                        ->placeholder('Örn: 45 dk'),
                                ]),

                                TextInput::make('youtube_url')
                                    ->label('YouTube Video Linki')
                                    ->placeholder('https://www.youtube.com/watch?v=...')
                                    ->url()
                                    ->visible(fn ($get) => in_array($get('video_source'), ['youtube', 'vimeo', 'hls', 'iframe', 'custom'], true)),

                                FileUpload::make('video_path')
                                    ->label('Video Dosyası')
                                    ->disk('public')
                                    ->directory('episode-videos')
                                    ->acceptedFileTypes(['video/mp4', 'video/webm'])
                                    ->maxSize(102400)
                                    ->visible(fn ($get) => $get('video_source') === 'upload')
                                    ->helperText('Desteklenen formatlar: MP4, WebM. Maksimum 100 MB.'),

                                Placeholder::make('video_preview')
                                    ->label('Video Önizleme')
                                    ->content(function ($record) {
                                        if (! $record) {
                                            return new HtmlString('<div class="p-3 bg-gray-800 rounded-lg text-xs text-gray-400">Videoyu önizlemek için kaydı tamamlayınız.</div>');
                                        }

                                        if ($record->video_source === 'youtube' && filled($record->youtube_url)) {
                                            $embedUrl = $record->youtube_embed_url;
                                            if ($embedUrl) {
                                                return new HtmlString("
                                                    <div class='aspect-video rounded-lg overflow-hidden border border-gray-800 max-w-lg'>
                                                        <iframe src='{$embedUrl}' class='w-full h-full' allowfullscreen></iframe>
                                                    </div>
                                                ");
                                            }
                                        }

                                        if ($record->video_source === 'upload' && filled($record->video_path)) {
                                            $videoUrl = asset('storage/' . $record->video_path);
                                            return new HtmlString("
                                                <div class='max-w-lg'>
                                                    <video controls class='w-full rounded-lg border border-gray-800'>
                                                        <source src='{$videoUrl}'>
                                                    </video>
                                                </div>
                                            ");
                                        }

                                        return new HtmlString('<div class="p-3 bg-gray-800 rounded-lg text-xs text-gray-400">Henüz geçerli bir video kaynağı tanımlanmadı.</div>');
                                    })
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('🖼️ Görseller')
                            ->schema([
                                Grid::make(3)->schema([
                                    FileUpload::make('thumbnail')
                                        ->label('Bölüm Kapak Görseli (Dikey)')
                                        ->image()
                                        ->disk('public')
                                        ->directory('episodes')
                                        ->maxSize(5120)
                                        ->helperText('1080 × 1350 px. Max 5 MB.'),

                                    FileUpload::make('horizontal_image')
                                        ->label('Yatay Bölüm Görseli')
                                        ->image()
                                        ->disk('public')
                                        ->directory('episodes')
                                        ->maxSize(5120)
                                        ->helperText('1920 × 1080 px (16:9). Max 5 MB.'),

                                    FileUpload::make('social_image')
                                        ->label('Sosyal Paylaşım Görseli')
                                        ->image()
                                        ->disk('public')
                                        ->directory('episodes')
                                        ->maxSize(5120)
                                        ->helperText('1200 × 630 px. Max 5 MB.'),
                                ]),

                                Placeholder::make('image_fallback_info')
                                    ->label('Görsel Öncelik Sırası (Fallback)')
                                    ->content(new HtmlString('
                                        <div class="p-3 bg-gray-900 border border-gray-800 rounded-lg text-xs text-gray-400 space-y-1">
                                            <span class="font-bold text-amber-400 block">ℹ️ Görsel Öncelik Hiyerarşisi:</span>
                                            <span>1. Bölüm Görseli ➔ 2. Program Varsayılan Bölüm Görseli ➔ 3. Program Kapak Görseli</span>
                                        </div>
                                    '))
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('🏷️ Kategoriler')
                            ->schema([
                                Placeholder::make('program_categories')
                                    ->label('Program Kategorileri (Salt Okunur)')
                                    ->content(function ($record) {
                                        if (! $record || ! $record->program) {
                                            return new HtmlString('<div class="p-3 bg-gray-800 rounded-lg text-xs text-gray-400">Programa bağlı kategorileri görmek için bir program seçiniz.</div>');
                                        }

                                        $cats = $record->program->categories->pluck('name')->implode(', ');
                                        if (blank($cats)) {
                                            $cats = 'Bu programa ait bir kategori henüz tanımlanmadı.';
                                        }

                                        return new HtmlString("
                                            <div class='p-4 bg-gray-900 border border-gray-800 rounded-lg text-sm text-gray-300'>
                                                <span class='text-gray-400 block text-xs font-semibold mb-1'>BAĞLI PROGRAM: {$record->program->name}</span>
                                                <span class='font-medium text-amber-400'>{$cats}</span>
                                            </div>
                                        ");
                                    })
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('📡 Yayın Bilgileri')
                            ->schema([
                                Grid::make(2)->schema([
                                    DatePicker::make('aired_at')
                                        ->label('Orijinal Yayın Tarihi'),
                                ]),

                                Placeholder::make('schedule_actions')
                                    ->label('Yayın Akışı Yönlendirmeleri')
                                    ->content(function () {
                                        $calendarUrl = url('/admin/schedule-calendar');

                                        return new HtmlString("
                                            <div class='pt-2'>
                                                <a href='{$calendarUrl}' class='inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold rounded-lg transition'>
                                                    📅 Yayın Akışını Aç
                                                </a>
                                            </div>
                                        ");
                                    })
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('🔍 SEO')
                            ->schema([
                                TextInput::make('meta_title')
                                    ->label('SEO Başlığı (Meta Title)')
                                    ->placeholder('Boş bırakılırsa bölüm başlığı kullanılır'),

                                Textarea::make('meta_description')
                                    ->label('Meta Açıklaması (Meta Description)')
                                    ->rows(3)
                                    ->placeholder('Bölümün Google arama sonuçlarında görünecek kısa özeti'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
