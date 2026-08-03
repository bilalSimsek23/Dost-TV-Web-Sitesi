<?php

namespace App\Filament\Resources\ScheduleTemplates\Schemas;

use App\Models\ScheduleTemplate;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ScheduleTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dönem Bilgileri')
                    ->schema([
                        TextInput::make('name')
                            ->label('Dönem Adı')
                            ->placeholder('Örn: Genel Yayın Akışı 2026')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('Kısa Açıklama')
                            ->placeholder('Bu yayın dönemi hakkında açıklama (isteğe bağlı)...')
                            ->rows(3)
                            ->maxLength(500),

                        Grid::make(2)->schema([
                            DatePicker::make('valid_from')
                                ->label('Başlangıç Tarihi')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $get) {
                                    static::checkOverlapWarning($state, $get('valid_until'));
                                }),

                            DatePicker::make('valid_until')
                                ->label('Bitiş Tarihi')
                                ->required()
                                ->afterOrEqual('valid_from')
                                ->live()
                                ->afterStateUpdated(function ($state, $get) {
                                    static::checkOverlapWarning($get('valid_from'), $state);
                                }),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('status')
                                ->label('Durum')
                                ->options([
                                    'draft' => 'Taslak',
                                    'published' => 'Yayında',
                                    'archived' => 'Arşivlendi',
                                ])
                                ->default('draft')
                                ->required(),

                            Toggle::make('is_active')
                                ->label('Varsayılan Dönem (Aktif)')
                                ->default(false)
                                ->helperText('Aktif yapıldığında diğer tüm dönemlerin varsayılan durumu kaldırılır.'),
                        ]),
                    ]),
            ]);
    }

    protected static function checkOverlapWarning(?string $from, ?string $until): void
    {
        if (! $from || ! $until) {
            return;
        }

        $overlapExists = ScheduleTemplate::where(function ($q) use ($from, $until) {
            $q->whereBetween('valid_from', [$from, $until])
                ->orWhereBetween('valid_until', [$from, $until])
                ->orWhere(function ($sub) use ($from, $until) {
                    $sub->where('valid_from', '<=', $from)->where('valid_until', '>=', $until);
                });
        })->exists();

        if ($overlapExists) {
            Notification::make()
                ->title('Tarih Aralığı Uyarısı')
                ->body('Bu tarih aralığında başka bir yayın dönemi bulunmaktadır.')
                ->warning()
                ->send();
        }
    }
}
