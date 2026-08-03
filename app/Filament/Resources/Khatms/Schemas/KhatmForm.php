<?php

namespace App\Filament\Resources\Khatms\Schemas;

use App\Models\Khatm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KhatmForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Hatim Başlığı')
                    ->placeholder('Örn: Dost TV Genel Hatm-i Şerif')
                    ->required(),

                TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('Boş bırakılırsa başlıktan otomatik üretilir.'),

                Select::make('status')
                    ->label('Durum')
                    ->options(Khatm::STATUSES)
                    ->default('active')
                    ->required(),

                TextInput::make('total_juz')
                    ->label('Toplam Cüz Sayısı')
                    ->numeric()
                    ->default(30)
                    ->required(),

                DatePicker::make('start_date')
                    ->label('Başlangıç Tarihi'),

                DatePicker::make('end_date')
                    ->label('Bitiş Tarihi'),

                Textarea::make('description')
                    ->label('Açıklama')
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('Yönetici Notları')
                    ->columnSpanFull(),
            ]);
    }
}
