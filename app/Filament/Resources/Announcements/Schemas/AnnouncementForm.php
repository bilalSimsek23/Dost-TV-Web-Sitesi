<?php

namespace App\Filament\Resources\Announcements\Schemas;

use App\Models\Announcement;
use App\Models\AnnouncementType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('announcement-tabs')
                    ->tabs([
                        Tab::make('Genel Bilgiler')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Duyuru Başlığı')
                                    ->required()
                                    ->maxLength(150),

                                Select::make('announcement_type_id')
                                    ->label('Duyuru Türü')
                                    ->relationship('announcementType', 'name', fn ($query) => $query->where('is_active', true)->orderBy('sort_order'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Tür Adı')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),

                                        TextInput::make('slug')
                                            ->label('Slug')
                                            ->required()
                                            ->unique('announcement_types', 'slug'),
                                    ]),

                                Textarea::make('message')
                                    ->label('Kısa Mesaj (İsteğe Bağlı)')
                                    ->maxLength(500)
                                    ->rows(4)
                                    ->helperText('Duyurular kısa ve hızlı bilgilendirmeler içindir. Boş bırakılabilir.')
                                    ->columnSpanFull(),

                                FileUpload::make('image')
                                    ->label('Duyuru Görseli (İsteğe Bağlı)')
                                    ->image()
                                    ->disk('public')
                                    ->directory('announcements')
                                    ->visibility('public')
                                    ->acceptedFileTypes([
                                        'image/jpeg',
                                        'image/png',
                                        'image/webp',
                                    ])
                                    ->maxSize(25600)
                                    ->helperText('JPG, PNG veya WEBP yükleyebilirsiniz. Maksimum 25 MB. Görsel oranı korunur ve sistem tarafından otomatik optimize edilir.')
                                    ->columnSpanFull(),

                                Grid::make(2)->schema([
                                    TextInput::make('button_text')
                                        ->label('Buton Metni (İsteğe Bağlı)')
                                        ->placeholder('Örn: Yayını İzle')
                                        ->maxLength(60),

                                    TextInput::make('button_url')
                                        ->label('Buton Bağlantı Adresi (URL)')
                                        ->placeholder('Örn: /canli-tv veya https://...')
                                        ->maxLength(255)
                                        ->helperText('Duyuruya tıklandığında açılacak sayfa bağlantısı. Boşsa buton gösterilmez.'),
                                ])->columnSpanFull(),
                            ]),

                        Tab::make('Gösterim')
                            ->schema([
                                Select::make('placement')
                                    ->label('Gösterim Yeri')
                                    ->options(Announcement::PLACEMENTS)
                                    ->default('global')
                                    ->required(),

                                Radio::make('status_selector')
                                    ->label('Duyuru Durumu')
                                    ->options([
                                        'active' => '🟢 Aktif Duyuru',
                                        'scheduled' => '🟡 Planlanan Duyuru',
                                        'draft' => '🔵 Taslak Duyuru',
                                        'expired' => '⚪ Süresi Dolan Duyuru',
                                    ])
                                    ->descriptions([
                                        'active' => 'Şu anda yayında görünür.',
                                        'scheduled' => 'Belirlenen başlangıç tarihinde otomatik yayına girer.',
                                        'draft' => 'Kayıtlıdır ancak public sitede görünmez.',
                                        'expired' => 'Yayın süresi tamamlanmış geçmiş duyurudur.',
                                    ])
                                    ->default('active')
                                    ->live()
                                    ->afterStateHydrated(function (Radio $component, ?Announcement $record) {
                                        if (! $record) {
                                            $component->state('active');
                                            return;
                                        }

                                        if ($record->ends_at && $record->ends_at->isPast()) {
                                            $component->state('expired');
                                        } elseif (! $record->is_active) {
                                            $component->state('draft');
                                        } elseif ($record->starts_at && $record->starts_at->isFuture()) {
                                            $component->state('scheduled');
                                        } else {
                                            $component->state('active');
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state === 'draft' || $state === 'expired') {
                                            $set('is_active', false);
                                        } else {
                                            $set('is_active', true);
                                        }
                                    })
                                    ->dehydrated(false)
                                    ->columnSpanFull(),

                                Hidden::make('is_active')
                                    ->default(true),

                                Toggle::make('is_pinned')
                                    ->label('📌 Sabitle / Öne Çıkar')
                                    ->default(false),

                                DateTimePicker::make('starts_at')
                                    ->label('Başlangıç Tarihi')
                                    ->required(fn (callable $get) => $get('status_selector') === 'scheduled')
                                    ->helperText(function (callable $get) {
                                        return match ($get('status_selector')) {
                                            'scheduled' => 'Başlangıç tarihi gelecekte olmalıdır.',
                                            'expired' => 'Geçmiş başlangıç tarihi.',
                                            'draft' => 'Duyuru kayıtlı kalır ancak sitede görünmez.',
                                            default => 'Başlangıç tarihi boşsa hemen gösterilir.',
                                        };
                                    }),

                                DateTimePicker::make('ends_at')
                                    ->label('Bitiş Tarihi')
                                    ->afterOrEqual('starts_at')
                                    ->required(fn (callable $get) => $get('status_selector') === 'expired')
                                    ->helperText(function (callable $get) {
                                        return match ($get('status_selector')) {
                                            'expired' => 'Bitiş tarihi geçmiş veya şu an olmalıdır.',
                                            'scheduled' => 'Planlanan duyurunun otomatik kapanış tarihi.',
                                            'draft' => 'Duyuru kayıtlı kalır ancak sitede görünmez.',
                                            default => 'Tarih girilmezse süresiz gösterilir.',
                                        };
                                    }),
                            ]),

                        Tab::make('Önizleme')
                            ->schema([
                                Placeholder::make('preview_card')
                                    ->label('Ziyaretçi Görünümü Önizlemesi')
                                    ->content(function (?Announcement $record, callable $get) {
                                        return new \Illuminate\Support\HtmlString(
                                            \Illuminate\Support\Facades\Blade::render(
                                                '<x-site.announcement-popup :preview="true" :announcement="$record" :title="$title" :message="$message" :image="$image" :button-text="$buttonText" :button-url="$buttonUrl" />',
                                                [
                                                    'record' => $record,
                                                    'title' => $get('title') ?? ($record ? $record->title : 'Duyuru Başlığı'),
                                                    'message' => $get('message') ?? ($record ? $record->message : null),
                                                    'image' => $get('image') ?? ($record ? $record->image : null),
                                                    'buttonText' => $get('button_text') ?? ($record ? $record->button_text : null),
                                                    'buttonUrl' => $get('button_url') ?? ($record ? $record->button_url : null),
                                                ]
                                            )
                                        );
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
