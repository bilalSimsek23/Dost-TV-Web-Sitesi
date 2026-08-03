<?php

namespace App\Filament\Pages;

use App\Models\Episode;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\ScheduleTemplate;
use App\Models\ScheduleTemplateItem;
use App\Models\ScheduleVersionHistory;
use App\Services\Schedule\ScheduleCalendarService;
use Illuminate\Support\Carbon;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ScheduleCalendarPage extends Page implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Yayın Yönetimi';

    protected static ?string $navigationLabel = 'Yayın Akışı';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Yayın Akışı';

    protected static ?string $slug = 'schedule-calendar';

    protected string $view = 'filament.pages.schedule-calendar';

    public function getMaxContentWidth(): \Filament\Support\Enums\Width | string | null
    {
        return \Filament\Support\Enums\Width::Full;
    }

    public ?int $selectedTemplateId = null;

    public string $viewMode = 'weekly'; // 'daily', 'weekly', 'monthly'

    public int $selectedDay = 0;

    public string $activeDayTab = '0'; // 0 = Pazartesi, 6 = Pazar

    public bool $showTemplateSelector = false;

    public function mount(): void
    {
        if (request()->has('template')) {
            $queryTemplateId = (int) request()->query('template');
            if (ScheduleTemplate::where('id', $queryTemplateId)->exists()) {
                $this->selectedTemplateId = $queryTemplateId;
            }
        }

        if (! $this->selectedTemplateId) {
            $service = app(ScheduleCalendarService::class);
            $template = $service->getActiveOrSelectedTemplate();

            if ($template) {
                $this->selectedTemplateId = $template->id;
            }
        }

        $this->selectedDay = (int) $this->activeDayTab;
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['daily', 'weekly', 'monthly'])) {
            $this->viewMode = $mode;
        }
    }

    public function toggleTemplateSelector(): void
    {
        $this->showTemplateSelector = ! $this->showTemplateSelector;
    }

    public function getSelectedTemplateProperty(): ?ScheduleTemplate
    {
        return app(ScheduleCalendarService::class)->getActiveOrSelectedTemplate($this->selectedTemplateId);
    }

    public function getTemplatesProperty()
    {
        return app(ScheduleCalendarService::class)->getTemplates();
    }

    public function getDayCountsProperty(): array
    {
        $template = $this->selectedTemplate;
        if (! $template) {
            return array_fill(0, 7, 0);
        }

        return app(ScheduleCalendarService::class)->getDayCounts($template);
    }

    public function selectTemplate(int $id): void
    {
        $this->selectedTemplateId = $id;
        $this->resetTable();
    }

    public function updatedSelectedTemplateId($value): void
    {
        $this->selectedTemplateId = (int) $value;
        $this->resetTable();
    }

    public function toggleItemActive(int $itemId): void
    {
        $item = ScheduleTemplateItem::find($itemId);
        if ($item) {
            $item->update(['is_active' => ! $item->is_active]);
            $this->resetTable();

            Notification::make()
                ->title('Yayın durumu güncellendi: ' . ($item->fresh()->is_active ? 'Aktif' : 'Pasif'))
                ->success()
                ->send();
        }
    }

    public function selectDay(int $day): void
    {
        $this->selectedDay = $day;
        $this->activeDayTab = (string) $day;
        $this->resetTable();
    }

    public function selectDayTab(string $day): void
    {
        $this->selectDay((int) $day);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $templateId = $this->selectedTemplateId ?: 0;

                return ScheduleTemplateItem::query()
                    ->where('schedule_template_id', $templateId)
                    ->where('day_of_week', $this->selectedDay)
                    ->where('is_active', true)
                    ->with('program');
            })
            ->columns([
                TextColumn::make('start_time')
                    ->label('Başlangıç')
                    ->state(fn (ScheduleTemplateItem $record) => $record->start_time ? Carbon::parse($record->start_time)->format('H:i') : '—')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color('primary'),

                TextColumn::make('end_time')
                    ->label('Bitiş')
                    ->state(fn (ScheduleTemplateItem $record) => $record->end_time ? Carbon::parse($record->end_time)->format('H:i') : '—')
                    ->sortable()
                    ->color('gray'),

                TextColumn::make('display_title')
                    ->label('Program')
                    ->description(fn (ScheduleTemplateItem $record) => $record->note ?: ($record->custom_title ?: null))
                    ->searchable(query: fn ($query, $search) => $query->whereHas('program', fn ($q) => $q->where('name', 'like', "%{$search}%")))
                    ->weight(FontWeight::Bold),

                TextColumn::make('type_badge')
                    ->label('Yayın Türü')
                    ->badge()
                    ->state(fn (ScheduleTemplateItem $record) => match (true) {
                        (bool) $record->is_live => 'CANLI YAYIN',
                        (bool) $record->is_repeat => 'TEKRAR YAYIN',
                        default => 'NORMAL',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'CANLI YAYIN' => 'danger',
                        'TEKRAR YAYIN' => 'warning',
                        default => 'gray',
                    }),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->defaultSort('start_time', 'asc')
            ->recordActions([
                EditAction::make('edit')
                    ->label('Düzenle')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->modalHeading('Yayın Kaydını Düzenle')
                    ->modalSubmitActionLabel('Değişiklikleri Kaydet')
                    ->modalCancelActionLabel('Vazgeç')
                    ->fillForm(fn (ScheduleTemplateItem $record): array => [
                        'program_id' => $record->program_id,
                        'day_of_week' => (int) $record->day_of_week,
                        'start_time' => $record->start_time ? Carbon::parse($record->start_time)->format('H:i') : null,
                        'end_time' => $record->end_time ? Carbon::parse($record->end_time)->format('H:i') : null,
                        'is_live' => (bool) $record->is_live,
                        'is_repeat' => (bool) $record->is_repeat,
                        'is_active' => (bool) $record->is_active,
                        'custom_title' => $record->custom_title,
                        'image' => $record->image,
                        'description' => $record->description,
                        'note' => $record->note,
                    ])
                    ->schema(fn (): array => $this->getScheduleItemFormSchema())
                    ->action(function (ScheduleTemplateItem $record, array $data, ScheduleCalendarService $service) {
                        $hasOverlap = $service->checkOverlap(
                            $record->schedule_template_id,
                            (int) $data['day_of_week'],
                            $data['start_time'],
                            $data['end_time'],
                            $record->id
                        );

                        if ($hasOverlap) {
                            Notification::make()
                                ->title('Saat Çakışması Uyarısı!')
                                ->body('Seçilen gün ve saat aralığında başka bir yayın bulunmaktadır.')
                                ->warning()
                                ->send();
                        }

                        $record->update([
                            'program_id' => $data['program_id'],
                            'day_of_week' => (int) $data['day_of_week'],
                            'start_time' => $data['start_time'],
                            'end_time' => $data['end_time'],
                            'is_live' => (bool) ($data['is_live'] ?? false),
                            'is_repeat' => (bool) ($data['is_repeat'] ?? false),
                            'is_active' => (bool) ($data['is_active'] ?? true),
                            'custom_title' => $data['custom_title'] ?? null,
                            'image' => $data['image'] ?? null,
                            'description' => $data['description'] ?? null,
                            'note' => $data['note'] ?? null,
                        ]);

                        if ($record->template && $record->template->status === 'published') {
                            $record->template->update(['status' => 'draft']);
                        }

                        Notification::make()->title('Yayın kaydı güncellendi.')->success()->send();
                    }),

                ActionGroup::make([
                    Action::make('copy_item')
                        ->label('Kopyala')
                        ->icon(Heroicon::OutlinedDocumentDuplicate)
                        ->color('info')
                        ->schema([
                            Select::make('target_day')
                                ->label('Kopyalanacak Hedef Gün')
                                ->options(Schedule::DAYS)
                                ->default(fn (ScheduleTemplateItem $record) => ($record->day_of_week + 1) % 7)
                                ->required(),

                            TimePicker::make('start_time')
                                ->label('Hedef Başlangıç Saati')
                                ->seconds(false)
                                ->default(fn (ScheduleTemplateItem $record) => Carbon::parse($record->start_time)->format('H:i'))
                                ->required(),
                        ])
                        ->action(function (ScheduleTemplateItem $record, array $data, ScheduleCalendarService $service) {
                            $targetDay = (int) $data['target_day'];
                            $startTime = $data['start_time'];

                            $startCarbon = Carbon::parse($record->start_time);
                            $endCarbon = $record->end_time ? Carbon::parse($record->end_time) : $startCarbon->copy()->addHour();
                            $durationMinutes = $startCarbon->diffInMinutes($endCarbon);

                            $newStart = Carbon::parse($startTime);
                            $newEnd = $newStart->copy()->addMinutes($durationMinutes)->format('H:i');

                            $hasOverlap = $service->checkOverlap(
                                $record->schedule_template_id,
                                $targetDay,
                                $newStart->format('H:i'),
                                $newEnd
                            );

                            if ($hasOverlap) {
                                Notification::make()
                                    ->title('Saat Çakışması Uyarısı!')
                                    ->body('Hedef günde belirtilen saat aralığında yayın çakışması tespit edildi.')
                                    ->warning()
                                    ->send();
                            }

                            $newItem = $record->replicate();
                            $newItem->day_of_week = $targetDay;
                            $newItem->start_time = $newStart->format('H:i');
                            $newItem->end_time = $newEnd;
                            $newItem->save();

                            if ($record->template && $record->template->status === 'published') {
                                $record->template->update(['status' => 'draft']);
                            }

                            Notification::make()
                                ->title('Yayın kaydı ' . Schedule::DAYS[$targetDay] . ' gününe kopyalandı.')
                                ->success()
                                ->send();
                        }),

                    Action::make('move_day')
                        ->label('Başka Güne Taşı')
                        ->icon(Heroicon::OutlinedArrowRightOnRectangle)
                        ->color('warning')
                        ->schema([
                            Select::make('target_day')
                                ->label('Taşınacak Hedef Gün')
                                ->options(Schedule::DAYS)
                                ->required(),
                        ])
                        ->action(function (ScheduleTemplateItem $record, array $data) {
                            $targetDay = (int) $data['target_day'];
                            $record->update(['day_of_week' => $targetDay]);

                            if ($record->template && $record->template->status === 'published') {
                                $record->template->update(['status' => 'draft']);
                            }

                            Notification::make()
                                ->title('Yayın kaydı ' . Schedule::DAYS[$targetDay] . ' gününe taşındı.')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()
                        ->label('Sil')
                        ->modalHeading('Yayın Kaydını Sil')
                        ->modalDescription('Bu yayın kaydını silmek istediğinizden emin misiniz?')
                        ->action(function (ScheduleTemplateItem $record) {
                            $template = $record->template;
                            $record->delete();

                            if ($template && $template->status === 'published') {
                                $template->update(['status' => 'draft']);
                            }

                            Notification::make()->title('Yayın kaydı silindi.')->success()->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Bu gün için henüz yayın planlanmamış.')
            ->emptyStateDescription('Yukarıdaki "+ Yeni Yayın" butonunu kullanarak yeni bir yayın ekleyebilirsiniz.');
    }

    protected function getHeaderActions(): array
    {
        return [
            // 1. Taslağı Yayınla Action
            Action::make('publish_template')
                ->label('Taslağı Yayınla')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Yayın Akışı Taslağını Canlıya Al')
                ->modalDescription('Tüm değişiklikler kaydedilecek ve yeni versiyon oluşturularak public sitede yayınlanacaktır.')
                ->schema([
                    TextInput::make('change_summary')
                        ->label('Değişiklik Özeti / Yayın Notu')
                        ->placeholder('Örn. Yaz sezonu ikindi kuşağı saatleri güncellendi.')
                        ->default('Yeni sürüm yayınlandı.')
                        ->required(),
                ])
                ->action(function (array $data, ScheduleCalendarService $service) {
                    $template = $this->selectedTemplate;
                    if (! $template) {
                        return;
                    }

                    $service->publishTemplate($template, auth()->id(), $data['change_summary']);

                    Notification::make()
                        ->title('Yayın Akışı v' . $template->version . ' başarıyla canlıya alındı!')
                        ->success()
                        ->send();
                }),

            // 2. + Akış Ekle Header Action
            Action::make('create_template')
                ->label('+ Akış Ekle')
                ->color('primary')
                ->modalHeading('Yeni Yayın Akışı Oluştur')
                ->schema([
                    TextInput::make('name')
                        ->label('Akış Adı')
                        ->placeholder('Örn: Yaz Akışı 2026')
                        ->required(),

                    Grid::make(2)->schema([
                        DatePicker::make('valid_from')
                            ->label('Başlangıç Tarihi')
                            ->native(false),

                        DatePicker::make('valid_until')
                            ->label('Bitiş Tarihi')
                            ->native(false)
                            ->afterOrEqual('valid_from'),
                    ]),

                    Select::make('creation_mode')
                        ->label('Akış Oluşturma Yöntemi')
                        ->options([
                            'empty' => 'Boş akış oluştur',
                            'copy' => 'Mevcut akıştan kopyala',
                        ])
                        ->default('empty')
                        ->live()
                        ->required(),

                    Select::make('source_template_id')
                        ->label('Kopyalanacak Akış')
                        ->options(fn () => ScheduleTemplate::pluck('name', 'id'))
                        ->default(fn () => $this->selectedTemplateId)
                        ->visible(fn ($get) => $get('creation_mode') === 'copy')
                        ->required(fn ($get) => $get('creation_mode') === 'copy'),
                ])
                ->action(function (array $data) {
                    $template = ScheduleTemplate::create([
                        'name' => $data['name'],
                        'slug' => ScheduleTemplate::generateUniqueSlug($data['name']),
                        'valid_from' => $data['valid_from'] ?? null,
                        'valid_until' => $data['valid_until'] ?? null,
                        'status' => 'draft',
                        'version' => 1,
                        'is_active' => false,
                    ]);

                    if (($data['creation_mode'] ?? 'empty') === 'copy' && ! empty($data['source_template_id'])) {
                        $source = ScheduleTemplate::with('items')->find($data['source_template_id']);
                        if ($source) {
                            foreach ($source->items as $item) {
                                $newItem = $item->replicate(['schedule_template_id']);
                                $newItem->schedule_template_id = $template->id;
                                $newItem->save();
                            }
                        }
                    }

                    $this->selectedTemplateId = $template->id;
                    $this->resetTable();

                    Notification::make()
                        ->title('"' . $template->name . '" akışı başarıyla oluşturuldu.')
                        ->success()
                        ->send();
                }),

            // 3. Yeni Yayın Ekle Header Action
            Action::make('create_item')
                ->label('+ Yeni Yayın')
                ->color('amber')
                ->modalHeading('Yeni Yayın Ekle')
                ->modalSubmitActionLabel('Yayını Ekle')
                ->modalCancelActionLabel('Vazgeç')
                ->schema(fn (): array => $this->getScheduleItemFormSchema((int) $this->activeDayTab))
                ->action(function (array $data, ScheduleCalendarService $service) {
                    $template = $this->selectedTemplate;
                    if (! $template) {
                        Notification::make()->title('Lütfen önce bir şablon seçiniz.')->danger()->send();
                        return;
                    }

                    $hasOverlap = $service->checkOverlap(
                        $template->id,
                        (int) $data['day_of_week'],
                        $data['start_time'],
                        $data['end_time']
                    );

                    if ($hasOverlap) {
                        Notification::make()
                            ->title('Saat Çakışması Uyarısı!')
                            ->body('Seçilen gün ve saat aralığında başka bir yayın bulunmaktadır. Yayın eklenemedi.')
                            ->danger()
                            ->send();

                        return;
                    }

                    ScheduleTemplateItem::create([
                        'schedule_template_id' => $template->id,
                        'program_id' => $data['program_id'],
                        'day_of_week' => (int) $data['day_of_week'],
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'custom_title' => $data['custom_title'] ?? null,
                        'image' => $data['image'] ?? null,
                        'description' => $data['description'] ?? null,
                        'is_live' => (bool) ($data['is_live'] ?? false),
                        'is_repeat' => (bool) ($data['is_repeat'] ?? false),
                        'is_active' => (bool) ($data['is_active'] ?? true),
                        'note' => $data['note'] ?? null,
                    ]);

                    if ($template->status === 'published') {
                        $template->update(['status' => 'draft']);
                    }

                    $this->resetTable();

                    Notification::make()
                        ->title('Yayın kaydı başarıyla eklendi.')
                        ->success()
                        ->send();
                }),

            // 4. Toplu İşlemler Grubu
            ActionGroup::make([
                Action::make('duplicate_template')
                    ->label('Gelecek Sezona Kopyala')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->schema([
                        TextInput::make('new_name')
                            ->label('Yeni Şablon Adı')
                            ->default(fn () => ($this->selectedTemplate?->name ?? 'Sezon') . ' (Gelecek Yıl)')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $template = $this->selectedTemplate;
                        if (! $template) {
                            return;
                        }

                        $newTemplate = $template->duplicateForNextYear($data['new_name']);
                        $this->selectedTemplateId = $newTemplate->id;

                        Notification::make()
                            ->title('Yayın akışı gelecek sezon için kopyalandı.')
                            ->success()
                            ->send();
                    }),

                Action::make('copy_week')
                    ->label('Haftayı Başka Şablona Kopyala')
                    ->icon(Heroicon::OutlinedArrowRightOnRectangle)
                    ->schema([
                        Select::make('target_template_id')
                            ->label('Hedef Şablon')
                            ->options(ScheduleTemplate::where('id', '!=', $this->selectedTemplateId)->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (array $data, ScheduleCalendarService $service) {
                        $template = $this->selectedTemplate;
                        if (! $template) {
                            return;
                        }

                        $service->copyWeek($template, (int) $data['target_template_id']);

                        Notification::make()
                            ->title('Tüm haftalık akış hedef şablona kopyalandı.')
                            ->success()
                            ->send();
                    }),

                Action::make('shift_times')
                    ->label('Toplu Saat Kaydır (+ / -)')
                    ->icon(Heroicon::OutlinedClock)
                    ->schema([
                        Select::make('target_day')
                            ->label('Uygulanacak Gün')
                            ->options(['all' => 'Tüm Hafta (7 Gün)'] + Schedule::DAYS)
                            ->default('all')
                            ->required(),

                        Select::make('direction')
                            ->label('Yön')
                            ->options([
                                'forward' => 'İleri Kaydır (+)',
                                'backward' => 'Geri Kaydır (-)',
                            ])
                            ->default('forward')
                            ->required(),

                        TextInput::make('minutes')
                            ->label('Dakika Miktarı')
                            ->numeric()
                            ->default(30)
                            ->required(),
                    ])
                    ->action(function (array $data, ScheduleCalendarService $service) {
                        $template = $this->selectedTemplate;
                        if (! $template) {
                            return;
                        }

                        $minutes = (int) $data['minutes'];
                        if ($data['direction'] === 'backward') {
                            $minutes = -$minutes;
                        }

                        $targetDay = $data['target_day'] === 'all' ? null : (int) $data['target_day'];
                        $service->shiftTimes($template, $minutes, $targetDay);

                        $this->resetTable();

                        Notification::make()
                            ->title('Saatler başarıyla kaydırıldı.')
                            ->info()
                            ->send();
                    }),
            ])
            ->label('İşlemler')
            ->icon(Heroicon::OutlinedEllipsisVertical)
            ->color('gray'),
        ];
    }

    protected function getScheduleItemFormSchema(?int $defaultDay = null): array
    {
        return [
            Section::make('İçerik')
                ->schema([
                    Select::make('program_id')
                        ->label('Program')
                        ->options(Program::pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('custom_title')
                        ->label('Özel Yayın Başlığı (İsteğe Bağlı)')
                        ->placeholder('Boş bırakılırsa program adı kullanılır'),
                ]),

            Section::make('Zamanlama')
                ->schema([
                    Select::make('day_of_week')
                        ->label('Yayın Günü')
                        ->options(Schedule::DAYS)
                        ->default($defaultDay ?? 0)
                        ->required(),

                    Grid::make(2)->schema([
                        TimePicker::make('start_time')
                            ->label('Başlangıç Saati')
                            ->seconds(false)
                            ->required(),

                        TimePicker::make('end_time')
                            ->label('Bitiş Saati')
                            ->seconds(false)
                            ->required()
                            ->rules([
                                fn ($get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    if ($value && $get('start_time') && $value <= $get('start_time')) {
                                        $fail('Bitiş saati, başlangıç saatinden sonra olmalıdır.');
                                    }
                                },
                            ]),
                    ]),
                ]),

            Section::make('Yayın Bilgisi')
                ->schema([
                    Grid::make(3)->schema([
                        Toggle::make('is_live')->label('Canlı Yayın')->default(false),
                        Toggle::make('is_repeat')->label('Tekrar Yayın')->default(false),
                        Toggle::make('is_active')->label('Aktif')->default(true),
                    ]),
                ]),

            Section::make('Görsel ve Açıklama')
                ->schema([
                    FileUpload::make('image')
                        ->label('Yayın Görseli')
                        ->disk('public')
                        ->directory('schedules')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios(['16:9'])
                        ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
                        ->maxSize(5120)
                        ->helperText('Önerilen görsel boyutu: 1920 × 1080 piksel (16:9). En fazla 5 MB boyutunda JPG, PNG veya WEBP yükleyin. Görsel eklenmezse program kapağı kullanılacaktır.'),

                    Textarea::make('description')
                        ->label('Yayın Açıklaması')
                        ->maxLength(1000)
                        ->helperText('Bu açıklama, ilgili yayın public sitede gösterildiğinde kullanılabilir.'),
                ]),

            Section::make('Yönetici Notu')
                ->schema([
                    Textarea::make('note')
                        ->label('Yönetici Notu')
                        ->helperText('Bu not yalnızca yönetim panelinde görünür; public sitede yayınlanmaz.'),
                ]),
        ];
    }
}
